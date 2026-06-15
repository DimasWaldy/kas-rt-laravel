<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->timestamp('dikecualikan_at')->nullable();
            $table->foreignId('dikecualikan_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('alasan_dikecualikan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dikecualikan_oleh');
            $table->dropColumn(['dikecualikan_at', 'alasan_dikecualikan']);
        });
    }
};
