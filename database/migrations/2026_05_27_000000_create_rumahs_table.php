<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rumahs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_rumah')->unique();
            $table->text('alamat')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['aktif', 'kosong', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rumah_id')->nullable()->after('role_id')->constrained('rumahs')->nullOnDelete();
            $table->boolean('is_penanggung_jawab_rumah')->default(false)->after('is_kepala_keluarga');
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->foreignId('rumah_id')->nullable()->after('user_id')->constrained('rumahs')->nullOnDelete();
            $table->unique(['rumah_id', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropUnique(['rumah_id', 'bulan', 'tahun']);
            $table->dropConstrainedForeignId('rumah_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rumah_id');
            $table->dropColumn('is_penanggung_jawab_rumah');
        });

        Schema::dropIfExists('rumahs');
    }
};
