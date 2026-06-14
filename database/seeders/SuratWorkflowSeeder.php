<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuratWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WilayahSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);

        $rt = Rt::where('name', 'RT 01')->firstOrFail();
        $wargaRole = Role::where('name', 'warga')->firstOrFail();

        $rumah = Rumah::updateOrCreate(
            ['kode_rumah' => 'DEMO-SURAT-01'],
            [
                'alamat' => 'Jl. Melati No. 101',
                'rt' => '01',
                'rw' => '05',
                'rt_id' => $rt->id,
                'status' => 'aktif',
            ]
        );

        $warga = User::updateOrCreate(
            ['email' => 'warga.surat@smart-rw.test'],
            [
                'name' => 'Warga Demo Surat',
                'password' => Hash::make('password'),
                'role_id' => $wargaRole->id,
                'rumah_id' => $rumah->id,
                'rt_id' => $rt->id,
                'no_kk' => '3273010101010001',
                'is_kepala_keluarga' => true,
                'is_penanggung_jawab_rumah' => true,
                'jumlah_anggota_keluarga' => 4,
                'phone' => '081200000101',
                'rt' => '01',
                'rw' => '05',
                'email_verified_at' => now(),
            ]
        );

        $rumah->update(['penanggung_jawab_id' => $warga->id]);

        $accounts = [
            [
                'name' => 'Sekretaris RT 01',
                'email' => 'sekretaris.rt@smart-rw.test',
                'role' => 'sekretaris_rt',
                'rt_id' => $rt->id,
            ],
            [
                'name' => 'Ketua RT 01',
                'email' => 'ketua.rt@smart-rw.test',
                'role' => 'ketua_rt',
                'rt_id' => $rt->id,
            ],
            [
                'name' => 'Sekretaris RW 05',
                'email' => 'sekretaris.rw@smart-rw.test',
                'role' => 'sekretaris_rw',
                'rt_id' => null,
            ],
            [
                'name' => 'Ketua RW 05',
                'email' => 'ketua.rw@smart-rw.test',
                'role' => 'ketua_rw',
                'rt_id' => null,
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'role_id' => Role::where('name', $account['role'])->value('id'),
                    'rt_id' => $account['rt_id'],
                    'rt' => $account['rt_id'] ? '01' : null,
                    'rw' => '05',
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('Akun demo workflow surat siap. Semua akun memakai password: password');
    }
}
