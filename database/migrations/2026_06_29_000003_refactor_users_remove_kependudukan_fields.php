<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('users')
                ->whereNotNull('no_kk')
                ->where('no_kk', '<>', '')
                ->chunkById(100, function ($users) {
                    $users->each(function (object $user) {
                        $kartuKeluargaId = DB::table('kartu_keluargas')
                            ->where('no_kk', $user->no_kk)
                            ->value('id');

                        if (! $kartuKeluargaId) {
                            $anggotaKeluarga = DB::table('users')
                                ->where('no_kk', $user->no_kk);

                            $namaKepalaKeluarga = (clone $anggotaKeluarga)
                                ->where('is_kepala_keluarga', true)
                                ->orderBy('id')
                                ->value('name') ?? $user->name;

                            $rumahId = (clone $anggotaKeluarga)
                                ->whereNotNull('rumah_id')
                                ->orderBy('id')
                                ->value('rumah_id');

                            $kartuKeluargaId = DB::table('kartu_keluargas')->insertGetId([
                                'no_kk' => $user->no_kk,
                                'rumah_id' => $rumahId,
                                'nama_kepala_keluarga' => $namaKepalaKeluarga,
                                'created_at' => $user->created_at,
                                'updated_at' => $user->updated_at,
                            ]);
                        }

                        DB::table('wargas')->updateOrInsert(
                            ['user_id' => $user->id],
                            [
                                'kartu_keluarga_id' => $kartuKeluargaId,
                                'nik' => null,
                                'nama_lengkap' => $user->name,
                                'status_dalam_kk' => $user->is_kepala_keluarga
                                    ? 'kepala_keluarga'
                                    : 'anggota',
                                'tanggal_lahir' => null,
                                'jenis_kelamin' => null,
                                'status_verifikasi' => 'terverifikasi',
                                'metode_verifikasi' => null,
                                'dokumen_kk' => null,
                                'dokumen_ktp' => null,
                                'diverifikasi_oleh' => null,
                                'diverifikasi_at' => $user->created_at,
                                'catatan_verifikasi' => null,
                                'rumah_diajukan' => null,
                                'rumah_diajukan_id' => null,
                                'created_at' => $user->created_at,
                                'updated_at' => $user->updated_at,
                            ]
                        );
                    });
                });
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'no_kk',
                'is_kepala_keluarga',
                'jumlah_anggota_keluarga',
                'rt',
                'rw',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('status_akun')->default('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_kk')->nullable();
            $table->boolean('is_kepala_keluarga')->nullable();
            $table->unsignedSmallInteger('jumlah_anggota_keluarga')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status_akun');
        });
    }
};
