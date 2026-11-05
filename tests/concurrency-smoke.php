<?php

// Run explicitly with: php tests/concurrency-smoke.php
// Uses a dedicated MySQL schema. Never points workers at the application's schema.

use App\Http\Controllers\KoperasiWargaController;
use App\Models\IuranBulanan;
use App\Models\KoperasiMember;
use App\Models\KoperasiPinjam;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const SCHEMA = 'kas_rt_concurrency_20261105';

if (isset($argv[1]) && $argv[1] === 'worker') {
    if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== SCHEMA || config('database.connections.mysql.url')) {
        throw new RuntimeException('Worker database is not the isolated concurrency schema.');
    }

    config(['session.driver' => 'array']);
    Notification::fake();
    Auth::loginUsingId((int) $argv[3]);
    $request = Request::create('/koperasi', 'POST', ['amount' => 900000, 'tenor_months' => 10]);
    $request->setUserResolver(fn () => Auth::user());
    DB::statement('SET SESSION innodb_lock_wait_timeout = 15');
    $threadId = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;

    // Announce only when the operation reaches the locked SELECT, not before it starts.
    $lockTable = $argv[2] === 'bill' ? 'iuran_bulanans' : 'users';
    $attempted = false;
    DB::connection()->beforeExecuting(function (string $query) use ($lockTable, $threadId, &$attempted) {
        if (! $attempted && str_contains(strtolower($query), $lockTable)
            && preg_match('/\bfor update\b/i', $query)) {
            $attempted = true;
            fwrite(STDOUT, 'ATTEMPT '.$threadId."\n");
            fflush(STDOUT);
        }
    });

    try {
        $result = match ($argv[2]) {
            'member' => (new KoperasiWargaController)->storeDaftar($request),
            'loan' => (new KoperasiWargaController)->storePinjam($request),
            'bill' => Tagihan::generate(8, 2026),
            default => throw new InvalidArgumentException('Unknown case.'),
        };
        if (! $attempted) {
            throw new RuntimeException('Operation did not reach the expected locked SELECT.');
        }
        fwrite(STDOUT, 'RESULT '.json_encode(is_array($result) ? $result : [
            'success' => $result->getSession()->get('success'),
            'error' => $result->getSession()->get('error'),
        ])."\n");
    } catch (Throwable $exception) {
        fwrite(STDOUT, 'ERROR '.get_class($exception).': '.$exception->getMessage()."\n");
        exit(1);
    }

    exit(0);
}

if (config('database.default') !== 'mysql' || config('database.connections.mysql.url')) {
    throw new RuntimeException('Run with a direct local MySQL connection, without DB_URL.');
}

