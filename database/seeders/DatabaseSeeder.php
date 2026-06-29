<?php

namespace Database\Seeders;

use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
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
        $this->call(WhoGrowthStandardSeeder::class);
        $this->call(PosyanduDemoSeeder::class);

        $wargaRole = Role::where('name', 'warga')->firstOrFail();
        $defaultRt = Rt::with('rw')->where('name', 'RT 01')->firstOrFail();

        $rumah = Rumah::updateOrCreate(
            ['kode_rumah' => 'DEMO-TEST-01'],
            [
                'alamat' => 'Jl. Demo Smart RW No. 1',
                'rt' => '01',
                'rw' => '05',
                'rt_id' => $defaultRt->id,
                'status' => 'aktif',
            ]
        );

        $kartuKeluarga = KartuKeluarga::updateOrCreate(
            ['no_kk' => '3273010101019001'],
            [
                'rumah_id' => $rumah->id,
                'nama_kepala_keluarga' => 'Test User',
            ]
        );

        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role_id' => $wargaRole->id,
                'rumah_id' => $rumah->id,
                'rt_id' => $defaultRt->id,
                'is_penanggung_jawab_rumah' => true,
                'phone' => '081200000001',
                'status_akun' => 'aktif',
                'email_verified_at' => now(),
            ]
        );

        Warga::updateOrCreate(
            ['user_id' => $user->id],
            [
                'kartu_keluarga_id' => $kartuKeluarga->id,
                'nama_lengkap' => $user->name,
                'status_dalam_kk' => 'kepala_keluarga',
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => 'tatap_muka',
                'diverifikasi_at' => now(),
            ]
        );

        $rumah->update(['penanggung_jawab_id' => $user->id]);
    }
}
