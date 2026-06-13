<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rt_id')
                ->nullable()
                ->constrained('rts')
                ->nullOnDelete();
        });

        Schema::table('rumahs', function (Blueprint $table) {
            $table->foreignId('rt_id')
                ->nullable()
                ->constrained('rts')
                ->nullOnDelete();
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->foreignId('rt_id')
                ->nullable()
                ->constrained('rts')
                ->nullOnDelete();
        });

        Schema::table('kas_masuks', function (Blueprint $table) {
            $table->foreignId('rt_id')
                ->nullable()
                ->constrained('rts')
                ->nullOnDelete();
        });

        Schema::table('kas_keluars', function (Blueprint $table) {
            $table->foreignId('rt_id')
                ->nullable()
                ->constrained('rts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kas_keluars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rt_id');
        });

        Schema::table('kas_masuks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rt_id');
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rt_id');
        });

        Schema::table('rumahs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rt_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rt_id');
        });
    }
};
