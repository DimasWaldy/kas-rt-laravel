<?php

namespace App\Http\Controllers;

use App\Models\JenisSampah;
use App\Models\Rw;
use App\Models\SetoranSampah;
use App\Models\User;
use App\Services\BankSampahService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SetoranSampahController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);
        $canManage = $user->hasPermission('manage-bank-sampah');

        $query = SetoranSampah::with(['warga.rt', 'petugas', 'jenisSampah'])
            ->where('rw_id', $rwId)
            ->latest();

        if (! $canManage) {
            $query->where('warga_id', $user->id);
        }

        $status = $request->string('status')->toString();
        if (in_array($status, ['menunggu', 'diverifikasi', 'ditolak'], true)) {
            $query->where('status', $status);
        }

        return view('bank_sampah.setoran.index', [
            'setorans' => $query->paginate(12)->withQueryString(),
            'status' => $status,
            'canManage' => $canManage,
        ]);
    }

    public function create(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());
        $jenisSampah = JenisSampah::where('rw_id', $rwId)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return view('bank_sampah.setoran.create', compact('jenisSampah'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);

        $validated = $request->validate([
            'jenis_sampah_id' => [
                'required',
                'integer',
                Rule::exists('jenis_sampahs', 'id')->where('rw_id', $rwId)->where('is_active', true),
            ],
            'estimasi_berat' => ['required', 'numeric', 'min:0.1'],
            'tanggal_setor' => ['required', 'date'],
            'metode_setor' => ['required', Rule::in(['langsung_petugas', 'setor_mandiri'])],
            'foto_bukti' => ['required_if:metode_setor,setor_mandiri', 'nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'catatan_warga' => ['nullable', 'string', 'max:255'],
        ]);

        $fotoBukti = $request->hasFile('foto_bukti')
            ? $request->file('foto_bukti')->store('bank-sampah/setoran-bukti', 'local')
            : null;

        unset($validated['foto_bukti']);

        SetoranSampah::create([
            ...$validated,
            'rw_id' => $rwId,
            'warga_id' => $user->id,
            'foto_bukti' => $fotoBukti,
            'status' => 'menunggu',
            'nilai' => 0,
        ]);

        return redirect()->route('setoran-sampah.index')
            ->with('success', 'Setoran sampah berhasil diajukan dan menunggu verifikasi petugas.');
    }

    public function show(Request $request, SetoranSampah $setoran)
    {
        $this->authorizeVisible($request->user(), $setoran);
        $setoran->load(['warga.rt', 'petugas', 'jenisSampah']);

        return view('bank_sampah.setoran.show', compact('setoran'));
    }

    public function fotoBukti(Request $request, SetoranSampah $setoran)
    {
        $this->authorizeVisible($request->user(), $setoran);
        abort_unless($setoran->foto_bukti && Storage::disk('local')->exists($setoran->foto_bukti), 404);

        return Storage::disk('local')->response($setoran->foto_bukti);
    }

    public function verifikasi(Request $request, SetoranSampah $setoran, BankSampahService $service)
    {
        $this->authorizeManage($request->user(), $setoran->rw_id);

        $validated = $request->validate([
            'berat_aktual' => ['required', 'numeric', 'min:0.1'],
            'catatan_petugas' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->verifikasiSetoran(
                $setoran,
                (float) $validated['berat_aktual'],
                $request->user(),
                $validated['catatan_petugas'] ?? ''
            );
        } catch (Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Setoran berhasil diverifikasi dan saldo warga sudah bertambah.');
    }

    public function tolak(Request $request, SetoranSampah $setoran)
    {
        $this->authorizeManage($request->user(), $setoran->rw_id);

        $validated = $request->validate([
            'catatan_petugas' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        if ($setoran->status !== 'menunggu') {
            throw ValidationException::withMessages([
                'setoran' => 'Setoran ini sudah diproses.',
            ]);
        }

        $setoran->update([
            'status' => 'ditolak',
            'petugas_id' => $request->user()->id,
            'catatan_petugas' => $validated['catatan_petugas'],
        ]);

        return back()->with('success', 'Setoran berhasil ditolak.');
    }

    private function authorizeVisible(User $user, SetoranSampah $setoran): void
    {
        $this->authorizeRw($user, $setoran->rw_id);

        if (! $user->hasPermission('manage-bank-sampah')) {
            abort_unless($setoran->warga_id === $user->id, 403);
        }
    }

    private function authorizeManage(User $user, int $rwId): void
    {
        abort_unless($user->hasPermission('manage-bank-sampah'), 403);
        $this->authorizeRw($user, $rwId);
    }

    private function authorizeRw(User $user, int $rwId): void
    {
        if ($user->isGlobalOperator()) {
            return;
        }

        abort_unless($rwId === $this->resolveRwId($user), 403);
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($this->isRwLevelBankSampahOperator($user) || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    private function isRwLevelBankSampahOperator(User $user): bool
    {
        return $user->isRwOfficial() || $user->role_name === 'petugas_bank_sampah';
    }
}
