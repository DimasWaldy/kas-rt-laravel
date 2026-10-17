<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ReportUsersWithoutRt extends Command
{
    protected $signature = 'users:report-without-rt {--limit=50 : Jumlah maksimal baris detail yang ditampilkan}';

    protected $description = 'Menampilkan laporan read-only user yang belum memiliki RT';

    public function handle(): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $total = User::query()->whereNull('rt_id')->count();

        if ($total === 0) {
            $this->info('Semua user sudah punya RT.');

            return self::SUCCESS;
        }

        $this->line('Total user tanpa RT: ' . $total);

        $users = User::query()
            ->with(['role:id,name', 'warga:id,user_id,rumah_diajukan_id'])
            ->whereNull('rt_id')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'role_id', 'rumah_id']);

        $this->table(
            ['id', 'name', 'email', 'role', 'rumah_id', 'rumah_diajukan_id'],
            $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name ?? '-',
                'rumah_id' => $user->rumah_id ?? '-',
                'rumah_diajukan_id' => $user->warga?->rumah_diajukan_id ?? '-',
            ])->all()
        );

        if ($total > $limit) {
            $this->line("Menampilkan {$limit} dari {$total} user. Gunakan --limit= untuk mengubah batas.");
        }

        return self::SUCCESS;
    }
}
