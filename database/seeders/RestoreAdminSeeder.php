<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Rt;
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

        $rtName = (string) env('SEED_ADMIN_RT', '');
        $rtAssignment = [];
        if ($rtName !== '') {
            $matches = Rt::where('name', $rtName)->where('is_active', true)->limit(2)->get();
            if ($matches->count() === 1) {
                $rtAssignment['rt_id'] = $matches->first()->id;
            } else {
                $this->command?->warn('SEED_ADMIN_RT tidak ditemukan atau ambigu di antara RT aktif. RT admin tidak diubah.');
            }
        } else {
            $this->command?->warn('SEED_ADMIN_RT belum diisi. Modul Aset dan Peminjaman Aset akan 403 sampai admin dihubungkan ke RT.');
        }

        User::updateOrCreate(
            ['email' => Str::lower($email)],
            [
                'name' => env('SEED_ADMIN_NAME', 'Admin Smart RW'),
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
                'status_akun' => 'aktif',
                'email_verified_at' => now(),
                ...$rtAssignment,
            ]
        );

        $this->command?->info('Admin berhasil dibuat/diperbarui: '.Str::lower($email));
    }
}
