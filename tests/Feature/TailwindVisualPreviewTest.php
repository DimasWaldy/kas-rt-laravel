<?php

use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;

test('representative pages render after Tailwind migration', function (?string $role, string $path, string $preview) {
    // Exercise the real build even while a developer's Vite server is running.
    // No hot file is written/deleted, and the live application's HMR is untouched.
    Vite::useHotFile(storage_path('framework/testing/no-dev-server-'.Str::uuid().'.hot'));
    $this->seed(RoleAndPermissionSeeder::class);
    $rw = Rw::create(['name' => 'RW Preview']);
    $rt = Rt::create(['name' => 'RT Preview', 'rw_id' => $rw->id]);
    $home = Rumah::create(['kode_rumah' => 'DEMO-01', 'rt_id' => $rt->id]);
    $kk = KartuKeluarga::create(['no_kk' => '1234567890123456', 'rumah_id' => $home->id, 'nama_kepala_keluarga' => 'Warga Fiktif']);
    $resident = User::factory()->create([
        'name' => 'Warga Fiktif', 'email' => 'preview@example.test', 'phone' => '080000000000',
        'role_id' => Role::where('name', 'warga')->sole()->id,
        'rumah_id' => $home->id, 'rt_id' => $rt->id, 'is_penanggung_jawab_rumah' => true,
    ]);
    $home->update(['penanggung_jawab_id' => $resident->id]);
    Warga::create([
        'user_id' => $resident->id, 'nama_lengkap' => $resident->name, 'nik' => '1234567890123456',
        'kartu_keluarga_id' => $kk->id, 'status_dalam_kk' => 'kepala_keluarga', 'status_verifikasi' => 'terverifikasi',
    ]);
    Tagihan::create(['user_id' => $resident->id, 'rumah_id' => $home->id, 'rt_id' => $rt->id,
        'bulan' => 10, 'tahun' => 2026, 'total' => 50000, 'status' => 'belum_bayar']);
    if ($role) {
        $actor = $role === 'warga' ? $resident : User::factory()->create([
            'name' => 'Admin Fiktif', 'role_id' => Role::where('name', $role)->sole()->id, 'rt_id' => $rt->id,
        ]);
        $this->actingAs($actor);
    }
    $response = $this->get($path)->assertOk()->assertSee('/build/assets/');

    // Opt-in visual fixtures use only the isolated SQLite test data above.
    // No production routes, accounts or database are added.
    if (getenv('SMART_RW_EXPORT_VISUAL_PREVIEWS') === '1') {
        $target = storage_path('app/private/tailwind-review/'.$preview);
        File::ensureDirectoryExists(dirname($target));
        $html = str_replace('http://localhost', '', $response->getContent());
        File::put($target, $html);
    }
})->with([
    [null, '/', 'index.html'],
    [null, '/login', 'login/index.html'],
    [null, '/register', 'register/index.html'],
    ['admin', '/admin/dashboard', 'admin-dashboard.html'],
    ['admin', '/admin/tagihan', 'tagihan.html'],
    ['admin', '/admin/warga', 'warga.html'],
    ['admin', '/admin/koperasi', 'koperasi.html'],
    ['warga', '/dashboard', 'warga-dashboard.html'],
    ['warga', '/profile', 'profile.html'],
]);
