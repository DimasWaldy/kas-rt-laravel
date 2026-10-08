<?php

namespace App\Http\Controllers;

use App\Models\KartuKeluarga;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use App\Models\Rw;
use App\Notifications\WargaTerverifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiWargaController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $this->authorizePetugas($request);

        $wargas = Warga::query()
            ->with(['user.rt', 'rumahDiajukan.rt'])
            ->where('status_verifikasi', 'pending')
            ->where(fn (Builder $query) => $this->scopeWargaToActor($query, $actor))
            ->orderBy('created_at')
            ->get();

        return view('verifikasi_warga.index', compact('wargas'));
    }

    public function show(Request $request, Warga $warga): View
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeWargaVisible($actor, $warga);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $warga->load(['user.rt', 'rumahDiajukan.rt']);

        return view('verifikasi_warga.show', compact('warga'));
    }

    public function dokumen(Request $request, Warga $warga, string $jenis): StreamedResponse
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeWargaVisible($actor, $warga);

        $path = match ($jenis) {
            'kk' => $warga->dokumen_kk,
            'ktp' => $warga->dokumen_ktp,
            default => null,
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function verifikasi(Request $request, Warga $warga): RedirectResponse
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeWargaVisible($actor, $warga);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $validated = $request->validate([
            'metode_verifikasi' => ['required', 'in:tatap_muka,dokumen'],
        ]);

        $user = DB::transaction(function () use ($validated, $warga, $actor) {
            $lockedWarga = Warga::query()
                ->with(['user.rt', 'rumahDiajukan.rt'])
                ->whereKey($warga->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeWargaVisible($actor, $lockedWarga);

            if ($lockedWarga->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'status_verifikasi' => 'Calon warga ini sudah selesai diproses.',
                ]);
            }

            $lockedWarga->update([
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => $validated['metode_verifikasi'],
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => null,
            ]);

            $user = $lockedWarga->user()->firstOrFail();
            $user->update([
                'status_akun' => 'aktif',
            ]);

            return $user;
        });

        $user->notify(new WargaTerverifikasi());

        return redirect()
            ->route('verifikasi-warga.index')
            ->with('success', 'Warga berhasil diverifikasi dan akunnya telah diaktifkan.');
    }

    public function tolak(Request $request, Warga $warga): RedirectResponse
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeWargaVisible($actor, $warga);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $validated = $request->validate([
            'catatan_verifikasi' => ['required', 'string', 'min:10'],
        ], [
            'catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.',
            'catatan_verifikasi.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($validated, $warga, $actor) {
            $lockedWarga = Warga::query()
                ->with(['user.rt', 'rumahDiajukan.rt'])
                ->whereKey($warga->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeWargaVisible($actor, $lockedWarga);

            if ($lockedWarga->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'status_verifikasi' => 'Calon warga ini sudah selesai diproses.',
                ]);
            }

            $lockedWarga->update([
                'status_verifikasi' => 'ditolak',
                'catatan_verifikasi' => $validated['catatan_verifikasi'],
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
            ]);

            $lockedWarga->user()->update([
                'status_akun' => 'ditolak',
            ]);
        });

        return redirect()
            ->route('verifikasi-warga.index')
            ->with('success', 'Pengajuan warga telah ditolak.');
    }

    public function getKkDiRumah(Request $request, Rumah $rumah): JsonResponse
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeRumahVisible($actor, $rumah);

        $kartuKeluargas = KartuKeluarga::query()
            ->where('rumah_id', $rumah->id)
            ->orderBy('nama_kepala_keluarga')
            ->get(['id', 'no_kk', 'nama_kepala_keluarga'])
            ->map(fn (KartuKeluarga $kartuKeluarga) => [
                'id' => $kartuKeluarga->id,
                'no_kk' => $kartuKeluarga->no_kk,
                'nama_kepala' => $kartuKeluarga->nama_kepala_keluarga,
            ])
            ->values();

        return response()->json($kartuKeluargas);
    }

    private function authorizePetugas(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor && $actor->canManageWarga(), 403);

        return $actor;
    }

    private function scopeWargaToActor(Builder $query, User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return $query;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $this->resolveRwId($actor);

            return $query->where(function (Builder $scope) use ($rwId) {
                $scope->whereHas('user.rt', fn (Builder $rtQuery) => $rtQuery->where('rw_id', $rwId))
                    ->orWhereHas('rumahDiajukan.rt', fn (Builder $rtQuery) => $rtQuery->where('rw_id', $rwId));
            });
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');

        return $query->where(function (Builder $scope) use ($actor) {
            $scope->whereHas('user', fn (Builder $userQuery) => $userQuery->where('rt_id', $actor->rt_id))
                ->orWhereHas('rumahDiajukan', fn (Builder $rumahQuery) => $rumahQuery->where('rt_id', $actor->rt_id));
        });
    }

    private function authorizeWargaVisible(User $actor, Warga $warga): void
    {
        if ($actor->isGlobalOperator()) {
            return;
        }

        $warga->loadMissing(['user.rt', 'rumahDiajukan.rt']);

        if ($actor->isRwOfficial()) {
            $rwId = $this->resolveRwId($actor);
            $userRwId = $warga->user?->rt?->rw_id;
            $rumahDiajukanRwId = $warga->rumahDiajukan?->rt?->rw_id;

            abort_unless($userRwId === $rwId || $rumahDiajukanRwId === $rwId, 404);

            return;
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');

        $userRtId = $warga->user?->rt_id;
        $rumahDiajukanRtId = $warga->rumahDiajukan?->rt_id;

        abort_unless($userRtId === $actor->rt_id || $rumahDiajukanRtId === $actor->rt_id, 404);
    }

    private function authorizeRumahVisible(User $actor, Rumah $rumah): void
    {
        if ($actor->isGlobalOperator()) {
            return;
        }

        if ($actor->isRwOfficial()) {
            abort_unless((int) $rumah->rt()->value('rw_id') === $this->resolveRwId($actor), 404);

            return;
        }

        abort_unless($actor->rt_id && $rumah->rt_id === $actor->rt_id, 404);
    }

    private function resolveRwId(User $actor): int
    {
        $rwId = $actor->rt()->value('rw_id');

        if (! $rwId && ($actor->isRwOfficial() || $actor->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah RW.');

        return (int) $rwId;
    }
}
