<?php

namespace App\Http\Controllers;

use App\Models\HadiahSampah;
use App\Models\PenukaranHadiah;
use App\Models\Rw;
use App\Models\SaldoSampah;
use App\Models\User;
use App\Services\BankSampahService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HadiahSampahController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);
        $canManage = $user->hasPermission('manage-bank-sampah');

        $hadiahs = HadiahSampah::where('rw_id', $rwId)
            ->when(! $canManage, fn($query) => $query->where('is_active', true))
            ->orderByDesc('is_active')
            ->orderBy('nilai_tukar')
            ->get();

        $saldoSaya = SaldoSampah::getOrCreate($user, $rwId);

        $penukaranMenunggu = $canManage
            ? PenukaranHadiah::with(['warga.rt', 'hadiah'])
                ->whereHas('hadiah', fn($query) => $query->where('rw_id', $rwId))
                ->where('status', 'menunggu')
                ->latest()
                ->get()
            : collect();

        return view('bank_sampah.hadiah.index', compact(
            'hadiahs',
            'saldoSaya',
            'penukaranMenunggu',
            'canManage'
        ));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-bank-sampah'), 403);

        return view('bank_sampah.hadiah.create');
    }

    public function store(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());
        $this->authorizeManage($request->user(), $rwId);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'nilai_tukar' => ['required', 'integer', 'min:1'],
            'stok' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('hadiah-sampah', 'local');
        }

        $validated['rw_id'] = $rwId;
        $validated['is_active'] = $request->boolean('is_active', true);

        HadiahSampah::create($validated);

        return redirect()->route('hadiah-sampah.index')
            ->with('success', 'Hadiah bank sampah berhasil ditambahkan.');
    }

    public function foto(Request $request, HadiahSampah $hadiah)
    {
        $this->authorizeRw($request->user(), $hadiah->rw_id);
        abort_unless($hadiah->foto && Storage::disk('local')->exists($hadiah->foto), 404);

        return Storage::disk('local')->response($hadiah->foto);
    }

    public function tukar(Request $request, HadiahSampah $hadiah)
    {
        $user = $request->user();
        $this->authorizeRw($user, $hadiah->rw_id);

        if (! $hadiah->isAvailable()) {
            throw ValidationException::withMessages([
                'hadiah' => 'Hadiah tidak tersedia atau stok sudah habis.',
            ]);
        }

        $saldo = SaldoSampah::getOrCreate($user, $hadiah->rw_id);

        if ($saldo->saldo < $hadiah->nilai_tukar) {
            throw ValidationException::withMessages([
                'hadiah' => 'Saldo belum cukup untuk menukar hadiah ini.',
            ]);
        }

        PenukaranHadiah::create([
            'warga_id' => $user->id,
            'hadiah_id' => $hadiah->id,
            'nilai_tukar_saat_itu' => $hadiah->nilai_tukar,
            'status' => 'menunggu',
            'catatan' => $request->string('catatan')->toString() ?: null,
        ]);

        return redirect()->route('hadiah-sampah.index')
            ->with('success', 'Pengajuan penukaran hadiah berhasil dibuat.');
    }

    public function konfirmasiTukar(Request $request, PenukaranHadiah $penukaran, BankSampahService $service)
    {
        $penukaran->load('hadiah');
        $this->authorizeManage($request->user(), $penukaran->hadiah->rw_id);

        try {
            $service->prosesPenukaran($penukaran, $request->user());
        } catch (Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Hadiah berhasil dikonfirmasi sudah diberikan.');
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
