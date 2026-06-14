<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->string('surat_number')->nullable()->unique();
            $table->string('verification_code', 40)->nullable()->unique();
            $table->string('type');
            $table->string('subject');
            $table->text('purpose');
            $table->text('content')->nullable();
            $table->boolean('requires_rw')->default(false);
            $table->string('status')->default('submitted')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_rt_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_rt_at')->nullable();
            $table->foreignId('approved_rt_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_rt_at')->nullable();
            $table->foreignId('verified_rw_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_rw_at')->nullable();
            $table->foreignId('approved_rw_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_rw_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->string('result_file')->nullable();
            $table->timestamps();

            $table->index(['rt_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};
