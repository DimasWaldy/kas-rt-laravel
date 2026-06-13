<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Rt;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(WilayahSeeder::class);
        $this->call(RoleAndPermissionSeeder::class);

        $wargaRole = Role::where('name', 'warga')->first();
        $defaultRt = Rt::where('name', 'RT 01')->first();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $wargaRole?->id,
            'rt_id' => $defaultRt?->id,
        ]);
    }
}
