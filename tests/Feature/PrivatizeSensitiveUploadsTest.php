<?php

use App\Console\Commands\PrivatizeSensitiveUploads;
use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

test('sensitive upload command defaults to read only preview', function () {
    Storage::disk('public')->put('koperasi/simpanan/old.jpg', 'receipt');
    $this->artisan('storage:privatize-sensitive-uploads')
        ->expectsOutput('DRY RUN: tidak ada file yang diubah.')
        ->expectsOutput('File sensitif publik ditemukan: 1.')->assertSuccessful();
    Storage::disk('public')->assertExists('koperasi/simpanan/old.jpg');
    Storage::disk('local')->assertMissing('koperasi/simpanan/old.jpg');
});

test('sensitive upload command preserves content paths and unrelated public assets', function () {
    foreach (PrivatizeSensitiveUploads::DIRECTORIES as $directory) {
        Storage::disk('public')->put("{$directory}/old.jpg", "private {$directory}");
    }
    Storage::disk('public')->put('logo/public.png', 'public logo');
    $this->artisan('storage:privatize-sensitive-uploads', ['--apply' => true])->assertSuccessful();
    foreach (PrivatizeSensitiveUploads::DIRECTORIES as $directory) {
        Storage::disk('public')->assertMissing("{$directory}/old.jpg");
        expect(Storage::disk('local')->get("{$directory}/old.jpg"))->toBe("private {$directory}");
    }
    Storage::disk('public')->assertExists('logo/public.png');
    $this->artisan('storage:privatize-sensitive-uploads', ['--apply' => true])
        ->expectsOutput('File sensitif publik ditemukan: 0.')->assertSuccessful();
});

test('different existing private file is never overwritten and public original is retained', function () {
    Storage::disk('public')->put('koperasi/proof.jpg', 'original');
    Storage::disk('local')->put('koperasi/proof.jpg', 'different');
    $this->artisan('storage:privatize-sensitive-uploads', ['--apply' => true])->assertFailed();
    expect(Storage::disk('public')->get('koperasi/proof.jpg'))->toBe('original')
        ->and(Storage::disk('local')->get('koperasi/proof.jpg'))->toBe('different');
});

test('identical private copy permits safe removal of only the public duplicate', function () {
    Storage::disk('public')->put('koperasi/proof.jpg', 'same');
    Storage::disk('local')->put('koperasi/proof.jpg', 'same');
    $this->artisan('storage:privatize-sensitive-uploads', ['--apply' => true])->assertSuccessful();
    Storage::disk('public')->assertMissing('koperasi/proof.jpg');
    expect(Storage::disk('local')->get('koperasi/proof.jpg'))->toBe('same');
});

test('failed private write keeps the public original intact', function () {
    Storage::disk('public')->put('koperasi/proof.jpg', 'original');
    $private = Mockery::mock(FilesystemAdapter::class);
    $private->shouldReceive('path')->with('')->andReturn(Storage::disk('local')->path(''));
    $private->shouldReceive('exists')->with('koperasi/proof.jpg')->andReturn(false);
    $private->shouldReceive('put')->once()->andReturn(false);
    Storage::set('local', $private);
    $this->artisan('storage:privatize-sensitive-uploads', ['--apply' => true])->assertFailed();
    expect(Storage::disk('public')->get('koperasi/proof.jpg'))->toBe('original');
});

test('identical source and destination disks are rejected without deleting anything', function () {
    Storage::disk('public')->put('koperasi/proof.jpg', 'original');
    Storage::set('local', Storage::disk('public'));
    $this->artisan('storage:privatize-sensitive-uploads', ['--apply' => true])->assertFailed();
    Storage::disk('public')->assertExists('koperasi/proof.jpg');
});

test('migrated complaint remains available through authorized route only', function () {
    $rw = Rw::create(['name' => 'RW Upload']);
    $rt = Rt::create(['name' => 'RT Upload', 'rw_id' => $rw->id]);
    $user = User::factory()->create(['rt_id' => $rt->id, 'role_id' => Role::firstOrCreate(['name' => 'warga'])->id]);
    $other = User::factory()->create(['rt_id' => null, 'role_id' => $user->role_id]);
    $report = Pengaduan::create([
        'user_id' => $user->id, 'rt_id' => $rt->id, 'judul' => 'Test report',
        'deskripsi' => 'Test complaint', 'kategori' => 'lainnya', 'status' => 'pending',
        'foto' => 'pengaduan/legacy.jpg',
    ]);
    Storage::disk('public')->put($report->foto, 'photo');
    $this->artisan('storage:privatize-sensitive-uploads', ['--apply' => true])->assertSuccessful();
    $this->get(route('pengaduan.foto', $report))->assertRedirect(route('login'));
    $this->actingAs($other)->get(route('pengaduan.foto', $report))->assertNotFound();
    $this->actingAs($user)->get(route('pengaduan.foto', $report))->assertOk();
    expect($report->fresh()->foto)->toBe('pengaduan/legacy.jpg');
});
