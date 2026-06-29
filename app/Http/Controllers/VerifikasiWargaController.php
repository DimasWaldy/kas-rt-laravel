<?php

namespace App\Http\Controllers;

use App\Models\KartuKeluarga;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use App\Notifications\WargaTerverifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiWargaController extends Controller
{
    public function menunggu(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->role_name === 'warga', 403);

        if ($user->isAktif()) {
            return redirect()->route('dashboard');
        }

        $user->load('warga');

        return view('verifikasi.menunggu', [
            'user' => $user,
            'warga' => $user->warga,
        ]);
    }

    public function index(Request $request): View
    {
        $actor = $this->authorizePetugas($request);

        $wargas = Warga::query()
            ->with(['user', 'rumahDiajukan'])
            ->where('status_verifikasi', 'pending')
            ->when(! $actor->canAccessAllRts(), function ($query) use ($actor) {
                $query->where(function ($scope) use ($actor) {
                    $scope->whereHas('user', fn ($users) => $users->where('rt_id', $actor->rt_id))
                        ->orWhereHas('rumahDiajukan', fn ($rumahs) => $rumahs->where('rt_id', $actor->rt_id));
                });
            })
            ->orderBy('created_at')
            ->get();

        $rumahs = Rumah::query()
            ->visibleTo($actor)
            ->where('status', 'aktif')
            ->orderBy('kode_rumah')
            ->get();

        return view('verifikasi_warga.index', compact('wargas', 'rumahs'));
    }

    public function show(Request $request, Warga $warga): View
    {
        $actor = $this->authorizePetugas($request, $warga);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $warga->load(['user', 'rumahDiajukan']);

        $rumahs = Rumah::query()
            ->visibleTo($actor)
            ->where('status', 'aktif')
            ->orderBy('kode_rumah')
            ->get();

        return view('verifikasi_warga.show', compact('warga', 'rumahs'));
    }

    public function dokumen(Request $request, Warga $warga, string $jenis): StreamedResponse
    {
        $this->authorizePetugas($request, $warga);

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
        $actor = $this->authorizePetugas($request, $warga);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $validated = $request->validate([
            'rumah_id' => [
                'required',
                'integer',
                Rule::exists('rumahs', 'id')->where(
                    fn ($query) => $actor->canAccessAllRts()
                        ? $query
                        : $query->where('rt_id', $actor->rt_id)
                ),
            ],
            'status_dalam_kk' => ['required', 'in:kepala_keluarga,anggota'],
            'no_kk' => [
                'required_if:status_dalam_kk,kepala_keluarga',
                'nullable',
                'digits:16',
                Rule::unique('kartu_keluargas', 'no_kk'),
            ],
            'kartu_keluarga_id' => [
                'required_if:status_dalam_kk,anggota',
                'nullable',
                'integer',
                'exists:kartu_keluargas,id',
            ],
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('wargas', 'nik')->ignore($warga->id),
            ],
            'metode_verifikasi' => ['required', 'in:tatap_muka,dokumen'],
        ], [
            'rumah_id.exists' => 'Rumah harus berada di wilayah yang dapat Anda kelola.',
            'no_kk.required_if' => 'Nomor KK wajib diisi untuk kepala keluarga.',
            'no_kk.digits' => 'Nomor KK harus berisi 16 digit angka.',
            'no_kk.unique' => 'Nomor KK tersebut sudah terdaftar.',
            'kartu_keluarga_id.required_if' => 'Pilih Kartu Keluarga untuk anggota keluarga.',
            'nik.digits' => 'NIK harus berisi 16 digit angka.',
            'nik.unique' => 'NIK tersebut sudah terdaftar.',
        ]);

        $rumah = Rumah::visibleTo($actor)->findOrFail($validated['rumah_id']);

        if ($validated['status_dalam_kk'] === 'anggota') {
            $kartuKeluarga = KartuKeluarga::query()
                ->whereKey($validated['kartu_keluarga_id'])
                ->where('rumah_id', $rumah->id)
                ->first();

            if (! $kartuKeluarga) {
                throw ValidationException::withMessages([
                    'kartu_keluarga_id' => 'Kartu Keluarga harus terdaftar pada rumah yang dipilih.',
                ]);
            }
        }

        $user = DB::transaction(function () use ($validated, $warga, $rumah, $actor) {
            $lockedWarga = Warga::query()
                ->whereKey($warga->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWarga->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'status_verifikasi' => 'Calon warga ini sudah selesai diproses.',
                ]);
            }

            if ($validated['status_dalam_kk'] === 'kepala_keluarga') {
                $kartuKeluarga = KartuKeluarga::create([
                    'no_kk' => $validated['no_kk'],
                    'rumah_id' => $rumah->id,
                    'nama_kepala_keluarga' => $lockedWarga->nama_lengkap,
                ]);
            } else {
                $kartuKeluarga = KartuKeluarga::query()
                    ->whereKey($validated['kartu_keluarga_id'])
                    ->where('rumah_id', $rumah->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $lockedWarga->update([
                'kartu_keluarga_id' => $kartuKeluarga->id,
                'nik' => $validated['nik'],
                'status_dalam_kk' => $validated['status_dalam_kk'],
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => $validated['metode_verifikasi'],
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => null,
            ]);

            $user = $lockedWarga->user()->firstOrFail();
            $user->update([
                'rumah_id' => $rumah->id,
                'rt_id' => $rumah->rt_id,
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
        $actor = $this->authorizePetugas($request, $warga);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $validated = $request->validate([
            'catatan_verifikasi' => ['required', 'string', 'min:10'],
        ], [
            'catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.',
            'catatan_verifikasi.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($validated, $warga, $actor) {
            $lockedWarga = Warga::query()
                ->whereKey($warga->id)
                ->lockForUpdate()
                ->firstOrFail();

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

        abort_unless($rumah->isVisibleTo($actor), 404);

        $kartuKeluargas = KartuKeluarga::query()
            ->where('rumah_id', $rumah->id)
            ->orderBy('nama_kepala_keluarga')
            ->get(['id', 'no_kk', 'nama_kepala_keluarga']);

        return response()->json($kartuKeluargas);
    }

    private function authorizePetugas(Request $request, ?Warga $warga = null): User
    {
        $actor = $request->user();

        abort_unless($actor && $actor->canManageWarga(), 403);

        if ($warga && ! $actor->canAccessAllRts()) {
            $warga->loadMissing(['user', 'rumahDiajukan']);

            $rtId = $warga->user?->rt_id ?? $warga->rumahDiajukan?->rt_id;

            abort_unless($rtId && $rtId === $actor->rt_id, 404);
        }

        return $actor;
    }
}
