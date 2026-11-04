<?php

use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RestoreAdminSeeder;

test('admin awal hanya dihubungkan ke satu rt aktif yang cocok', function (string $rtName, bool $active, bool $duplicate, bool $assigned) {
    $rw = Rw::create(['name' => 'RW Admin', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01', 'is_active' => $active]);
    if ($duplicate) {
        $otherRw = Rw::create(['name' => 'RW Lain', 'is_active' => true]);
        Rt::create(['rw_id' => $otherRw->id, 'name' => 'RT 01', 'is_active' => true]);
    }

    $values = ['SEED_ADMIN_EMAIL' => 'admin-test@example.test', 'SEED_ADMIN_PASSWORD' => 'Testing-admin-strong-123', 'SEED_ADMIN_RT' => $rtName];
    $original = [];
    foreach ($values as $key => $value) {
        $original[$key] = [getenv($key), $_ENV[$key] ?? null, array_key_exists($key, $_ENV)];
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
    try {
        $this->seed(RestoreAdminSeeder::class);
    } finally {
        foreach ($original as $key => [$processValue, $envValue, $exists]) {
            putenv($processValue === false ? $key : "{$key}={$processValue}");
            if ($exists) {
                $_ENV[$key] = $envValue;
            } else {
                unset($_ENV[$key]);
            }
        }
    }

    $admin = User::where('email', 'admin-test@example.test')->sole();
    expect($admin->rt_id)->toBe($assigned ? $rt->id : null);
    foreach (['aset.index', 'peminjaman-aset.index', 'peminjaman-aset.create'] as $routeName) {
        $response = $this->actingAs($admin)->get(route($routeName));
        if ($assigned) {
            $response->assertOk();
        } else {
            $response->assertForbidden()->assertSee('Akun admin belum terhubung ke RT.');
        }
    }
})->with([
    'valid' => ['RT 01', true, false, true],
    'kosong' => ['', true, false, false],
    'tidak ditemukan' => ['RT 99', true, false, false],
    'nonaktif' => ['RT 01', false, false, false],
    'ambigu' => ['RT 01', true, true, false],
]);
