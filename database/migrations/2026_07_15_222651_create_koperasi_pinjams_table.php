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
        Schema::create('koperasi_pinjams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedTinyInteger('tenor_months');
            $table->decimal('service_fee_percentage', 5, 2);
            $table->unsignedInteger('service_fee_amount');
            $table->unsignedInteger('remaining_amount'); // amount + service_fee_amount
            $table->string('proof_path')->nullable(); // For transfer proof to user
            $table->enum('status', ['menunggu_persetujuan', 'disetujui', 'ditolak', 'lunas'])->default('menunggu_persetujuan');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('koperasi_pinjams');
    }
};
