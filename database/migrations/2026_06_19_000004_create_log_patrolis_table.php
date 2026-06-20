<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_patrolis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_shift_id')->constrained('jadwal_shift_satpams')->cascadeOnDelete();
            $table->dateTime('waktu_patroli');
            $table->text('catatan')->nullable();
            $table->boolean('ada_kejadian')->default(false);
            $table->foreignId('dicatat_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['jadwal_shift_id', 'waktu_patroli']);
            $table->index('ada_kejadian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_patrolis');
    }
};
