<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rukem_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->foreignId('rw_id')->nullable()->constrained('rws')->nullOnDelete();
            $table->string('member_number')->nullable()->index();
            $table->date('joined_at');
            $table->string('status')->default('active'); // active, inactive, pending, rejected
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('rukem_iurans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->foreignId('rw_id')->nullable()->constrained('rws')->nullOnDelete();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('proof_path')->nullable();
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->date('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['period_year', 'period_month']);
            $table->index(['user_id', 'period_year', 'period_month']);
        });

        Schema::create('rukem_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->foreignId('rw_id')->nullable()->constrained('rws')->nullOnDelete();
            $table->string('deceased_name');
            $table->string('nik_deceased')->nullable();
            $table->string('relationship'); // orang_tua, pasangan, anak, saudara, famili_lain, tetangga
            $table->date('death_date');
            $table->dateTime('funeral_date')->nullable();
            $table->string('cemetery_location')->nullable();
            $table->text('description')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status')->default('submitted'); // submitted, verified, approved, rejected, done
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('rukem_bantuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('rukem_claims')->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->foreignId('rw_id')->nullable()->constrained('rws')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->date('given_at');
            $table->foreignId('given_by')->constrained('users')->cascadeOnDelete();
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rukem_bantuans');
        Schema::dropIfExists('rukem_claims');
        Schema::dropIfExists('rukem_iurans');
        Schema::dropIfExists('rukem_members');
    }
};
