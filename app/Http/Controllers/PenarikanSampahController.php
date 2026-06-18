<?php

namespace App\Http\Controllers;

use App\Models\PenarikanSampah;
use App\Models\Rw;
use App\Models\SaldoSampah;
use App\Models\User;
use App\Services\BankSampahService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PenarikanSampahController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);
        $canManage = $user->hasPermission('manage-bank-sampah');

        $query = PenarikanSampah::with(['warga.rt', 'petugas'])
            ->where('rw_id', $rwId)
            ->latest();

        if (! $canManage) {
            $query->where('warga_id', $user->id);
        }

        return view('bank_sampah.tarik.index', [
            'penarikans' => $query->paginate(12),
            'canManage' => $canManage,
        ]);
    }

    public function create(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());
        $saldo = SaldoSampah::getOrCreate($request->user(), $rwId);

        return view('bank_sampah.tarik.create', compact('saldo'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);
        $saldo = SaldoSampah::getOrCreate($user, $rwId);

        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1000'],
            'catatan_warga' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['jumlah'] > $saldo->saldo) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah penarikan tidak boleh melebihi saldo.',
            ]);
        }

        PenarikanSampah::create([
            'warga_id' => $user->id,
            'rw_id' => $rwId,
            'jumlah' => $validated['jumlah'],
            'status' => 'menunggu',
            'catatan_warga' => $validated['catatan_warga'] ?? null,
        ]);

        return redirect()->route('bank-sampah.index')
            ->with('success', 'Pengajuan penarikan saldo berhasil dibuat.');
    }

    public function konfirmasi(Request $request, PenarikanSampah $penarikan, BankSampahService $service)
    {
        $this->authorizeManage($request->user(), $penarikan->rw_id);

        try {
            $service->prosesPenarikan($penarikan, $request->user(), $request->string('catatan_petugas')->toString());
        } catch (Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Penarikan saldo berhasil dikonfirmasi sebagai sudah dibayar.');
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
