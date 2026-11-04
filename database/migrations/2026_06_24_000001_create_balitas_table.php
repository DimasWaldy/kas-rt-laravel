<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('rt_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('rumah_id')->nullable()->constrained('rumahs')->nullOnDelete();
            $table->foreignId('orang_tua_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nik', 16)->nullable()->unique();
            $table->string('no_kk', 16)->nullable();
            $table->string('nama');
            $table->string('jenis_kelamin');
            $table->date('tanggal_lahir');
            $table->decimal('berat_lahir_kg', 5, 2)->nullable();
            $table->decimal('panjang_lahir_cm', 5, 2)->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('nama_ayah')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['rw_id', 'is_active']);
            $table->index(['rt_id', 'is_active']);
            $table->index(['orang_tua_id', 'is_active']);
            $table->index(['tanggal_lahir', 'jenis_kelamin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balitas');
    }
};
