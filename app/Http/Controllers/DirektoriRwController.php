<?php

namespace App\Http\Controllers;

use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirektoriRwController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $rtIds = $this->visibleRtIds($actor);
        $selectedRtId = $request->integer('rt_id') ?: null;
        $statusProfil = $request->string('status_profil')->toString();
        $search = trim($request->string('search')->toString());

        if ($selectedRtId && ! $rtIds->contains($selectedRtId)) {
            abort(404);
        }

        $rts = Rt::withCount([
                'users as warga_count' => fn (Builder $query) => $query->whereRelation('role', 'name', 'warga'),
                'rumahs as rumah_count',
            ])
            ->with(['rw'])
            ->whereIn('id', $rtIds)
            ->orderBy('name')
            ->get();

        $usersQuery = User::with(['role', 'rt.rw', 'rumah', 'warga.kartuKeluarga'])
            ->whereIn('rt_id', $rtIds)
            ->whereRelation('role', 'name', 'warga');

        if ($selectedRtId) {
            $usersQuery->where('rt_id', $selectedRtId);
        }

        if ($statusProfil === 'belum_lengkap') {
            $usersQuery->where(function (Builder $query) {
                $query->whereNull('phone')
                    ->orWhereNull('rumah_id')
                    ->orWhereNull('rt_id')
                    ->orWhereDoesntHave('warga', fn (Builder $warga) => $warga
                        ->whereNotNull('nik')
                        ->whereNotNull('status_dalam_kk')
                        ->whereHas('kartuKeluarga', fn (Builder $kk) => $kk->whereNotNull('no_kk')));
            });
        }

        if ($search !== '') {
            $usersQuery->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('rt', fn (Builder $rt) => $rt->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('rumah', fn (Builder $rumah) => $rumah
                        ->where('kode_rumah', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%"))
                    ->orWhereHas('warga', fn (Builder $warga) => $warga
                        ->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhereHas('kartuKeluarga', fn (Builder $kk) => $kk->where('no_kk', 'like', "%{$search}%")));
            });
        }

        $wargas = $usersQuery
            ->orderBy('rt_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $statistik = [
            'total_rt' => $rts->count(),
            'total_rumah' => Rumah::whereIn('rt_id', $rtIds)->count(),
            'total_warga' => User::whereIn('rt_id', $rtIds)->whereRelation('role', 'name', 'warga')->count(),
            'profil_belum_lengkap' => User::with(['warga.kartuKeluarga'])
                ->whereIn('rt_id', $rtIds)
                ->whereRelation('role', 'name', 'warga')
                ->get()
                ->filter(fn (User $user) => $user->profile_status !== 'Lengkap')
                ->count(),
            'kepala_keluarga' => User::whereIn('rt_id', $rtIds)
                ->whereRelation('role', 'name', 'warga')
                ->whereHas('warga', fn (Builder $warga) => $warga->where('status_dalam_kk', 'kepala_keluarga'))
                ->count(),
        ];

        return view('direktori_rw.index', compact(
            'rts',
            'wargas',
            'statistik',
            'selectedRtId',
            'statusProfil',
            'search'
        ));
    }

    public function showRt(Request $request, Rt $rt): View
    {
        $actor = $request->user();
        abort_unless($this->visibleRtIds($actor)->contains($rt->id), 404);

        $rt->load('rw');

        $pengurus = User::with('role')
            ->where('rt_id', $rt->id)
            ->whereHas('role', fn (Builder $role) => $role->whereIn('name', [
                'ketua_rt',
                'sekretaris_rt',
                'bendahara_rt',
                'sekretaris',
                'bendahara',
            ]))
            ->orderBy('name')
            ->get();

        $rumahs = Rumah::withCount('warga')
            ->where('rt_id', $rt->id)
            ->orderBy('kode_rumah')
            ->get();

        $wargas = User::with(['rumah', 'warga.kartuKeluarga'])
            ->where('rt_id', $rt->id)
            ->whereRelation('role', 'name', 'warga')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $statistik = [
            'total_rumah' => $rumahs->count(),
            'total_warga' => $wargas->total(),
            'profil_lengkap' => User::with(['warga.kartuKeluarga'])
                ->where('rt_id', $rt->id)
                ->whereRelation('role', 'name', 'warga')
                ->get()
                ->filter(fn (User $user) => $user->profile_status === 'Lengkap')
                ->count(),
            'kepala_keluarga' => User::where('rt_id', $rt->id)
                ->whereRelation('role', 'name', 'warga')
                ->whereHas('warga', fn (Builder $warga) => $warga->where('status_dalam_kk', 'kepala_keluarga'))
                ->count(),
        ];

        return view('direktori_rw.show', compact('rt', 'pengurus', 'rumahs', 'wargas', 'statistik'));
    }

    private function visibleRtIds(User $actor)
    {
        if ($actor->canAccessAllRts()) {
            $rwId = $actor->rt()->value('rw_id')
                ?: Rw::where('is_active', true)->orderBy('id')->value('id');

            abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

            return Rt::where('rw_id', $rwId)->pluck('id');
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');

        return collect([(int) $actor->rt_id]);
    }
}
