<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('koperasi_angsurans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('koperasi_pinjam_id')->constrained('koperasi_pinjams')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->date('paid_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->enum('status', ['pending', 'terverifikasi', 'ditolak'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('koperasi_angsurans');
    }
};
