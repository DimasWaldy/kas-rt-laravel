<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_sessions', function (Blueprint $table) {
            $table->string('gate_mode', 16)->default('auto')->after('scope_type');
        });

        Schema::create('gerbang_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->nullable()->constrained('rws')->nullOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rfid_card_id')->nullable()->constrained('rfid_cards')->nullOnDelete();
            $table->string('uid_masked', 24)->nullable();
            $table->string('arah', 16)->default('masuk')->index();
            $table->string('status', 20)->default('diizinkan')->index();
            $table->string('device_id', 64)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->timestamp('tapped_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gerbang_logs');

        Schema::table('rfid_sessions', function (Blueprint $table) {
            $table->dropColumn('gate_mode');
        });
    }
};
