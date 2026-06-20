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
            'submit-surat' => 'Mengajukan surat administrasi',
            'view-surat' => 'Melihat pengajuan surat sesuai wilayah',
            'manage-surat' => 'Memverifikasi dan memproses surat',
            'approve-surat' => 'Menyetujui atau menolak surat',
            'export-surat' => 'Mencetak surat yang sudah disetujui',
            'manage-kegiatan' => 'Membuat dan mengelola kegiatan RT atau RW',
            'view-kegiatan' => 'Melihat daftar kegiatan sesuai wilayah',
            'manage-aset' => 'Mengelola inventaris aset RT dan RW',
            'view-aset' => 'Melihat daftar aset',
            'pinjam-aset' => 'Mengajukan peminjaman aset',
            'manage-aset-rw' => 'Mengelola inventaris aset milik RW',
            'view-aset-rw' => 'Melihat daftar aset milik RW',
            'pinjam-aset-rw' => 'Mengajukan peminjaman aset milik RW',
            'manage-bank-sampah' => 'Mengelola bank sampah RW',
            'view-bank-sampah' => 'Melihat info bank sampah dan saldo sendiri',
            'setor-sampah' => 'Mengajukan setoran sampah',
            'manage-fasilitas' => 'Mengelola data fasilitas dan keamanan',
            'view-fasilitas' => 'Melihat daftar fasilitas',
            'lapor-fasilitas' => 'Melaporkan masalah fasilitas',
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
                    'view-surat',
                    'approve-surat',
                    'export-surat',
                    'manage-kegiatan',
                    'view-kegiatan',
                    'manage-aset',
                    'view-aset',
                    'pinjam-aset',
                    'manage-aset-rw',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'manage-bank-sampah',
                    'view-bank-sampah',
                    'setor-sampah',
                    'manage-fasilitas',
                    'view-fasilitas',
                    'lapor-fasilitas',
                ],
            ],
            'sekretaris_rw' => [
                'description' => 'Sekretaris RW, administrasi level RW',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'manage-pengaduan',
                    'manage-warga',
                    'view-surat',
                    'manage-surat',
                    'export-surat',
                    'manage-kegiatan',
                    'view-kegiatan',
                    'manage-aset',
                    'view-aset',
                    'pinjam-aset',
                    'manage-aset-rw',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'manage-bank-sampah',
                    'view-bank-sampah',
                    'setor-sampah',
                    'manage-fasilitas',
                    'view-fasilitas',
                    'lapor-fasilitas',
                ],
            ],
            'bendahara_rw' => [
                'description' => 'Bendahara RW, rekap keuangan lintas RT',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'view-finance',
                    'export-finance',
                    'view-kegiatan',
                    'view-aset',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'manage-bank-sampah',
                    'view-bank-sampah',
                    'setor-sampah',
                    'view-fasilitas',
                ],
            ],
            'petugas_bank_sampah' => [
                'description' => 'Petugas operasional bank sampah RW',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'view-kegiatan',
                    'manage-bank-sampah',
                    'view-bank-sampah',
                    'setor-sampah',
                ],
            ],
            'ketua_rt' => [
                'description' => 'Ketua RT, approve surat dan pengaduan RT',
                'permissions' => [
                    'view-wilayah',
                    'view-dashboard',
                    'manage-pengaduan',
                    'view-finance',
                    'view-surat',
                    'approve-surat',
                    'export-surat',
                    'manage-kegiatan',
                    'view-kegiatan',
                    'manage-aset',
                    'view-aset',
                    'pinjam-aset',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'view-bank-sampah',
                    'setor-sampah',
                    'manage-fasilitas',
                    'view-fasilitas',
                    'lapor-fasilitas',
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
                    'view-kegiatan',
                    'view-aset',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'view-bank-sampah',
                    'setor-sampah',
                    'view-fasilitas',
                ],
            ],
            'sekretaris_rt' => [
                'description' => 'Sekretaris RT, administrasi level RT',
                'permissions' => [
                    'view-dashboard',
                    'manage-warga',
                    'manage-pengaduan',
                    'submit-pengaduan',
                    'view-surat',
                    'manage-surat',
                    'export-surat',
                    'manage-kegiatan',
                    'view-kegiatan',
                    'manage-aset',
                    'view-aset',
                    'pinjam-aset',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'view-bank-sampah',
                    'setor-sampah',
                    'manage-fasilitas',
                    'view-fasilitas',
                    'lapor-fasilitas',
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
                    'view-kegiatan',
                    'view-aset',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'view-bank-sampah',
                    'setor-sampah',
                    'view-fasilitas',
                ],
            ],
            'sekretaris' => [
                'description' => 'Sekretaris RT',
                'permissions' => [
                    'view-dashboard',
                    'manage-warga',
                    'manage-pengaduan',
                    'submit-pengaduan',
                    'view-surat',
                    'manage-surat',
                    'export-surat',
                    'manage-kegiatan',
                    'view-kegiatan',
                    'manage-aset',
                    'view-aset',
                    'pinjam-aset',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'view-bank-sampah',
                    'setor-sampah',
                    'manage-fasilitas',
                    'view-fasilitas',
                    'lapor-fasilitas',
                ],
            ],
            'warga' => [
                'description' => 'Warga RT',
                'permissions' => [
                    'view-dashboard',
                    'view-finance',
                    'submit-payment',
                    'submit-pengaduan',
                    'submit-surat',
                    'view-surat',
                    'view-kegiatan',
                    'view-aset',
                    'pinjam-aset',
                    'view-aset-rw',
                    'pinjam-aset-rw',
                    'view-bank-sampah',
                    'setor-sampah',
                    'view-fasilitas',
                    'lapor-fasilitas',
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
