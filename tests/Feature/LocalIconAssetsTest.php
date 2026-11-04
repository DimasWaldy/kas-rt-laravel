<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('halaman publik tidak memuat font awesome dari cdnjs', function (string $path) {
    $this->get($path)->assertOk()->assertDontSee('cdnjs.cloudflare.com');
})->with(['/', '/login', '/register']);

test('layout aplikasi tidak memuat font awesome dari cdnjs', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee('cdnjs.cloudflare.com');
});
