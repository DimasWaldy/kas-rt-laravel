<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'admin-only' => 'Akses khusus admin utama',
            'manage-finance' => 'Mengelola kas, iuran, dan tagihan',
            'manage-warga' => 'Mengelola data warga',
            'manage-pengaduan' => 'Mengelola tanggapan pengaduan warga',
            'view-dashboard' => 'Melihat dashboard aplikasi',
            'view-finance' => 'Melihat laporan kas dan tagihan',
            'submit-payment' => 'Mengajukan pembayaran tagihan',
            'submit-pengaduan' => 'Membuat pengaduan warga',
            'verify-payment' => 'Memverifikasi bukti pembayaran tagihan warga',
            'export-finance' => 'Mengekspor laporan keuangan',
            'manage-wilayah' => 'Mengelola data RW dan RT',
            'view-wilayah' => 'Melihat daftar RW dan RT',
        ];

        $permissionModels = collect($permissions)->mapWithKeys(function (string $description, string $name) {
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );

            return [$name => $permission];
        });

        $roles = [
            'super_admin' => [
                'description' => 'Developer/operator sistem, akses penuh',
                'permissions' => array_keys($permissions),
            ],
            'admin' => [
                'description' => 'Administrator RT',
                'permissions' => array_keys($permissions),
            ],
            'ketua_rw' => [
                'description' => 'Ketua RW, monitoring lintas RT',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'view-finance',
                    'manage-pengaduan',
                ],
            ],
            'sekretaris_rw' => [
                'description' => 'Sekretaris RW, administrasi level RW',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'manage-pengaduan',
                    'manage-warga',
                ],
            ],
            'bendahara_rw' => [
                'description' => 'Bendahara RW, rekap keuangan lintas RT',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'view-finance',
                    'export-finance',
                ],
            ],
            'ketua_rt' => [
                'description' => 'Ketua RT, approve surat dan pengaduan RT',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'manage-pengaduan',
                    'view-finance',
                ],
            ],
            'bendahara_rt' => [
                'description' => 'Bendahara RT, pengelola keuangan RT',
                'permissions' => [
                    'view-dashboard',
                    'view-finance',
                    'manage-finance',
                    'verify-payment',
                    'export-finance',
                    'submit-pengaduan',
                ],
            ],
            'sekretaris_rt' => [
                'description' => 'Sekretaris RT, administrasi level RT',
                'permissions' => [
                    'view-dashboard',
                    'manage-warga',
                    'manage-pengaduan',
                    'submit-pengaduan',
                ],
            ],
            'bendahara' => [
                'description' => 'Bendahara RT',
                'permissions' => [
                    'view-dashboard',
                    'view-finance',
                    'manage-finance',
                    'verify-payment',
                    'export-finance',
                    'submit-pengaduan',
                ],
            ],
            'sekretaris' => [
                'description' => 'Sekretaris RT',
                'permissions' => [
                    'view-dashboard',
                    'manage-warga',
                    'manage-pengaduan',
                    'submit-pengaduan',
                ],
            ],
            'warga' => [
                'description' => 'Warga RT',
                'permissions' => [
                    'view-dashboard',
                    'view-finance',
                    'submit-payment',
                    'submit-pengaduan',
                ],
            ],
        ];

        foreach ($roles as $name => $data) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                ['description' => $data['description']]
            );

            $role->permissions()->sync(
                collect($data['permissions'])
                    ->map(fn(string $permission) => $permissionModels[$permission]->id)
                    ->all()
            );
        }
    }
}
