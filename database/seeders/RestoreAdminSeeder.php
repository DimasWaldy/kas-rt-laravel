<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RestoreAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $sekretarisRole = Role::where('name', 'sekretaris')->firstOrFail();
        $bendaharaRole = Role::where('name', 'bendahara')->firstOrFail();

        $users = [
            [
                'role' => $adminRole,
                'name' => 'Admin RT',
                'email' => 'admin@kas-rt.test',
            ],
            [
                'role' => $sekretarisRole,
                'name' => 'Sekretaris RT',
                'email' => 'sekretaris@kas-rt.test',
            ],
            [
                'role' => $bendaharaRole,
                'name' => 'Bendahara RT',
                'email' => 'bendahara@kas-rt.test',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $data['role']->id,
                    'is_kepala_keluarga' => false,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('Roles restored: admin, warga, sekretaris, bendahara');
        $this->command?->info('Users restored:');
        $this->command?->info('- Admin      : admin@kas-rt.test (password)');
        $this->command?->info('- Sekretaris : sekretaris@kas-rt.test (password)');
        $this->command?->info('- Bendahara  : bendahara@kas-rt.test (password)');
    }
}
