<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\KartuKeluarga;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use App\Notifications\WargaTerverifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $domisilis = $this->scopeDomisiliToActor(Warga::menungguDomisili(), $actor)
            ->with(['user.rt', 'rumahDiajukan'])->orderBy('domisili_diajukan_at')->get();

        $antrian = collect($wargas)->map(fn (Warga $warga) => (object) [
            'id' => $warga->id,
            'jenis' => 'akun',
            'warga' => $warga,
            'tanggal' => $warga->created_at,
            'email' => $warga->user?->email,
            'rt' => $warga->user?->rt?->name,
            'label' => $warga->rumahDiajukan?->label
                ?? $warga->rumah_diajukan
                ?? ($warga->user?->onboarding_domisili ? 'Diisi warga setelah verifikasi akun' : 'Belum menentukan rumah'),
        ])
            ->concat(collect($domisilis)->map(fn (Warga $warga) => (object) [
                'id' => $warga->id,
                'jenis' => 'domisili',
                'warga' => $warga,
                'tanggal' => $warga->domisili_diajukan_at ?? $warga->updated_at,
                'email' => $warga->user?->email,
                'rt' => $warga->user?->rt?->name,
                'label' => $warga->rumahDiajukan?->label ?? $warga->alamat_domisili,
            ]))
            ->sortBy(fn ($item) => $item->tanggal?->timestamp ?? 0)
            ->values();

        $pjHouse = session('domisili_rumah_id') ? Rumah::billingVisibleTo($actor)->whereKey(session('domisili_rumah_id'))->needsBillingContact()->first() : null;

        return view('verifikasi_warga.index', compact('antrian', 'wargas', 'domisilis', 'pjHouse'));
    }

    public function showDomisili(Request $request, Warga $warga): View
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeDomisiliVisible($actor, $warga);
        $warga->load(['user.rt.rw', 'rumahDiajukan.penanggungJawab']);

        return view('verifikasi_warga.domisili', compact('warga'));
    }

    public function verifikasiDomisili(Request $request, Warga $warga): RedirectResponse
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeDomisiliVisible($actor, $warga);
        $request->validate(['rumah_id' => ['prohibited'], 'rt_id' => ['prohibited']]);

        $rumah = DB::transaction(function () use ($request, $actor, $warga) {
            $lockedWarga = Warga::whereKey($warga->id)->lockForUpdate()->firstOrFail();
            $user = $lockedWarga->user()->lockForUpdate()->firstOrFail();
            $this->authorizeDomisiliVisible($actor, $lockedWarga);
            $oldValues = ['status_domisili' => $lockedWarga->status_domisili, 'rumah_id' => $user->rumah_id];
            // Also serialize creation of equal addresses within this RT.
            $rt = Rt::whereKey($user->rt_id)->where('is_active', true)
                ->whereHas('rw', fn (Builder $q) => $q->where('is_active', true))->lockForUpdate()->firstOrFail();

            if ($lockedWarga->mode_domisili === 'existing' || $lockedWarga->rumah_diajukan_id) {
                $rumah = Rumah::whereKey($lockedWarga->rumah_diajukan_id)->where('rt_id', $rt->id)
                    ->where('status', 'aktif')->lockForUpdate()->first();
                if (! $rumah) {
                    throw ValidationException::withMessages(['domisili' => 'Rumah yang diajukan sudah tidak tersedia di RT ini. Minta warga memperbaiki pengajuannya.']);
                }
            } else {
                abort_unless($lockedWarga->mode_domisili === 'new' && filled($lockedWarga->alamat_domisili), 422);
                $alamat = trim($lockedWarga->alamat_domisili);
                $rumah = Rumah::where('rt_id', $rt->id)->where('status', 'aktif')
                    ->whereRaw('LOWER(TRIM(alamat)) = ?', [mb_strtolower($alamat)])->lockForUpdate()->first();
                if ($rumah) {
                    throw ValidationException::withMessages(['domisili' => 'Alamat ini sudah menjadi rumah terdaftar sejak pengajuan dikirim. Kembalikan pengajuan agar warga memilih rumah tersebut dan periksa kembali penghuninya.']);
                }
                $rumah = Rumah::create([
                    'kode_rumah' => 'R-'.Str::ulid(), 'alamat' => $alamat,
                    'rt_id' => $rt->id, 'status' => 'aktif',
                ]);
            }

            // Only the officer's approval may turn a proposal into household access.
            $user->rumah_id = $rumah->id;
            $user->rt_id = $rumah->rt_id;
            $user->onboarding_domisili = false;
            $user->save();
            $lockedWarga->update([
                'rumah_diajukan_id' => $rumah->id,
                'status_domisili' => 'disetujui', 'catatan_domisili' => null,
                'domisili_diproses_oleh' => $actor->id, 'domisili_diproses_at' => now(),
            ]);

            if ($lockedWarga->kartu_keluarga_id && $lockedWarga->kartuKeluarga) {
                $kartuKeluarga = $lockedWarga->kartuKeluarga;
                if ($kartuKeluarga->rumah_id && (int) $kartuKeluarga->rumah_id !== (int) $rumah->id) {
                    throw ValidationException::withMessages([
                        'domisili' => 'KK warga sudah terhubung ke rumah lain. Periksa penempatan KK sebelum menyetujui domisili.',
                    ]);
                }
                $kartuKeluarga->update(['rumah_id' => $rumah->id]);
            }

            if ($lockedWarga->ajukan_pj_iuran && ! $rumah->penanggung_jawab_id) {
                $rumah->update(['penanggung_jawab_id' => $user->id]);
                $user->is_penanggung_jawab_rumah = true;
                $user->save();
            }

            $this->auditDomisili($request, $lockedWarga, $oldValues, 'domisili_disetujui', $rumah->id);

            return $rumah;
        }, 3);

        return redirect()->route('verifikasi-warga.index')->with('success', 'Domisili disetujui. Warga kini terhubung ke rumah yang telah diperiksa.')
            ->with('domisili_rumah_id', $rumah->billingIssue() ? $rumah->id : null);
    }

    public function perbaikiDomisili(Request $request, Warga $warga): RedirectResponse
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeDomisiliVisible($actor, $warga);
        $data = $request->validate(['catatan_domisili' => ['required', 'string', 'min:10', 'max:1000']]);

        DB::transaction(function () use ($request, $actor, $warga, $data) {
            $lockedWarga = Warga::whereKey($warga->id)->lockForUpdate()->firstOrFail();
            $user = $lockedWarga->user()->lockForUpdate()->firstOrFail();
            $this->authorizeDomisiliVisible($actor, $lockedWarga);
            $oldValues = ['status_domisili' => $lockedWarga->status_domisili, 'rumah_id' => $user->rumah_id];
            $lockedWarga->update([
                'status_domisili' => 'perlu_perbaikan', 'catatan_domisili' => $data['catatan_domisili'],
                'domisili_diproses_oleh' => $actor->id, 'domisili_diproses_at' => now(),
            ]);
            $this->auditDomisili($request, $lockedWarga, $oldValues, 'domisili_perlu_perbaikan', null);
        }, 3);

        return redirect()->route('verifikasi-warga.index')->with('success', 'Pengajuan dikembalikan. Warga dapat membaca catatan dan mengajukan domisili kembali.');
    }

    private function scopeDomisiliToActor(Builder $query, User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return $query;
        }
        if ($actor->isRwOfficial()) {
            $rwId = $this->resolveRwId($actor);

            return $query->whereHas('user', fn (Builder $user) => $user->inRw($rwId));
        }
        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');

        return $query->whereHas('user', fn (Builder $user) => $user->where('rt_id', $actor->rt_id));
    }

    private function authorizeDomisiliVisible(User $actor, Warga $warga): void
    {
        abort_unless($this->scopeDomisiliToActor(Warga::menungguDomisili(), $actor)->whereKey($warga->id)->exists(), 404);
    }

    private function auditDomisili(Request $request, Warga $warga, array $oldValues, string $event, ?int $rumahId): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id, 'auditable_type' => Warga::class, 'auditable_id' => $warga->id,
            'event' => $event, 'old_values' => $oldValues,
            'new_values' => ['status_domisili' => $warga->status_domisili, 'rumah_id' => $rumahId],
            'notes' => $warga->catatan_domisili, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);
    }

    public function show(Request $request, Warga $warga): View
    {
        $actor = $this->authorizePetugas($request);
        $this->authorizeWargaVisible($actor, $warga);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $warga->load(['user.rt', 'rumahDiajukan.rt', 'kartuKeluarga']);

        $rts = $actor->isGlobalOperator() && $warga->user?->onboarding_domisili
            ? Rt::where('is_active', true)->whereHas('rw', fn (Builder $q) => $q->where('is_active', true))->with('rw:id,name')->orderBy('rw_id')->orderBy('name')->get()
            : collect();

        $perbaikan = AuditLog::where('auditable_type', Warga::class)->where('auditable_id', $warga->id)
            ->where('event', 'pendaftaran_diajukan_ulang')->latest('id')->first();

        $dokumenTersedia = $warga->availableVerificationDocuments();

        return view('verifikasi_warga.show', compact('warga', 'rts', 'perbaikan', 'dokumenTersedia'));
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
            'metode_verifikasi' => ['nullable', 'in:tatap_muka,dokumen'],
            'rt_id' => ['nullable', 'integer'],
        ]);
        $validated['metode_verifikasi'] = $validated['metode_verifikasi'] ?? 'tatap_muka';

        $user = DB::transaction(function () use ($request, $validated, $warga, $actor) {
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

            if ($validated['metode_verifikasi'] === 'dokumen' && ! $lockedWarga->availableVerificationDocuments()) {
                throw ValidationException::withMessages([
                    'metode_verifikasi' => 'Verifikasi dokumen memerlukan minimal satu unggahan KTP atau KK yang tersedia. Jika memeriksa dokumen fisik, gunakan metode tatap muka.',
                ]);
            }

            $user = $lockedWarga->user()->lockForUpdate()->firstOrFail();
            if ($user->onboarding_domisili) {
                $rtId = $user->rt_id ?? ($actor->isGlobalOperator() ? ($validated['rt_id'] ?? null) : $actor->rt_id);
                $rt = Rt::whereKey($rtId)->where('is_active', true)
                    ->whereHas('rw', fn (Builder $q) => $q->where('is_active', true))->first();
                if (! $rt || (isset($validated['rt_id']) && (int) $validated['rt_id'] !== (int) $rt->id)) {
                    throw ValidationException::withMessages(['rt_id' => 'Tetapkan RT aktif yang sesuai wilayah verifikasi warga.']);
                }
                $user->rt_id = $rt->id;

                if ($lockedWarga->status_domisili === 'pending' || $lockedWarga->rumah_diajukan_id || filled($lockedWarga->alamat_domisili)) {
                    $oldDomValues = ['status_domisili' => $lockedWarga->status_domisili, 'rumah_id' => $user->rumah_id];

                    if ($lockedWarga->mode_domisili === 'existing' || $lockedWarga->rumah_diajukan_id) {
                        $rumah = Rumah::whereKey($lockedWarga->rumah_diajukan_id)->where('rt_id', $rt->id)
                            ->where('status', 'aktif')->lockForUpdate()->first();
                    } else {
                        $alamat = trim($lockedWarga->alamat_domisili);
                        $rumah = Rumah::where('rt_id', $rt->id)->where('status', 'aktif')
                            ->whereRaw('LOWER(TRIM(alamat)) = ?', [mb_strtolower($alamat)])->lockForUpdate()->first();
                        if (! $rumah && filled($alamat)) {
                            $rumah = Rumah::create([
                                'kode_rumah' => 'R-'.Str::ulid(),
                                'alamat' => $alamat,
                                'rt_id' => $rt->id,
                                'status' => 'aktif',
                            ]);
                        }
                    }

                    if ($rumah) {
                        $user->rumah_id = $rumah->id;
                        $user->onboarding_domisili = false;

                        $lockedWarga->rumah_diajukan_id = $rumah->id;
                        $lockedWarga->status_domisili = 'disetujui';
                        $lockedWarga->catatan_domisili = null;
                        $lockedWarga->domisili_diproses_oleh = $actor->id;
                        $lockedWarga->domisili_diproses_at = now();

                        if ($lockedWarga->kartu_keluarga_id && $lockedWarga->kartuKeluarga) {
                            $kartuKeluarga = $lockedWarga->kartuKeluarga;
                            if ($kartuKeluarga->rumah_id && (int) $kartuKeluarga->rumah_id !== (int) $rumah->id) {
                                throw ValidationException::withMessages([
                                    'domisili' => 'KK warga sudah terhubung ke rumah lain. Periksa penempatan KK sebelum menyetujui domisili.',
                                ]);
                            }
                            $kartuKeluarga->update(['rumah_id' => $rumah->id]);
                        }

                        // Auto-assign PJ iuran jika warga mengajukan dan rumah belum punya PJ
                        if ($lockedWarga->ajukan_pj_iuran && ! $rumah->penanggung_jawab_id) {
                            $rumah->update(['penanggung_jawab_id' => $user->id]);
                            $user->is_penanggung_jawab_rumah = true;
                        }

                        $this->auditDomisili($request, $lockedWarga, $oldDomValues, 'domisili_disetujui', $rumah->id);
                    }
                }
            }

            $lockedWarga->update([
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => $validated['metode_verifikasi'],
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => null,
            ]);

            $user->update([
                'status_akun' => 'aktif',
            ]);

            return $user;
        });

        $user->notify(new WargaTerverifikasi);

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
            'catatan_verifikasi' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.',
            'catatan_verifikasi.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($request, $validated, $warga, $actor) {
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

            $user = $lockedWarga->user()->lockForUpdate()->firstOrFail();
            $oldValues = ['status_akun' => $user->status_akun, 'status_verifikasi' => $lockedWarga->status_verifikasi];
            $lockedWarga->update([
                'status_verifikasi' => 'ditolak',
                'catatan_verifikasi' => $validated['catatan_verifikasi'],
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
            ]);

            $user->update([
                'status_akun' => 'ditolak',
            ]);
            AuditLog::create([
                'user_id' => $actor->id, 'auditable_type' => Warga::class, 'auditable_id' => $lockedWarga->id,
                'event' => 'pendaftaran_ditolak', 'old_values' => $oldValues,
                'new_values' => ['status_akun' => 'ditolak', 'status_verifikasi' => 'ditolak'],
                'notes' => $validated['catatan_verifikasi'], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('verifikasi-warga.index')
            ->with('success', 'Pengajuan warga ditolak. Warga dapat login untuk membaca alasan, memperbaiki data, dan mengajukan ulang.');
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

        abort_unless($actor && $actor->isAktif() && $actor->canManageWarga(), 403);

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
                $scope->whereHas('user', fn (Builder $user) => $user->inRw($rwId))
                    ->orWhereHas('rumahDiajukan.rt', fn (Builder $rtQuery) => $rtQuery->where('rw_id', $rwId));
            });
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');

        $allowNewRegistrations = $actor->rt()->where('is_active', true)
            ->whereHas('rw', fn (Builder $rw) => $rw->where('is_active', true))->exists();

        return $query->where(function (Builder $scope) use ($actor, $allowNewRegistrations) {
            $scope->whereHas('user', fn (Builder $userQuery) => $userQuery->where('rt_id', $actor->rt_id))
                ->orWhereHas('rumahDiajukan', fn (Builder $rumahQuery) => $rumahQuery->where('rt_id', $actor->rt_id))
                ->orWhere(function (Builder $newRegistration) use ($allowNewRegistrations) {
                    if (! $allowNewRegistrations) {
                        $newRegistration->whereRaw('1 = 0');
                    }
                    $newRegistration->where('status_verifikasi', 'pending')
                        ->whereNull('rumah_diajukan_id')
                        ->whereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('onboarding_domisili', true)->where('status_akun', 'pending_verifikasi')
                            ->whereNull('rt_id')->whereNull('rumah_id')->whereHas('role', fn (Builder $role) => $role->where('name', 'warga')));
                });
        });
    }

    private function authorizeWargaVisible(User $actor, Warga $warga): void
    {
        abort_unless($this->scopeWargaToActor(Warga::query(), $actor)->whereKey($warga->id)->exists(), 404);
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
        $rwId = $actor->assignedRwId();

        if (! $rwId && $actor->isGlobalOperator()) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah RW.');

        return (int) $rwId;
    }
}
