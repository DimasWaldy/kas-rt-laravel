<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('isi');
            $table->string('kategori', 40)->default('umum');
            $table->string('prioritas', 20)->default('normal');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamp('tanggal_mulai')->nullable();
            $table->timestamp('tanggal_berakhir')->nullable();
            $table->timestamps();

            $table->index(['rw_id', 'is_published', 'is_pinned']);
            $table->index(['rt_id', 'is_published']);
            $table->index('tanggal_berakhir');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumumans');
    }
};
