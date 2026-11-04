<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wargas', function (Blueprint $table) {
            // Identity approval does not approve the household selected afterwards.
            $table->string('status_domisili', 30)->nullable()->index();
            $table->string('mode_domisili', 15)->nullable();
            $table->text('alamat_domisili')->nullable();
            $table->text('catatan_domisili')->nullable();
            $table->timestamp('domisili_diajukan_at')->nullable();
            $table->foreignId('domisili_diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('domisili_diproses_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wargas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domisili_diproses_oleh');
            $table->dropIndex(['status_domisili']);
            $table->dropColumn(['status_domisili', 'mode_domisili', 'alamat_domisili', 'catatan_domisili', 'domisili_diajukan_at', 'domisili_diproses_at']);
        });
    }
};
