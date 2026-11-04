<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('petugas posyandu memiliki seluruh permission operasional', function () {
    $role = Role::where('name', 'petugas_posyandu')->firstOrFail();
    $petugas = User::factory()->create(['role_id' => $role->id]);

    expect($petugas->hasPermission('view-posyandu'))->toBeTrue()
        ->and($petugas->hasPermission('manage-posyandu'))->toBeTrue()
        ->and($petugas->hasPermission('record-posyandu'))->toBeTrue();
});

test('pengurus memperoleh akses posyandu sesuai tanggung jawab', function () {
    $userForRole = function (string $role): User {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
        ]);
    };

    $ketuaRw = $userForRole('ketua_rw');
    $sekretarisRw = $userForRole('sekretaris_rw');
    $ketuaRt = $userForRole('ketua_rt');
    $sekretarisRt = $userForRole('sekretaris_rt');

    expect($ketuaRw->hasPermission('view-posyandu'))->toBeTrue()
        ->and($ketuaRw->hasPermission('manage-posyandu'))->toBeFalse()
        ->and($ketuaRt->hasPermission('view-posyandu'))->toBeTrue()
        ->and($ketuaRt->hasPermission('record-posyandu'))->toBeFalse()
        ->and($sekretarisRw->hasPermission('manage-posyandu'))->toBeTrue()
        ->and($sekretarisRw->hasPermission('record-posyandu'))->toBeFalse()
        ->and($sekretarisRt->hasPermission('manage-posyandu'))->toBeTrue()
        ->and($sekretarisRt->hasPermission('record-posyandu'))->toBeFalse();
});

test('warga hanya dapat melihat data posyandu dan bendahara tidak mendapat akses kesehatan', function () {
    $warga = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->value('id'),
    ]);
    $bendaharaRt = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rt')->value('id'),
    ]);
    $bendaharaRw = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rw')->value('id'),
    ]);

    expect($warga->hasPermission('view-posyandu'))->toBeTrue()
        ->and($warga->hasPermission('manage-posyandu'))->toBeFalse()
        ->and($warga->hasPermission('record-posyandu'))->toBeFalse()
        ->and($bendaharaRt->hasPermission('view-posyandu'))->toBeFalse()
        ->and($bendaharaRw->hasPermission('view-posyandu'))->toBeFalse();
});
