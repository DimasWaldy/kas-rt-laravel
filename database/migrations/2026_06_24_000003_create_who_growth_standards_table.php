<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('who_growth_standards', function (Blueprint $table) {
            $table->id();
            $table->string('indicator');
            $table->string('jenis_kelamin');
            $table->unsignedTinyInteger('usia_bulan');
            $table->decimal('l', 12, 8);
            $table->decimal('m', 12, 6);
            $table->decimal('s', 12, 8);
            $table->decimal('sd3neg', 8, 3);
            $table->decimal('sd2neg', 8, 3);
            $table->decimal('sd1neg', 8, 3);
            $table->decimal('sd0', 8, 3);
            $table->decimal('sd1', 8, 3);
            $table->decimal('sd2', 8, 3);
            $table->decimal('sd3', 8, 3);
            $table->string('versi_standar');
            $table->string('source_file');
            $table->string('source_checksum', 64);
            $table->timestamps();

            $table->unique(['indicator', 'jenis_kelamin', 'usia_bulan'], 'who_growth_standard_unique');
            $table->index(['indicator', 'jenis_kelamin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('who_growth_standards');
    }
};
