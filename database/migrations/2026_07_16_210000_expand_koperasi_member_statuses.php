<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE koperasi_members MODIFY status ENUM('pending', 'aktif', 'nonaktif', 'ditolak') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE koperasi_members MODIFY status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif'");
        }
    }
};
