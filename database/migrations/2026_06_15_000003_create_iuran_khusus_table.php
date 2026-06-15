<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuran_khusus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rts');
            $table->foreignId('created_by')->constrained('users');
            $table->string('jenis');
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('nominal_per_warga');
            $table->string('billing_group')->unique();
            $table->date('tanggal_kejadian')->nullable();
            $table->unsignedInteger('total_tagihan')->default(0);
            $table->unsignedBigInteger('total_terkumpul')->default(0);
            $table->timestamps();

            $table->index(['rt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iuran_khusus');
    }
};
