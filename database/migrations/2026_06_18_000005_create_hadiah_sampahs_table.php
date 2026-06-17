<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadiah_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('nilai_tukar');
            $table->unsignedInteger('stok')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['rw_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadiah_sampahs');
    }
};
