<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestoreAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $email = (string) env('SEED_ADMIN_EMAIL', '');
        $password = (string) env('SEED_ADMIN_PASSWORD', '');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            $this->command?->error('RestoreAdminSeeder dibatalkan.');
            $this->command?->warn('Isi SEED_ADMIN_EMAIL dan SEED_ADMIN_PASSWORD minimal 12 karakter sebelum menjalankan seeder ini.');

            return;
        }

        User::updateOrCreate(
            ['email' => Str::lower($email)],
            [
                'name' => env('SEED_ADMIN_NAME', 'Admin Smart RW'),
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
                'status_akun' => 'aktif',
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('Admin berhasil dibuat/diperbarui: ' . Str::lower($email));
    }
}
