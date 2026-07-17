<?php

namespace App\Http\Controllers;

use App\Models\KoperasiAngsuran;
use App\Models\KoperasiMember;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiSimpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KoperasiWargaController extends Controller
{
    private const HIGH_LOAN_THRESHOLD = 1_000_000;

    private const MINIMUM_WAJIB_SAVING_FOR_HIGH_LOAN = 50_000;

    public function index(Request $request)
    {
        $user = $request->user();
        $isMember = $user->koperasiMember !== null;
        $memberStatus = $user->koperasiMember->status ?? null;

        if (!$isMember) {
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

        if ($user->koperasiMember) {
            return back()->with('error', 'Anda sudah mendaftar sebagai anggota koperasi.');
        }

        $memberNumber = 'KOP-' . now()->format('Ym') . '-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);

        KoperasiMember::create([
            'user_id' => $user->id,
            'member_number' => $memberNumber,
            'joined_at' => now(),
            'status' => 'pending',
        ]);

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

        $path = $request->file('proof_file')->store('koperasi/simpanan', 'public');

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

        // Check if there's an active unpaid loan
        $activeLoan = KoperasiPinjam::where('user_id', Auth::id())
            ->whereIn('status', ['menunggu_persetujuan', 'disetujui'])
            ->exists();

        if ($activeLoan) {
            return back()->with('error', 'Anda masih memiliki pinjaman yang sedang berjalan atau menunggu persetujuan.');
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:50000',
            'tenor_months' => 'required|integer|min:1|max:24',
        ]);

        $simpananWajibTerverifikasi = $this->simpananWajibTerverifikasi(Auth::id());

        if (
            $validated['amount'] >= self::HIGH_LOAN_THRESHOLD
            && $simpananWajibTerverifikasi < self::MINIMUM_WAJIB_SAVING_FOR_HIGH_LOAN
        ) {
            return back()
                ->withInput()
                ->with('error', 'Pinjaman Rp 1.000.000 atau lebih hanya bisa diajukan jika simpanan wajib terverifikasi minimal Rp 50.000.');
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

        if ($pinjaman->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:10000',
            'paid_at' => 'required|date',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validated['amount'] > $pinjaman->remaining_amount) {
            return back()->with('error', 'Jumlah angsuran melebihi sisa pinjaman.');
        }

        $path = $request->file('proof_file')->store('koperasi/angsuran', 'public');

        KoperasiAngsuran::create([
            'koperasi_pinjam_id' => $pinjaman->id,
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'proof_path' => $path,
            'status' => 'pending',
        ]);

        return redirect()->route('koperasi.index')->with('success', 'Pembayaran angsuran berhasil diajukan, menunggu verifikasi.');
    }

    private function authorizeMember()
    {
        $user = Auth::user();
        if (!$user->koperasiMember || $user->koperasiMember->status !== 'aktif') {
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
