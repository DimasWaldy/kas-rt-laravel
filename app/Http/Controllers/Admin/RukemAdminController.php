<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RukemBantuanRequest;
use App\Models\RukemBantuan;
use App\Models\RukemClaim;
use App\Models\RukemIuran;
use App\Models\RukemMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RukemAdminController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();

        // Rekapitulasi Kas Sosial Rukem
        $totalIuranVerified = RukemIuran::visibleTo($actor)
            ->where('status', 'verified')
            ->sum('amount');

        $totalSantunanKeluar = RukemBantuan::visibleTo($actor)
            ->sum('amount');

        $saldoKasRukem = max(0, $totalIuranVerified - $totalSantunanKeluar);

        $pendingIuranCount = RukemIuran::visibleTo($actor)
            ->where('status', 'pending')
            ->count();

        $pendingClaimsCount = RukemClaim::visibleTo($actor)
            ->whereIn('status', ['submitted', 'verified'])
            ->count();

        $activeMembersCount = RukemMember::visibleTo($actor)
            ->where('status', 'active')
            ->count();

        // Data Klaim & Laporan Duka
        $claimStatusFilter = $request->query('claim_status');
        $claimsQuery = RukemClaim::visibleTo($actor)
            ->with(['user.warga', 'user.rt:id,name', 'bantuan', 'verifier:id,name', 'approver:id,name'])
            ->latest();

        if ($claimStatusFilter && in_array($claimStatusFilter, ['submitted', 'verified', 'approved', 'rejected', 'done'])) {
            $claimsQuery->where('status', $claimStatusFilter);
        }
        $claims = $claimsQuery->paginate(10, ['*'], 'page_claims');

        // Data Iuran
        $iurans = RukemIuran::visibleTo($actor)
            ->with(['user.warga', 'user.rt:id,name', 'verifier:id,name'])
            ->latest('period_year')
            ->latest('period_month')
            ->latest('id')
            ->paginate(10, ['*'], 'page_iurans');

        // Data Anggota
        $members = RukemMember::visibleTo($actor)
            ->with(['user.warga', 'user.rt:id,name'])
            ->latest()
            ->paginate(10, ['*'], 'page_members');

        // Riwayat Penyaluran Bantuan
        $bantuans = RukemBantuan::visibleTo($actor)
            ->with(['claim.user.warga', 'claim.user.rt:id,name', 'giver:id,name'])
            ->latest('given_at')
            ->paginate(10, ['*'], 'page_bantuans');

        return view('rukem.admin.index', compact(
            'totalIuranVerified',
            'totalSantunanKeluar',
            'saldoKasRukem',
            'pendingIuranCount',
            'pendingClaimsCount',
            'activeMembersCount',
            'claims',
            'iurans',
            'members',
            'bantuans',
            'claimStatusFilter'
        ));
    }

    public function showClaim(Request $request, RukemClaim $claim)
    {
        $actor = $request->user();
        $this->authorizeTerritory($actor, $claim);

        $claim->loadMissing(['user.warga', 'user.rt', 'bantuan.giver', 'verifier', 'approver']);

        return view('rukem.admin.show-claim', compact('claim'));
    }

    public function verifyClaim(Request $request, RukemClaim $claim)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('manage-rukem'), 403);
        $this->authorizeTerritory($actor, $claim);

        $validated = $request->validate([
            'status' => 'required|in:verified,rejected',
            'rejected_reason' => 'required_if:status,rejected|nullable|string|max:500',
        ]);

        if ($validated['status'] === 'verified') {
            $claim->update([
                'status' => 'verified',
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);
            $msg = 'Laporan duka berhasil diverifikasi. Laporan kini disiarkan ke warga dan siap diproses santunan.';
        } else {
            $claim->update([
                'status' => 'rejected',
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejected_reason' => $validated['rejected_reason'],
            ]);
            $msg = 'Laporan duka ditolak dengan alasan yang telah dicatat.';
        }

        return redirect()->route('admin.rukem.show-claim', $claim)->with('success', $msg);
    }

    public function approveClaim(Request $request, RukemClaim $claim)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('approve-rukem'), 403);
        $this->authorizeTerritory($actor, $claim);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejected_reason' => 'required_if:status,rejected|nullable|string|max:500',
        ]);

        if ($validated['status'] === 'approved') {
            $claim->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);
            $msg = 'Santunan duka telah disetujui. Silakan lakukan pencairan dan serah terima santunan.';
        } else {
            $claim->update([
                'status' => 'rejected',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'rejected_reason' => $validated['rejected_reason'],
            ]);
            $msg = 'Pengajuan santunan duka ditolak.';
        }

        return redirect()->route('admin.rukem.show-claim', $claim)->with('success', $msg);
    }

    public function disalurkanBantuan(RukemBantuanRequest $request, RukemClaim $claim)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('approve-rukem'), 403);
        $this->authorizeTerritory($actor, $claim);

        if (! in_array($claim->status, ['verified', 'approved'])) {
            throw ValidationException::withMessages([
                'amount' => 'Santunan duka hanya dapat disalurkan jika laporan telah diverifikasi atau disetujui.',
            ]);
        }

        if ($claim->bantuan) {
            throw ValidationException::withMessages([
                'amount' => 'Santunan duka untuk laporan ini sudah pernah disalurkan.',
            ]);
        }

        $validated = $request->validated();

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('rukem/bantuans', 'local');
        }

        RukemBantuan::create([
            'claim_id' => $claim->id,
            'rt_id' => $claim->rt_id,
            'rw_id' => $claim->rw_id,
            'amount' => $validated['amount'],
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'] ?? null,
            'given_at' => $validated['given_at'],
            'given_by' => $actor->id,
            'proof_path' => $proofPath,
            'note' => $validated['note'] ?? null,
        ]);

        $claim->update([
            'status' => 'done',
            'approved_by' => $claim->approved_by ?? $actor->id,
            'approved_at' => $claim->approved_at ?? now(),
        ]);

        return redirect()->route('admin.rukem.show-claim', $claim)->with('success', 'Santunan duka kematian berhasil disalurkan dan tercatat dalam buku kas sosial Rukem!');
    }

    public function verifyIuran(Request $request, RukemIuran $iuran)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('manage-rukem'), 403);
        $this->authorizeTerritory($actor, $iuran);

        $validated = $request->validate([
            'status' => 'required|in:verified,rejected',
        ]);

        $iuran->update([
            'status' => $validated['status'],
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        $statusText = $validated['status'] === 'verified' ? 'diterima dan diverifikasi' : 'ditolak';

        return redirect()->back()->with('success', "Iuran Rukem berhasil {$statusText}.");
    }

    public function updateMemberStatus(Request $request, RukemMember $member)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('manage-rukem'), 403);
        $this->authorizeTerritory($actor, $member);

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,pending,rejected',
        ]);

        $member->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Status keanggotaan Rukem warga berhasil diperbarui.');
    }

    public function export(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('export-rukem'), 403);

        $year = $request->query('year', now()->year);

        $claims = RukemClaim::visibleTo($actor)
            ->whereYear('death_date', $year)
            ->with(['user.warga', 'user.rt:id,name', 'bantuan.giver', 'verifier', 'approver'])
            ->latest('death_date')
            ->get();

        $iurans = RukemIuran::visibleTo($actor)
            ->where('period_year', $year)
            ->where('status', 'verified')
            ->with(['user.warga', 'user.rt:id,name'])
            ->get();

        $bantuans = RukemBantuan::visibleTo($actor)
            ->whereYear('given_at', $year)
            ->with(['claim.user.warga', 'claim.user.rt:id,name', 'giver'])
            ->latest('given_at')
            ->get();

        $totalIuran = $iurans->sum('amount');
        $totalSantunan = $bantuans->sum('amount');
        $saldo = max(0, $totalIuran - $totalSantunan);

        return view('rukem.admin.export', compact(
            'year',
            'claims',
            'iurans',
            'bantuans',
            'totalIuran',
            'totalSantunan',
            'saldo'
        ));
    }

    public function buktiBantuan(Request $request, RukemBantuan $bantuan)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('manage-rukem'), 403);
        $this->authorizeTerritory($actor, $bantuan);

        return $this->proofResponse($bantuan->proof_path);
    }

    private function authorizeTerritory(User $actor, $model): void
    {
        if ($actor->isGlobalOperator()) {
            return;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();
            abort_unless($rwId && (int) $model->rw_id === (int) $rwId, 403, 'Akses lintas RW tidak diizinkan.');

            return;
        }

        abort_unless($actor->rt_id && (int) $model->rt_id === (int) $actor->rt_id, 403, 'Akses lintas RT tidak diizinkan.');
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
