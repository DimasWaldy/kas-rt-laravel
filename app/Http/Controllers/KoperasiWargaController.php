<?php

namespace App\Http\Controllers;

use App\Models\KoperasiAngsuran;
use App\Models\KoperasiMember;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiSimpanan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KoperasiWargaController extends Controller
{
    private const HIGH_LOAN_THRESHOLD = 1_000_000;

    private const MINIMUM_WAJIB_SAVING_FOR_HIGH_LOAN = 50_000;

    public function index(Request $request)
    {
        $user = $request->user();
        $isMember = $user->koperasiMember !== null;
        $memberStatus = $user->koperasiMember->status ?? null;

        if (! $isMember) {
            abort_unless($user->hasPermission('submit-koperasi'), 403);

            return view('koperasi.warga.daftar');
        }

        $simpananTotal = KoperasiSimpanan::where('user_id', $user->id)
            ->where('status', 'terverifikasi')
            ->sum('amount');

        $pinjamanAktif = KoperasiPinjam::with(['angsurans' => fn ($query) => $query->latest()])
            ->where('user_id', $user->id)
            ->whereIn('status', ['menunggu_persetujuan', 'disetujui'])
            ->get();

        $riwayatSimpanan = KoperasiSimpanan::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $riwayatAngsuran = KoperasiAngsuran::with('pinjaman')
            ->whereHas('pinjaman', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->limit(10)
            ->get();

        return view('koperasi.warga.index', compact(
            'isMember',
            'memberStatus',
            'simpananTotal',
            'pinjamanAktif',
            'riwayatSimpanan',
            'riwayatAngsuran'
        ));
    }

    public function storeDaftar(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('submit-koperasi'), 403);

        $created = DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (KoperasiMember::where('user_id', $user->id)->exists()) {
                return false;
            }

            $memberNumber = 'KOP-'.now()->format('Ym').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);

            KoperasiMember::create([
                'user_id' => $user->id,
                'member_number' => $memberNumber,
                'joined_at' => now(),
                'status' => 'pending',
            ]);

            return true;
        }, 3);

        if (! $created) {
            return back()->with('error', 'Anda sudah mendaftar sebagai anggota koperasi.');
        }

        return redirect()->route('koperasi.index')->with('success', 'Pendaftaran koperasi berhasil dikirim dan menunggu verifikasi bendahara.');
    }

    public function simpan()
    {
        $this->authorizeMember();

        return view('koperasi.warga.simpan');
    }

    public function storeSimpanan(Request $request)
    {
        $this->authorizeMember();

        $validated = $request->validate([
            'type' => 'required|in:pokok,wajib,sukarela',
            'amount' => 'required|integer|min:10000',
            'transaction_date' => 'required|date',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = $request->file('proof_file')->store('koperasi/simpanan', 'local');

        KoperasiSimpanan::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'proof_path' => $path,
            'status' => 'pending',
        ]);

        return redirect()->route('koperasi.index')->with('success', 'Simpanan berhasil diajukan, menunggu verifikasi pengurus.');
    }

    public function pinjam()
    {
        $this->authorizeMember();

        $simpananWajibTerverifikasi = $this->simpananWajibTerverifikasi(Auth::id());

        return view('koperasi.warga.pinjam', compact('simpananWajibTerverifikasi'));
    }

    public function storePinjam(Request $request)
    {
        $this->authorizeMember();

        $validated = $request->validate([
            'amount' => 'required|integer|min:50000',
            'tenor_months' => 'required|integer|min:1|max:24',
        ]);

        $result = DB::transaction(function () use ($validated) {
            User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();

            $activeLoan = KoperasiPinjam::where('user_id', Auth::id())
                ->whereIn('status', ['menunggu_persetujuan', 'disetujui'])
                ->exists();
            if ($activeLoan) {
                return 'active_loan';
            }

            $simpananWajibTerverifikasi = $this->simpananWajibTerverifikasi(Auth::id());
            if (
                $validated['amount'] >= self::HIGH_LOAN_THRESHOLD
                && $simpananWajibTerverifikasi < self::MINIMUM_WAJIB_SAVING_FOR_HIGH_LOAN
            ) {
                return 'insufficient_savings';
            }

            // E.g. flat rate 2% per month
            $serviceFeePercentage = 2.0;
            $serviceFeeAmount = (int) ($validated['amount'] * ($serviceFeePercentage / 100) * $validated['tenor_months']);
            $remainingAmount = $validated['amount'] + $serviceFeeAmount;

            KoperasiPinjam::create([
                'user_id' => Auth::id(),
                'amount' => $validated['amount'],
                'tenor_months' => $validated['tenor_months'],
                'service_fee_percentage' => $serviceFeePercentage,
                'service_fee_amount' => $serviceFeeAmount,
                'remaining_amount' => $remainingAmount,
                'status' => 'menunggu_persetujuan',
            ]);

            return 'created';
        }, 3);

        if ($result === 'active_loan') {
            return back()->with('error', 'Anda masih memiliki pinjaman yang sedang berjalan atau menunggu persetujuan.');
        }

        if ($result === 'insufficient_savings') {
            return back()
                ->withInput()
                ->with('error', 'Pinjaman Rp 1.000.000 atau lebih hanya bisa diajukan jika simpanan wajib terverifikasi minimal Rp 50.000.');
        }

        return redirect()->route('koperasi.index')->with('success', 'Pengajuan pinjaman berhasil dibuat, menunggu persetujuan pengurus.');
    }

    public function angsuran(KoperasiPinjam $pinjaman)
    {
        $this->authorizeMember();

        if ($pinjaman->user_id !== Auth::id()) {
            abort(403);
        }

        return view('koperasi.warga.angsuran', compact('pinjaman'));
    }

    public function storeAngsuran(Request $request, KoperasiPinjam $pinjaman)
    {
        $this->authorizeMember();

        $pinjaman->loadMissing('user');
        $minimumAmount = min(10000, max(1, (int) $pinjaman->remaining_amount));
        $validated = $request->validate([
            'amount' => 'required|integer|min:'.$minimumAmount,
            'paid_at' => 'required|date',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated, $pinjaman) {
            $lockedPinjaman = KoperasiPinjam::query()
                ->whereKey($pinjaman->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPinjaman->user_id !== Auth::id()) {
                abort(403);
            }

            if ($lockedPinjaman->status !== 'disetujui') {
                throw ValidationException::withMessages([
                    'amount' => 'Angsuran hanya bisa diajukan untuk pinjaman yang sudah disetujui dan masih aktif.',
                ]);
            }

            $pendingAngsuran = KoperasiAngsuran::query()
                ->where('koperasi_pinjam_id', $lockedPinjaman->id)
                ->where('status', 'pending')
                ->sum('amount');

            if (($pendingAngsuran + $validated['amount']) > $lockedPinjaman->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Jumlah angsuran pending dan angsuran baru melebihi sisa pinjaman.',
                ]);
            }

            $path = $request->file('proof_file')->store('koperasi/angsuran', 'local');

            KoperasiAngsuran::create([
                'koperasi_pinjam_id' => $lockedPinjaman->id,
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'],
                'proof_path' => $path,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('koperasi.index')->with('success', 'Pembayaran angsuran berhasil diajukan, menunggu verifikasi.');
    }

    private function authorizeMember()
    {
        $user = Auth::user();
        if (! $user->koperasiMember || $user->koperasiMember->status !== 'aktif') {
            abort(403, 'Anda belum terdaftar sebagai anggota koperasi yang aktif.');
        }
    }

    private function simpananWajibTerverifikasi(int $userId): int
    {
        return (int) KoperasiSimpanan::where('user_id', $userId)
            ->where('type', 'wajib')
            ->where('status', 'terverifikasi')
            ->sum('amount');
    }
}
