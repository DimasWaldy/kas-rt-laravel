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
        ];

        $permissionModels = collect($permissions)->mapWithKeys(function (string $description, string $name) {
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );

            return [$name => $permission];
        });

        $roles = [
            'admin' => [
                'description' => 'Administrator RT',
                'permissions' => array_keys($permissions),
            ],
            'bendahara' => [
                'description' => 'Bendahara RT',
                'permissions' => [
                    'view-dashboard',
                    'view-finance',
                    'manage-finance',
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
