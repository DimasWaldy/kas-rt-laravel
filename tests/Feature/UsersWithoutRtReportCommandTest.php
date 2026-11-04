<?php

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Support\Facades\DB;

test('command laporan user tanpa rt menampilkan user null rt dan menyembunyikan data sensitif', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);

    $userTanpaRt = User::factory()->create([
        'name' => 'User Tanpa RT',
        'email' => 'tanpa-rt@example.test',
        'role_id' => $role->id,
        'rt_id' => null,
        'phone' => '081299988877',
    ]);

    $rw = Rw::create(['name' => 'RW Command']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Command']);
    $rumah = Rumah::create([
        'kode_rumah' => 'CMD-001',
        'alamat' => 'Alamat command tidak ditampilkan',
        'rt_id' => $rt->id,
        'status' => 'aktif',
    ]);

    Warga::create([
        'user_id' => $userTanpaRt->id,
        'nama_lengkap' => 'User Tanpa RT',
        'nik' => '3273010101010001',
        'rumah_diajukan_id' => $rumah->id,
        'status_verifikasi' => 'pending',
    ]);

    User::factory()->create([
        'name' => 'User Punya RT',
        'email' => 'punya-rt@example.test',
        'role_id' => $role->id,
        'rt_id' => $rt->id,
    ]);

    $this->artisan('users:report-without-rt')
        ->expectsOutputToContain('Total user tanpa RT: 1')
        ->expectsTable(
            ['id', 'name', 'email', 'role', 'rumah_id', 'rumah_diajukan_id'],
            [[$userTanpaRt->id, 'User Tanpa RT', 'tanpa-rt@example.test', 'warga', '-', $rumah->id]]
        )
        ->doesntExpectOutputToContain('User Punya RT')
        ->doesntExpectOutputToContain('punya-rt@example.test')
        ->doesntExpectOutputToContain('081299988877')
        ->doesntExpectOutputToContain('3273010101010001')
        ->assertSuccessful();
});

test('command laporan user tanpa rt menghormati opsi limit', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);

    $firstUser = User::factory()->create([
        'name' => 'User Null RT Satu',
        'email' => 'null-rt-1@example.test',
        'role_id' => $role->id,
        'rt_id' => null,
    ]);

    User::factory()->create([
        'name' => 'User Null RT Dua',
        'email' => 'null-rt-2@example.test',
        'role_id' => $role->id,
        'rt_id' => null,
    ]);

    $this->artisan('users:report-without-rt', ['--limit' => 1])
        ->expectsOutputToContain('Total user tanpa RT: 2')
        ->expectsTable(
            ['id', 'name', 'email', 'role', 'rumah_id', 'rumah_diajukan_id'],
            [[$firstUser->id, 'User Null RT Satu', 'null-rt-1@example.test', 'warga', '-', '-']]
        )
        ->expectsOutputToContain('Menampilkan 1 dari 2 user.')
        ->doesntExpectOutputToContain('User Null RT Dua')
        ->assertSuccessful();
});

test('command laporan user tanpa rt membatasi detail default menjadi 50 baris', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $users = User::factory()->count(51)->create([
        'role_id' => $role->id,
        'rt_id' => null,
    ]);

    $rows = $users->sortBy('id')->take(50)->map(fn (User $user): array => [
        $user->id, $user->name, $user->email, 'warga', '-', '-',
    ])->values()->all();

    $this->artisan('users:report-without-rt')
        ->expectsOutput('Total user tanpa RT: 51')
        ->expectsTable(['id', 'name', 'email', 'role', 'rumah_id', 'rumah_diajukan_id'], $rows)
        ->expectsOutput('Menampilkan 50 dari 51 user. Gunakan --limit= untuk mengubah batas.')
        ->assertSuccessful();
});

test('command laporan user tanpa rt tidak mengubah data users', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);

    $user = User::factory()->create([
        'name' => 'User Read Only',
        'email' => 'read-only@example.test',
        'role_id' => $role->id,
        'rt_id' => null,
    ]);

    $before = DB::table('users')->orderBy('id')->get()->toJson();

    $this->artisan('users:report-without-rt')->assertSuccessful();

    $after = DB::table('users')->orderBy('id')->get()->toJson();

    expect($after)->toBe($before)
        ->and($user->fresh()->rt_id)->toBeNull();
});

test('command laporan user tanpa rt menampilkan pesan jika semua user sudah punya rt', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $rw = Rw::create(['name' => 'RW Semua Aman']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Semua Aman']);

    User::factory()->create([
        'role_id' => $role->id,
        'rt_id' => $rt->id,
    ]);

    $this->artisan('users:report-without-rt')
        ->expectsOutput('Semua user sudah punya RT.')
        ->assertSuccessful();
});
