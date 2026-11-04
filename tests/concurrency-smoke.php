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
    fwrite(STDOUT, "READY\n");
    fflush(STDOUT);

    try {
        $result = match ($argv[2]) {
            'member' => (new KoperasiWargaController)->storeDaftar($request),
            'loan' => (new KoperasiWargaController)->storePinjam($request),
            'bill' => Tagihan::generate(8, 2026),
            default => throw new InvalidArgumentException('Unknown case.'),
        };
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

if (! $exists) {
    $exit = Artisan::call('migrate', ['--database' => 'concurrency', '--force' => true, '--no-interaction' => true]);
    if ($exit !== 0) {
        throw new RuntimeException('Migration failed in isolated schema: '.Artisan::output());
    }

    $role = Role::create(['name' => 'warga']);
    $submit = Permission::create(['name' => 'submit-koperasi']);
    $role->permissions()->attach($submit);
} else {
    $role = Role::where('name', 'warga')->firstOrFail();
    Permission::where('name', 'submit-koperasi')->firstOrFail();
}

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
    DB::table($lockTable)->where('id', $lockId)->lockForUpdate()->first();
    $workers = [];
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
            $ready = fgets($pipes[1]);
            if ($ready !== "READY\n") {
                throw new RuntimeException('Worker failed before starting: '.(string) $ready.' '.stream_get_contents($pipes[2]));
            }
        }
        usleep(800000);
        foreach ($workers as [$process]) {
            if (! proc_get_status($process)['running']) {
                throw new RuntimeException('Worker finished while database row remained locked.');
            }
        }
    } finally {
        DB::commit();
    }

    $results = [];
    foreach ($workers as [$process, $pipes]) {
        $results[] = trim(stream_get_contents($pipes[1]));
        $errors = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0 || $errors !== '' || ! str_starts_with($results[array_key_last($results)], 'RESULT ')) {
            throw new RuntimeException('Worker failed: '.$results[array_key_last($results)].' '.$errors.' (exit '.$exit.')');
        }
    }

    return array_map(fn ($result) => json_decode(substr($result, 7), true, 512, JSON_THROW_ON_ERROR), $results);
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
$billResults = runPair('bill', $user->id, 'iuran_bulanans', $iuran->id);
$billCount = Tagihan::where('rumah_id', $house->id)->where('bulan', 8)->where('tahun', 2026)->count();
if ($billCount !== 1 || array_sum(array_column($billResults, 'created')) !== 1) {
    throw new RuntimeException('Bill concurrency failed: '.json_encode([$billResults, $billCount]));
}
fwrite(STDOUT, 'bill: '.json_encode($billResults).' count='.$billCount."\n");
fwrite(STDOUT, 'PASS. Isolated schema retained: '.SCHEMA."\n");
