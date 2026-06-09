<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tagihans` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'belum_bayar'");
        }

        Schema::table('tagihans', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihans', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }

            if (! Schema::hasColumn('tagihans', 'rejected_by')) {
                $table->foreignId('rejected_by')
                    ->nullable()
                    ->after('rejected_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            if (Schema::hasColumn('tagihans', 'rejected_by')) {
                $table->dropConstrainedForeignId('rejected_by');
            }

            if (Schema::hasColumn('tagihans', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
        });
    }
};
