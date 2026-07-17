<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KoperasiAngsuran;
use App\Models\KoperasiMember;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiSimpanan;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KoperasiController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();

        $members = KoperasiMember::with('user.warga')
            ->whereHas('user', fn (Builder $query) => $this->scopeUsersToActor($query, $actor))
            ->latest()
            ->get();

        $pendingSimpanans = KoperasiSimpanan::with('user.warga')
            ->where('status', 'pending')
            ->whereHas('user', fn (Builder $query) => $this->scopeUsersToActor($query, $actor))
            ->latest()
            ->get();

        $pendingPinjamans = KoperasiPinjam::with('user.warga')
            ->where('status', 'menunggu_persetujuan')
            ->whereHas('user', fn (Builder $query) => $this->scopeUsersToActor($query, $actor))
            ->latest()
            ->get();

        $pendingAngsurans = KoperasiAngsuran::with('pinjaman.user.warga')
            ->where('status', 'pending')
            ->whereHas('pinjaman.user', fn (Builder $query) => $this->scopeUsersToActor($query, $actor))
            ->latest()
            ->get();

        return view('koperasi.admin.dashboard', compact(
            'members',
            'pendingSimpanans',
            'pendingPinjamans',
            'pendingAngsurans'
        ));
    }

    public function approveMember(Request $request, KoperasiMember $member)
    {
        $this->authorizeVisibleUser($request->user(), $member->user);

        $validated = $request->validate([
            'status' => 'required|in:aktif,nonaktif,ditolak',
        ]);

        $member->update(['status' => $validated['status']]);

        return back()->with('success', 'Status anggota berhasil diubah.');
    }

    public function approveSimpanan(Request $request, KoperasiSimpanan $simpanan)
    {
        $this->authorizeVisibleUser($request->user(), $simpanan->user);

        $validated = $request->validate([
            'status' => 'required|in:terverifikasi,ditolak',
            'rejected_reason' => 'required_if:status,ditolak|nullable|string|max:255',
        ]);

        $simpanan->update([
            'status' => $validated['status'],
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'rejected_reason' => $validated['rejected_reason'] ?? null,
        ]);

        return back()->with('success', 'Simpanan berhasil diverifikasi.');
    }

    public function approvePinjaman(Request $request, KoperasiPinjam $pinjaman)
    {
        $this->authorizeVisibleUser($request->user(), $pinjaman->user);

        $validated = $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'rejected_reason' => 'required_if:status,ditolak|nullable|string|max:255',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $data = [
            'status' => $validated['status'],
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_reason' => $validated['rejected_reason'] ?? null,
        ];

        if ($request->hasFile('proof_file')) {
            $data['proof_path'] = $request->file('proof_file')->store('koperasi/pinjaman', 'public');
        }

        $pinjaman->update($data);

        return back()->with('success', 'Pinjaman berhasil diverifikasi.');
    }

    public function approveAngsuran(Request $request, KoperasiAngsuran $angsuran)
    {
        $angsuran->loadMissing('pinjaman.user');
        $this->authorizeVisibleUser($request->user(), $angsuran->pinjaman?->user);

        $validated = $request->validate([
            'status' => 'required|in:terverifikasi,ditolak',
            'rejected_reason' => 'required_if:status,ditolak|nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $angsuran) {
            $angsuran->update([
                'status' => $validated['status'],
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'rejected_reason' => $validated['rejected_reason'] ?? null,
            ]);

            if ($validated['status'] === 'terverifikasi') {
                $pinjaman = $angsuran->pinjaman;
                $newRemaining = $pinjaman->remaining_amount - $angsuran->amount;

                $pinjaman->update([
                    'remaining_amount' => $newRemaining > 0 ? $newRemaining : 0,
                    'status' => $newRemaining <= 0 ? 'lunas' : $pinjaman->status,
                ]);
            }
        });

        return back()->with('success', 'Angsuran berhasil diverifikasi.');
    }

    private function scopeUsersToActor(Builder $query, User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return $query;
        }

        if ($actor->isRwOfficial()) {
            return $query->where(function (Builder $userQuery) use ($actor) {
                $userQuery->whereHas('rt', function (Builder $rtQuery) use ($actor) {
                    $rtQuery->where('rw_id', $this->resolveRwId($actor));
                })->orWhereNull('rt_id');
            });
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');

        return $query->where(function (Builder $userQuery) use ($actor) {
            $userQuery->where('rt_id', $actor->rt_id)
                ->orWhereNull('rt_id');
        });
    }

    private function authorizeVisibleUser(User $actor, ?User $target): void
    {
        abort_unless($target, 404);

        if ($actor->isGlobalOperator()) {
            return;
        }

        if ($actor->isRwOfficial()) {
            if ($target->rt_id === null) {
                return;
            }

            abort_unless((int) $target->rt()->value('rw_id') === $this->resolveRwId($actor), 404);

            return;
        }

        abort_unless($actor->rt_id && ($target->rt_id === null || $target->rt_id === $actor->rt_id), 404);
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($user->isRwOfficial() || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }
}
