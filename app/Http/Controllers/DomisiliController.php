<?php

namespace App\Http\Controllers;

use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DomisiliController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        if ($user->isAktif() && $user->rumah_id && $user->warga?->status_domisili === 'disetujui') {
            return redirect()->route('profile.edit')->with('success', 'Domisili telah disetujui pengurus. Silakan lengkapi profil Anda.');
        }
        $this->authorizeInitialSetup($user);
        $rt = $this->activeRt($user)->firstOrFail();
        $warga = $user->warga;
        $warga->load('rumahDiajukan');
        $rumahs = $warga->status_domisili === 'pending' ? collect() : Rumah::where('rt_id', $rt->id)->where('status', 'aktif')
            ->orderBy('kode_rumah')->get(['id', 'kode_rumah', 'alamat']);

        return response()->view('profile.domisili', compact('user', 'warga', 'rt', 'rumahs'))
            ->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        $this->authorizeInitialSetup($request->user());
        $data = $request->validate([
            'mode' => ['required', 'in:existing,new'],
            'rumah_id' => ['required_if:mode,existing', 'prohibited_unless:mode,existing', 'nullable', 'integer'],
            'alamat' => ['required_if:mode,new', 'prohibited_unless:mode,new', 'nullable', 'string', 'min:5', 'max:500'],
            'rt_id' => ['prohibited'],
            'is_penanggung_jawab_rumah' => ['prohibited'],
            'penanggung_jawab_id' => ['prohibited'],
        ], [
            'rumah_id.required_if' => 'Pilih rumah yang sudah terdaftar.',
            'alamat.required_if' => 'Isi alamat lengkap rumah Anda.',
            'rt_id.prohibited' => 'RT mengikuti hasil verifikasi pengurus dan tidak dapat diganti sendiri.',
        ]);

        DB::transaction(function () use ($request, $data) {
            // Match the review lock order: resident record, account, then RT/house.
            $warga = Warga::where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $user->setRelation('warga', $warga);
            $this->authorizeInitialSetup($user);
            if ($warga->status_domisili === 'pending') {
                throw ValidationException::withMessages(['domisili' => 'Pengajuan domisili sedang diperiksa. Tunggu hasil dari pengurus.']);
            }
            $rt = $this->activeRt($user)->firstOrFail();
            if ($data['mode'] === 'existing') {
                $rumah = Rumah::whereKey($data['rumah_id'])->where('rt_id', $rt->id)
                    ->where('status', 'aktif')->firstOrFail();
                $alamat = $rumah->alamat;
            } else {
                $alamat = trim($data['alamat']);
                $rumah = Rumah::where('rt_id', $rt->id)->where('status', 'aktif')
                    ->whereRaw('LOWER(TRIM(alamat)) = ?', [mb_strtolower($alamat)])->first();
            }

            // A proposal never grants household access, including an address match.
            $warga->update([
                'rumah_diajukan_id' => $rumah?->id,
                'mode_domisili' => $data['mode'],
                'alamat_domisili' => $alamat,
                'status_domisili' => 'pending',
                'catatan_domisili' => null,
                'domisili_diajukan_at' => now(),
                'domisili_diproses_oleh' => null,
                'domisili_diproses_at' => null,
            ]);
        }, 3);

        return redirect()->route('profile.edit')->with('success', 'Pengajuan domisili terkirim. Tunggu persetujuan pengurus sebelum mengakses layanan dan data rumah.');
    }

    private function authorizeInitialSetup(User $user): void
    {
        abort_unless($user->role_name === 'warga' && $user->onboarding_domisili
            && $user->isAktif() && ! $user->rumah_id
            && $user->rt_id && $user->warga?->status_verifikasi === 'terverifikasi'
            && ! $user->warga?->kartu_keluarga_id, 403, 'Pengisian domisili awal tidak tersedia. Hubungi pengurus untuk perubahan domisili.');
    }

    private function activeRt(User $user)
    {
        return Rt::whereKey($user->rt_id)->where('is_active', true)
            ->whereHas('rw', fn ($q) => $q->where('is_active', true))->with('rw:id,name');
    }
}
