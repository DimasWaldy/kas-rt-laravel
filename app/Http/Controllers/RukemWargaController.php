<?php

namespace App\Http\Controllers;

use App\Http\Requests\RukemClaimRequest;
use App\Http\Requests\RukemIuranRequest;
use App\Models\RukemBantuan;
use App\Models\RukemClaim;
use App\Models\RukemIuran;
use App\Models\RukemMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RukemWargaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $member = $user->rukemMember;

        // Berita duka lingkungan (terverifikasi / disetujui / selesai) yang terlihat oleh warga di RT/RW-nya
        $beritaDuka = RukemClaim::broadcast()
            ->visibleTo($user)
            ->with(['user.warga', 'user.rt:id,name', 'bantuan'])
            ->latest('death_date')
            ->paginate(6, ['*'], 'page_duka');

        // Data pribadi warga
        $myClaims = RukemClaim::where('user_id', $user->id)
            ->with(['bantuan', 'verifier:id,name', 'approver:id,name'])
            ->latest()
            ->get();

        $myIurans = RukemIuran::where('user_id', $user->id)
            ->with('verifier:id,name')
            ->latest('period_year')
            ->latest('period_month')
            ->get();

        // Ringkasan transparansi kas sosial Rukem di wilayah warga
        $totalIuranTerkumpul = RukemIuran::visibleTo($user)
            ->where('status', 'verified')
            ->sum('amount');

        $totalSantunanDisalurkan = RukemBantuan::visibleTo($user)
            ->sum('amount');

        $saldoKasRukem = max(0, $totalIuranTerkumpul - $totalSantunanDisalurkan);

        $totalAnggotaAktif = RukemMember::visibleTo($user)
            ->where('status', 'active')
            ->count();

        $totalLaporanDuka = RukemClaim::visibleTo($user)->count();

        return view('rukem.warga.index', compact(
            'member',
            'beritaDuka',
            'myClaims',
            'myIurans',
            'totalIuranTerkumpul',
            'totalSantunanDisalurkan',
            'saldoKasRukem',
            'totalAnggotaAktif',
            'totalLaporanDuka'
        ));
    }

    public function storeDaftar(Request $request)
    {
        $user = $request->user();

        if ($user->rukemMember) {
            return redirect()->route('rukem.index')->with('warning', 'Anda sudah terdaftar sebagai anggota Rukun Kematian (Rukem).');
        }

        $rtId = $user->rt_id;
        $rwId = $user->assignedRwId();

        $count = RukemMember::count() + 1;
        $memberNumber = sprintf('RKM-%04d', $count);

        RukemMember::create([
            'user_id' => $user->id,
            'rt_id' => $rtId,
            'rw_id' => $rwId,
            'member_number' => $memberNumber,
            'joined_at' => now()->toDateString(),
            'status' => 'active',
            'notes' => 'Pendaftaran mandiri oleh warga.',
        ]);

        return redirect()->route('rukem.index')->with('success', 'Selamat, pendaftaran anggota Rukun Kematian (Rukem) Anda berhasil aktif!');
    }

    public function storeIuran(RukemIuranRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $exists = RukemIuran::where('user_id', $user->id)
            ->where('period_year', $validated['period_year'])
            ->where('period_month', $validated['period_month'])
            ->whereIn('status', ['pending', 'verified'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'period_month' => 'Iuran untuk periode bulan dan tahun ini sudah tercatat atau sedang menunggu verifikasi.',
            ]);
        }

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('rukem/iurans', 'local');
        }

        RukemIuran::create([
            'user_id' => $user->id,
            'rt_id' => $user->rt_id,
            'rw_id' => $user->assignedRwId(),
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'amount' => $validated['amount'],
            'proof_path' => $proofPath,
            'status' => 'pending',
            'paid_at' => now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('rukem.index')->with('success', 'Pembayaran iuran duka berhasil diajukan dan sedang menunggu verifikasi pengurus.');
    }

    public function createClaim()
    {
        return view('rukem.warga.create-claim');
    }

    public function storeClaim(RukemClaimRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('rukem/claims', 'local');
        }

        $claim = RukemClaim::create([
            'user_id' => $user->id,
            'rt_id' => $user->rt_id,
            'rw_id' => $user->assignedRwId(),
            'deceased_name' => $validated['deceased_name'],
            'nik_deceased' => $validated['nik_deceased'] ?? null,
            'relationship' => $validated['relationship'],
            'death_date' => $validated['death_date'],
            'funeral_date' => $validated['funeral_date'] ?? null,
            'cemetery_location' => $validated['cemetery_location'] ?? null,
            'description' => $validated['description'] ?? null,
            'proof_path' => $proofPath,
            'status' => 'submitted',
        ]);

        return redirect()->route('rukem.show-claim', $claim)->with('success', 'Laporan duka dan permohonan santunan kematian berhasil dikirimkan. Pengurus RT/RW akan segera memverifikasi.');
    }

    public function showClaim(Request $request, RukemClaim $claim)
    {
        $user = $request->user();

        // Cek izin akses: pemilik laporan, atau pengurus dengan manage-rukem, atau warga yang melihat siaran terverifikasi di wilayahnya
        $isOwner = $claim->user_id === $user->id;
        $canManage = $user->hasPermission('manage-rukem');
        $isBroadcastVisible = in_array($claim->status, ['verified', 'approved', 'done']) && $this->isWithinUserTerritory($user, $claim);

        abort_unless($isOwner || $canManage || $isBroadcastVisible, 403, 'Anda tidak berhak melihat data laporan duka ini.');

        $claim->loadMissing(['user.warga', 'user.rt', 'bantuan.giver', 'verifier', 'approver']);

        return view('rukem.warga.show-claim', compact('claim', 'isOwner', 'canManage'));
    }

    public function buktiIuran(Request $request, RukemIuran $iuran)
    {
        $user = $request->user();
        abort_unless($iuran->user_id === $user->id || $user->hasPermission('manage-rukem'), 403);

        return $this->proofResponse($iuran->proof_path);
    }

    public function buktiClaim(Request $request, RukemClaim $claim)
    {
        $user = $request->user();
        abort_unless($claim->user_id === $user->id || $user->hasPermission('manage-rukem'), 403);

        return $this->proofResponse($claim->proof_path);
    }

    private function isWithinUserTerritory(User $user, RukemClaim $claim): bool
    {
        if ($user->isGlobalOperator()) {
            return true;
        }

        if ($user->isRwOfficial()) {
            $userRw = $user->assignedRwId();

            return $userRw && (int) $claim->rw_id === (int) $userRw;
        }

        return $user->rt_id && (int) $claim->rt_id === (int) $user->rt_id;
    }

    private function proofResponse(?string $path)
    {
        abort_unless($path, 404);

        $disk = Storage::disk('local')->exists($path)
            ? 'local'
            : (Storage::disk('public')->exists($path) ? 'public' : null);

        abort_unless($disk, 404, 'Berkas bukti tidak ditemukan.');

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }
}
