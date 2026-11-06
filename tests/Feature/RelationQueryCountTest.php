<?php

use App\Models\KoperasiMember;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\DB;

test('daftar admin tidak melakukan lazy load rt per warga', function (string $routeName) {
    $this->seed(RoleAndPermissionSeeder::class);
    $rw = Rw::create(['name' => 'RW Query', 'is_active' => true]);
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);
    $wargaRole = Role::where('name', 'warga')->value('id');

    for ($rtNumber = 1; $rtNumber <= 3; $rtNumber++) {
        $rt = Rt::create(['rw_id' => $rw->id, 'name' => "RT Query {$rtNumber}", 'is_active' => true]);
        foreach (User::factory()->count(4)->create(['role_id' => $wargaRole, 'rt_id' => $rt->id]) as $user) {
            Warga::create([
                'user_id' => $user->id,
                'nama_lengkap' => $user->name,
                'status_dalam_kk' => 'kepala_keluarga',
            ]);
            KoperasiMember::create([
                'user_id' => $user->id,
                'member_number' => 'QUERY-'.$user->id,
                'joined_at' => now(),
                'status' => 'pending',
            ]);
        }
    }

    $this->actingAs($admin);
    DB::enableQueryLog();
    DB::flushQueryLog();
    try {
        $response = $this->get(route($routeName));
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    $response->assertOk();
    $lazyRtQueries = collect($queries)->filter(function (array $query): bool {
        $sql = str_replace(['"', '`'], '', strtolower($query['query']));

        return (bool) preg_match('/select .* from rts where rts\.id = \? limit 1/', $sql);
    });
    expect($lazyRtQueries->count())->toBeLessThanOrEqual(1);
    if ($routeName !== 'admin.koperasi.index') {
        $response->assertSee('RT Query 1')->assertSee('RT Query 2')->assertSee('RT Query 3');
    }
})->with(['tagihan.admin', 'tagihan.create', 'admin.warga.index', 'admin.koperasi.index']);
