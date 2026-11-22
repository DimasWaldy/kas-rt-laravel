<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pemilihans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->foreignId('rw_id')->nullable()->constrained('rws')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('scope_type', ['rt', 'rw'])->default('rt');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('status', ['draft', 'aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pemilihan_kandidats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemilihan_id')->constrained('pemilihans')->cascadeOnDelete();
            $table->unsignedInteger('nomor_urut');
            $table->string('nama_calon');
            $table->string('nama_wakil')->nullable();
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->string('slogan')->nullable();
            $table->string('foto_path')->nullable();
            $table->timestamps();

            $table->unique(['pemilihan_id', 'nomor_urut']);
        });

        Schema::create('pemilihan_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemilihan_id')->constrained('pemilihans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('voted_at');
            $table->timestamps();

            $table->unique(['pemilihan_id', 'user_id']);
        });

        Schema::create('pemilihan_suaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemilihan_id')->constrained('pemilihans')->cascadeOnDelete();
            $table->foreignId('kandidat_id')->constrained('pemilihan_kandidats')->cascadeOnDelete();
            $table->dateTime('cast_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemilihan_suaras');
        Schema::dropIfExists('pemilihan_voters');
        Schema::dropIfExists('pemilihan_kandidats');
        Schema::dropIfExists('pemilihans');
    }
};
