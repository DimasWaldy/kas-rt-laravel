<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_cards', function (Blueprint $table) {
            $table->timestamp('ktp_verified_at')->nullable()->after('uid_hash');
            $table->foreignId('verified_by')->nullable()->after('ktp_verified_at')->constrained('users')->nullOnDelete();
            $table->foreignId('verified_rt_id')->nullable()->after('verified_by')->constrained('rts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rfid_cards', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['verified_rt_id']);
            $table->dropColumn(['ktp_verified_at', 'verified_by', 'verified_rt_id']);
        });
    }
};
