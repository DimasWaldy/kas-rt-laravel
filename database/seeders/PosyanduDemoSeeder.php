<?php

namespace Database\Seeders;

use App\Models\Balita;
use App\Models\KartuKeluarga;
use App\Models\PemeriksaanPosyandu;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use App\Services\Posyandu\WeightForAgeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PosyanduDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WilayahSeeder::class,
            RoleAndPermissionSeeder::class,
            WhoGrowthStandardSeeder::class,
        ]);

        $rtSatu = Rt::with('rw')->where('name', 'RT 01')->firstOrFail();
        $rtDua = Rt::with('rw')->where('name', 'RT 02')->firstOrFail();

        $petugas = $this->user(
            'petugas.posyandu@smart-rw.test',
            'Kader Posyandu RW 05',
            'petugas_posyandu',
        );
        $sekretaris = $this->user(
            'sekretaris.posyandu@smart-rw.test',
            'Sekretaris Posyandu RT 01',
            'sekretaris_rt',
            $rtSatu,
        );
        $wargaSatu = $this->familyUser(
            'warga.posyandu.rt01@smart-rw.test',
            'Siti Rahmawati',
            $rtSatu,
            'DEMO-POS-RT01',
            '3273010101011001',
        );
        $wargaDua = $this->familyUser(
            'warga.posyandu.rt02@smart-rw.test',
            'Dewi Anggraini',
            $rtDua,
            'DEMO-POS-RT02',
            '3273010101012001',
        );

        $aisyah = Balita::updateOrCreate(
            ['nik' => '3273011508240001'],
            [
                'rw_id' => $rtSatu->rw_id,
                'rt_id' => $rtSatu->id,
                'rumah_id' => $wargaSatu->rumah_id,
                'orang_tua_id' => $wargaSatu->id,
                'no_kk' => $wargaSatu->warga->kartuKeluarga->no_kk,
                'nama' => 'Aisyah Putri',
                'jenis_kelamin' => 'perempuan',
                'tanggal_lahir' => '2024-08-15',
                'berat_lahir_kg' => 3.2,
                'panjang_lahir_cm' => 49,
                'nama_ibu' => $wargaSatu->name,
                'nama_ayah' => 'Ahmad Hidayat',
                'catatan' => 'Data demo KMS dengan riwayat timbang berkala.',
                'is_active' => true,
            ]
        );

        $bagas = Balita::updateOrCreate(
            ['nik' => '3273011001250002'],
            [
                'rw_id' => $rtDua->rw_id,
                'rt_id' => $rtDua->id,
                'rumah_id' => $wargaDua->rumah_id,
                'orang_tua_id' => $wargaDua->id,
                'no_kk' => $wargaDua->warga->kartuKeluarga->no_kk,
                'nama' => 'Bagas Pratama',
                'jenis_kelamin' => 'laki_laki',
                'tanggal_lahir' => '2025-01-10',
                'berat_lahir_kg' => 3.4,
                'panjang_lahir_cm' => 50,
                'nama_ibu' => $wargaDua->name,
                'nama_ayah' => 'Rizky Pratama',
                'catatan' => 'Data demo untuk pengujian scope lintas RT.',
                'is_active' => true,
            ]
        );

        $this->seedMeasurements($aisyah, $petugas, [
            '2024-08-15' => 3.2,
            '2024-09-15' => 4.1,
            '2024-10-15' => 5.0,
            '2024-11-15' => 5.7,
            '2024-12-15' => 6.2,
            '2025-01-15' => 6.7,
            '2025-02-15' => 7.1,
            '2025-03-15' => 7.5,
            '2025-04-15' => 7.8,
            '2025-05-15' => 8.0,
            '2025-06-15' => 8.2,
            '2025-07-15' => 8.4,
            '2025-08-15' => 8.6,
        ]);
        $this->seedMeasurements($bagas, $petugas, [
            '2025-01-10' => 3.4,
            '2025-02-10' => 4.4,
            '2025-03-10' => 5.5,
            '2025-04-10' => 6.3,
            '2025-05-10' => 6.8,
            '2025-06-10' => 7.2,
        ]);

        $this->command?->info('Demo Posyandu siap. Semua akun memakai password: password');
        $this->command?->line("Petugas: {$petugas->email}");
        $this->command?->line("Sekretaris RT: {$sekretaris->email}");
        $this->command?->line("Warga RT 01: {$wargaSatu->email}");
        $this->command?->line("Warga RT 02: {$wargaDua->email}");
    }

    private function user(string $email, string $name, string $role, ?Rt $rt = null): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', $role)->value('id'),
                'rt_id' => $rt?->id,
                'status_akun' => 'aktif',
                'email_verified_at' => now(),
            ]
        );
    }

    private function familyUser(string $email, string $name, Rt $rt, string $houseCode, string $noKk): User
    {
        $rumah = Rumah::updateOrCreate(
            ['kode_rumah' => $houseCode],
            [
                'alamat' => 'Jl. Sehat Posyandu '.str_replace('RT ', 'No. ', $rt->name),
                'rt' => str_replace('RT ', '', $rt->name),
                'rw' => '05',
                'rt_id' => $rt->id,
                'status' => 'aktif',
            ]
        );

        $user = $this->user($email, $name, 'warga', $rt);
        $user->update([
            'rumah_id' => $rumah->id,
            'is_penanggung_jawab_rumah' => true,
            'phone' => $rt->name === 'RT 01' ? '081200009001' : '081200009002',
            'status_akun' => 'aktif',
        ]);

        $kartuKeluarga = KartuKeluarga::updateOrCreate(
            ['no_kk' => $noKk],
            [
                'rumah_id' => $rumah->id,
                'nama_kepala_keluarga' => $name,
            ]
        );

        Warga::updateOrCreate(
            ['user_id' => $user->id],
            [
                'kartu_keluarga_id' => $kartuKeluarga->id,
                'nama_lengkap' => $name,
                'status_dalam_kk' => 'kepala_keluarga',
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => 'tatap_muka',
                'diverifikasi_at' => now(),
            ]
        );

        $rumah->update(['penanggung_jawab_id' => $user->id]);

        return $user->load('warga.kartuKeluarga');
    }

    private function seedMeasurements(Balita $balita, User $petugas, array $weights): void
    {
        $calculator = app(WeightForAgeCalculator::class);

        foreach ($weights as $date => $weight) {
            $result = $calculator->calculate(
                $balita->jenis_kelamin,
                CarbonImmutable::parse($balita->tanggal_lahir),
                CarbonImmutable::parse($date),
                $weight,
            );

            $measurement = PemeriksaanPosyandu::where('balita_id', $balita->id)
                ->whereDate('tanggal_pemeriksaan', $date)
                ->first() ?? new PemeriksaanPosyandu([
                    'balita_id' => $balita->id,
                    'tanggal_pemeriksaan' => $date,
                ]);

            $measurement->fill([
                'petugas_id' => $petugas->id,
                'berat_kg' => $weight,
                'panjang_tinggi_cm' => null,
                'metode_ukur_tinggi' => null,
                'lingkar_kepala_cm' => null,
                'lingkar_lengan_cm' => null,
                'vitamin_a' => str_ends_with($date, '-02-15'),
                'catatan' => 'Riwayat timbang demo.',
                ...$result->toArray(),
            ])->save();
        }
    }
}
