<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('uid_hash', 64)->unique();
            $table->timestamps();
        });

        Schema::create('rfid_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->unique()->constrained('kegiatans')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->foreignId('enrollment_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enrollment_expires_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::table('kegiatan_hadirs', function (Blueprint $table) {
            $table->timestamp('rfid_hadir_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan_hadirs', fn (Blueprint $table) => $table->dropColumn('rfid_hadir_at'));
        Schema::dropIfExists('rfid_sessions');
        Schema::dropIfExists('rfid_cards');
    }
};