$exists = DB::table('information_schema.schemata')->where('schema_name', SCHEMA)->exists();
if (! $exists) {
    DB::statement('CREATE DATABASE `'.SCHEMA.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
$config = config('database.connections.mysql');
$config['database'] = SCHEMA;
config(['database.connections.concurrency' => $config]);
DB::setDefaultConnection('concurrency');

// Resume an incomplete schema setup without touching any other database.
$exit = Artisan::call('migrate', ['--database' => 'concurrency', '--force' => true, '--no-interaction' => true]);
if ($exit !== 0) {
    throw new RuntimeException('Migration failed in isolated schema: '.Artisan::output());
}

$role = Role::firstOrCreate(['name' => 'warga']);
$submit = Permission::firstOrCreate(['name' => 'submit-koperasi']);
$role->permissions()->syncWithoutDetaching($submit->id);

// New fixtures each run; previous results remain intact for inspection.
$user = User::factory()->create(['role_id' => $role->id, 'status_akun' => 'aktif']);
$house = Rumah::create([
    'kode_rumah' => 'PARALLEL-'.$user->id,
    'status' => 'aktif',
    'penanggung_jawab_id' => $user->id,
]);
$user->update(['rumah_id' => $house->id, 'is_penanggung_jawab_rumah' => true]);
IuranBulanan::firstOrCreate(
    ['nama' => 'Iuran Paralel', 'bulan' => 8, 'tahun' => 2026],
    ['jumlah' => 20000, 'is_wajib' => true]
);

function runPair(string $case, int $userId, string $lockTable, int $lockId): array
{
    DB::beginTransaction();
    if (! DB::table($lockTable)->where('id', $lockId)->lockForUpdate()->first()) {
        DB::rollBack();
        throw new RuntimeException('Lock target not found.');
    }
    $workers = [];
    $released = false;
    try {
        for ($n = 0; $n < 2; $n++) {
            $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' worker '.escapeshellarg($case).' '.$userId;
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), array_merge(array_filter(getenv(), 'is_scalar'), [
                'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => SCHEMA, 'DB_URL' => '', 'SESSION_DRIVER' => 'array',
            ]));
            if (! is_resource($process)) {
                throw new RuntimeException('Unable to launch worker.');
            }
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
            $attempt = fgets($pipes[1]);
            if (! preg_match('/^ATTEMPT (\d+)\R$/', (string) $attempt, $matches)) {
                throw new RuntimeException('Worker failed before locked query: '.(string) $attempt.' '.stream_get_contents($pipes[2]));
            }
            $workers[$n]['thread_id'] = (int) $matches[1];
        }

        // Both workers reached the locked query. Confirm MySQL sees both waiting for this exact row lock.
        $deadline = microtime(true) + 8;
        do {
            $waiting = DB::select('SELECT w.REQUESTING_ENGINE_TRANSACTION_ID AS transaction_id FROM performance_schema.data_lock_waits w JOIN performance_schema.threads blocker ON blocker.THREAD_ID = w.BLOCKING_THREAD_ID JOIN performance_schema.threads requester ON requester.THREAD_ID = w.REQUESTING_THREAD_ID WHERE blocker.PROCESSLIST_ID = CONNECTION_ID() AND requester.PROCESSLIST_ID IN (?, ?)', [
                $workers[0]['thread_id'], $workers[1]['thread_id'],
            ]);
            if (count(array_unique(array_column($waiting, 'transaction_id'))) === 2) {
                break;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);

        if (count(array_unique(array_column($waiting, 'transaction_id'))) !== 2) {
            throw new RuntimeException('MySQL did not observe both workers waiting on the held row lock.');
        }
        DB::commit();
        $released = true;

        $results = [];
        foreach ($workers as [$process, $pipes]) {
            $output = trim(stream_get_contents($pipes[1]));
            $errors = trim(stream_get_contents($pipes[2]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            if ($exit !== 0 || $errors !== '' || ! str_starts_with($output, 'RESULT ')) {
                throw new RuntimeException('Worker failed: '.$output.' '.$errors.' (exit '.$exit.')');
            }
            $results[] = json_decode(substr($output, 7), true, 512, JSON_THROW_ON_ERROR);
        }

        return $results;
    } finally {
        if (! $released) {
            DB::rollBack();
        }
        foreach ($workers as [$process, $pipes]) {
            if (is_resource($process)) {
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                proc_close($process);
            }
        }
    }
}

$memberResults = runPair('member', $user->id, 'users', $user->id);
$memberCount = KoperasiMember::where('user_id', $user->id)->count();
if ($memberCount !== 1 || count(array_filter($memberResults, fn ($item) => (bool) $item['success'])) !== 1) {
    throw new RuntimeException('Membership concurrency failed: '.json_encode([$memberResults, $memberCount]));
}
fwrite(STDOUT, 'member: '.json_encode($memberResults).' count='.$memberCount."\n");

KoperasiMember::where('user_id', $user->id)->update(['status' => 'aktif']);
$loanResults = runPair('loan', $user->id, 'users', $user->id);
$loanCount = KoperasiPinjam::where('user_id', $user->id)->count();
if ($loanCount !== 1 || count(array_filter($loanResults, fn ($item) => (bool) $item['success'])) !== 1) {
    throw new RuntimeException('Loan concurrency failed: '.json_encode([$loanResults, $loanCount]));
}
fwrite(STDOUT, 'loan: '.json_encode($loanResults).' count='.$loanCount."\n");

$iuran = IuranBulanan::where('bulan', 8)->where('tahun', 2026)->firstOrFail();
$billCountBefore = Tagihan::where('bulan', 8)->where('tahun', 2026)->count();
$billResults = runPair('bill', $user->id, 'iuran_bulanans', $iuran->id);
$billCount = Tagihan::where('rumah_id', $house->id)->where('bulan', 8)->where('tahun', 2026)->count();
$billCountAfter = Tagihan::where('bulan', 8)->where('tahun', 2026)->count();
if ($billCount !== 1 || array_sum(array_column($billResults, 'created')) !== $billCountAfter - $billCountBefore
    || count(array_filter($billResults, fn ($item) => $item['created'] > 0)) !== 1) {
    throw new RuntimeException('Bill concurrency failed: '.json_encode([$billResults, $billCount, $billCountBefore, $billCountAfter]));
}
fwrite(STDOUT, 'bill: '.json_encode($billResults).' count='.$billCount."\n");
fwrite(STDOUT, 'PASS. Isolated schema retained: '.SCHEMA."\n");
