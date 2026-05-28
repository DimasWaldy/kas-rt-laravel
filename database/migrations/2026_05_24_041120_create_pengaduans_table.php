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
        Schema::create('pengaduans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('judul');
            $table->string('kategori'); // Keamanan, Kebersihan, Infrastruktur, Sosial, Lainnya
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->enum('status', ['pending', 'proses', 'selesai', 'ditolak'])->default('pending');
            $table->text('tanggapan')->nullable();
            $table->foreignId('tanggapan_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggapan_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduans');
    }
};
