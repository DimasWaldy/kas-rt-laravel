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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KoperasiController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();

        $members = KoperasiMember::with(['user.warga', 'user.rt:id,name'])
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
        $validated = $request->validate([
            'status' => 'required|in:pending,aktif,nonaktif,ditolak',
        ]);

        DB::transaction(function () use ($request, $member, $validated) {
            $lockedMember = KoperasiMember::query()
                ->with('user')
                ->whereKey($member->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeVisibleUser($request->user(), $lockedMember->user);

            $allowedTransitions = [
                'pending' => ['aktif', 'ditolak'],
                'aktif' => ['nonaktif'],
                'nonaktif' => ['aktif'],
                'ditolak' => ['pending'],
            ];

            if (! in_array($validated['status'], $allowedTransitions[$lockedMember->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Transisi status anggota koperasi tidak valid.',
                ]);
            }

            $lockedMember->update(['status' => $validated['status']]);
        });

        return back()->with('success', 'Status anggota berhasil diubah.');
    }

    public function approveSimpanan(Request $request, KoperasiSimpanan $simpanan)
    {
        $validated = $request->validate([
            'status' => 'required|in:terverifikasi,ditolak',
            'rejected_reason' => 'required_if:status,ditolak|nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $validated, $simpanan) {
            $lockedSimpanan = KoperasiSimpanan::query()
                ->with('user')
                ->whereKey($simpanan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeVisibleUser($request->user(), $lockedSimpanan->user);

            if ($lockedSimpanan->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Simpanan ini sudah diproses sebelumnya.',
                ]);
            }

            $lockedSimpanan->update([
                'status' => $validated['status'],
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'rejected_reason' => $validated['rejected_reason'] ?? null,
            ]);
        });

        return back()->with('success', 'Simpanan berhasil diverifikasi.');
    }

    public function approvePinjaman(Request $request, KoperasiPinjam $pinjaman)
    {
        $validated = $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'rejected_reason' => 'required_if:status,ditolak|nullable|string|max:255',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated, $pinjaman) {
            $lockedPinjaman = KoperasiPinjam::query()
                ->with('user')
                ->whereKey($pinjaman->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeVisibleUser($request->user(), $lockedPinjaman->user);

            if ($lockedPinjaman->status !== 'menunggu_persetujuan') {
                throw ValidationException::withMessages([
                    'status' => 'Pinjaman ini sudah diproses sebelumnya.',
                ]);
            }

            $data = [
                'status' => $validated['status'],
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejected_reason' => $validated['rejected_reason'] ?? null,
            ];

            if ($request->hasFile('proof_file')) {
                $data['proof_path'] = $request->file('proof_file')->store('koperasi/pinjaman', 'local');
            }

            $lockedPinjaman->update($data);
        });

        return back()->with('success', 'Pinjaman berhasil diverifikasi.');
    }

    public function buktiSimpanan(Request $request, KoperasiSimpanan $simpanan)
    {
        $simpanan->loadMissing('user');
        $this->authorizeVisibleUser($request->user(), $simpanan->user);

        return $this->privateProofResponse($simpanan->proof_path);
    }

    public function buktiPinjaman(Request $request, KoperasiPinjam $pinjaman)
    {
        $pinjaman->loadMissing('user');
        $this->authorizeVisibleUser($request->user(), $pinjaman->user);

        return $this->privateProofResponse($pinjaman->proof_path);
    }

    public function buktiAngsuran(Request $request, KoperasiAngsuran $angsuran)
    {
        $angsuran->loadMissing('pinjaman.user');
        $this->authorizeVisibleUser($request->user(), $angsuran->pinjaman?->user);

        return $this->privateProofResponse($angsuran->proof_path);
    }

    public function approveAngsuran(Request $request, KoperasiAngsuran $angsuran)
    {
        $validated = $request->validate([
            'status' => 'required|in:terverifikasi,ditolak',
            'rejected_reason' => 'required_if:status,ditolak|nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $validated, $angsuran) {
            $lockedAngsuran = KoperasiAngsuran::query()
                ->with('pinjaman.user')
                ->whereKey($angsuran->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeVisibleUser($request->user(), $lockedAngsuran->pinjaman?->user);

            if ($lockedAngsuran->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Angsuran ini sudah diproses sebelumnya.',
                ]);
            }

            $pinjaman = KoperasiPinjam::query()
                ->whereKey($lockedAngsuran->koperasi_pinjam_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pinjaman->status !== 'disetujui') {
                throw ValidationException::withMessages([
                    'status' => 'Angsuran hanya bisa diproses untuk pinjaman yang masih aktif.',
                ]);
            }

            $lockedAngsuran->update([
                'status' => $validated['status'],
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'rejected_reason' => $validated['rejected_reason'] ?? null,
            ]);

            if ($validated['status'] === 'terverifikasi') {
                $newRemaining = $pinjaman->remaining_amount - $lockedAngsuran->amount;

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
            return $query->whereHas('rt', function (Builder $rtQuery) use ($actor) {
                $rtQuery->where('rw_id', $this->resolveRwId($actor));
            });
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');

        return $query->where('rt_id', $actor->rt_id);
    }

    private function authorizeVisibleUser(User $actor, ?User $target): void
    {
        abort_unless($target, 404);

        if ($actor->isGlobalOperator()) {
            return;
        }

        if ($actor->isRwOfficial()) {
            abort_unless(
                $target->rt_id && (int) $target->rt()->value('rw_id') === $this->resolveRwId($actor),
                404
            );

            return;
        }

        abort_unless($actor->rt_id && $target->rt_id === $actor->rt_id, 404);
    }

    private function privateProofResponse(?string $path)
    {
        abort_unless($path, 404);

        $disk = Storage::disk('local')->exists($path)
            ? 'local'
            : (Storage::disk('public')->exists($path) ? 'public' : null);

        abort_unless($disk, 404);

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && $user->isGlobalOperator()) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }
}
