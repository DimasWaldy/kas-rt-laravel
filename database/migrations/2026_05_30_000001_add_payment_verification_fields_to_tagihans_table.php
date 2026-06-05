<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->string('transaction_number')->nullable()->unique()->after('judul');
            $table->string('verification_status')->default('belum_dikirim')->after('payment_method');
            $table->text('verification_note')->nullable()->after('note');
            $table->text('rejection_reason')->nullable()->after('verification_note');
            $table->foreignId('verified_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropUnique(['transaction_number']);
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'transaction_number',
                'verification_status',
                'verification_note',
                'rejection_reason',
                'verified_at',
            ]);
        });
    }
};
