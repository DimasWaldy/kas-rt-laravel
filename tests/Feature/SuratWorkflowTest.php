<?php

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\Surat;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW 05',
        'address' => 'Jl. Smart RW No. 5',
        'kota' => 'Bandung',
        'is_active' => true,
    ]);
    $this->rtOne = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->rtTwo = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 02', 'is_active' => true]);

    $user = function (string $role, ?Rt $rt = null): User {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            'rt_id' => $rt?->id,
        ]);
    };

    $this->wargaOne = $user('warga', $this->rtOne);
    $this->wargaTwo = $user('warga', $this->rtTwo);
    $this->sekretarisRt = $user('sekretaris_rt', $this->rtOne);
    $this->ketuaRt = $user('ketua_rt', $this->rtOne);
    $this->sekretarisRw = $user('sekretaris_rw');
    $this->ketuaRw = $user('ketua_rw');
});

test('warga can submit a letter with private supporting document', function () {
    Storage::fake('local');

    $response = $this->actingAs($this->wargaOne)->post(route('surat.store'), [
        'type' => 'pengantar',
        'subject' => 'Pengantar administrasi perkawinan',
        'purpose' => 'Persyaratan administrasi ke instansi terkait.',
        'content' => 'Mohon diproses sesuai data kependudukan.',
        'attachments' => [UploadedFile::fake()->image('ktp.jpg')],
    ]);

    $surat = Surat::firstOrFail();
    $response->assertRedirect(route('surat.show', $surat));
    expect($surat->rt_id)->toBe($this->rtOne->id)
        ->and($surat->requires_rw)->toBeTrue()
        ->and($surat->status)->toBe('submitted')
        ->and($surat->attachments)->toHaveCount(1);
    Storage::disk('local')->assertExists($surat->attachments->first()->file_path);
});

test('rw letter completes the rt and rw approval chain', function () {
    $surat = Surat::create([
        'user_id' => $this->wargaOne->id,
        'rt_id' => $this->rtOne->id,
        'type' => 'pengantar',
        'subject' => 'Pengantar administrasi',
        'purpose' => 'Keperluan administrasi warga.',
        'requires_rw' => true,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->sekretarisRt)->patch(route('surat.verify-rt', $surat))->assertRedirect();
    expect($surat->fresh()->status)->toBe('verified_rt');

    $this->actingAs($this->ketuaRt)->patch(route('surat.approve-rt', $surat))->assertRedirect();
    expect($surat->fresh()->status)->toBe('approved_rt');

    $this->actingAs($this->sekretarisRw)->patch(route('surat.verify-rw', $surat))->assertRedirect();
    expect($surat->fresh()->status)->toBe('verified_rw');

    $this->actingAs($this->ketuaRw)->patch(route('surat.approve-rw', $surat))->assertRedirect();
    $surat->refresh();

    expect($surat->status)->toBe('done')
        ->and($surat->surat_number)->not->toBeNull()
        ->and($surat->verification_code)->not->toBeNull();

    $this->get(route('surat.verify-public', $surat->verification_code))
        ->assertOk()
        ->assertSee($surat->surat_number);
});

test('rt only letter is final after ketua rt approval', function () {
    $surat = Surat::create([
        'user_id' => $this->wargaOne->id,
        'rt_id' => $this->rtOne->id,
        'type' => 'domisili',
        'subject' => 'Keterangan domisili',
        'purpose' => 'Keperluan administrasi domisili.',
        'requires_rw' => false,
        'status' => 'verified_rt',
        'submitted_at' => now(),
        'verified_rt_by' => $this->sekretarisRt->id,
        'verified_rt_at' => now(),
    ]);

    $this->actingAs($this->ketuaRt)->patch(route('surat.approve-rt', $surat))->assertRedirect();

    expect($surat->fresh()->status)->toBe('done')
        ->and($surat->fresh()->surat_number)->not->toBeNull();

    $this->actingAs($this->wargaOne)
        ->get(route('surat.print', $surat))
        ->assertOk()
        ->assertSee('RUKUN TETANGGA')
        ->assertSee('Cetak / Simpan PDF');
});

test('warga cannot view another residents letter', function () {
    $surat = Surat::create([
        'user_id' => $this->wargaOne->id,
        'rt_id' => $this->rtOne->id,
        'type' => 'umum',
        'subject' => 'Surat milik warga satu',
        'purpose' => 'Keperluan pengujian privasi.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->wargaTwo)
        ->get(route('surat.show', $surat))
        ->assertNotFound();
});

test('rt official cannot view or process a letter from another rt', function () {
    $surat = Surat::create([
        'user_id' => $this->wargaTwo->id,
        'rt_id' => $this->rtTwo->id,
        'type' => 'umum',
        'subject' => 'Surat dari RT dua',
        'purpose' => 'Keperluan pengujian scope RT.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->sekretarisRt)->get(route('surat.show', $surat))->assertNotFound();
    $this->actingAs($this->sekretarisRt)->patch(route('surat.verify-rt', $surat))->assertNotFound();
    expect($surat->fresh()->status)->toBe('submitted');
});

test('finance officers do not receive access to resident letters', function () {
    $bendaharaRt = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rt')->value('id'),
        'rt_id' => $this->rtOne->id,
    ]);

    $this->actingAs($bendaharaRt)->get(route('surat.index'))->assertForbidden();
});

test('surat index renders scoped list and alpine submission modal', function () {
    $surat = Surat::create([
        'user_id' => $this->wargaOne->id,
        'rt_id' => $this->rtOne->id,
        'type' => 'domisili',
        'subject' => 'Pengajuan warga satu',
        'purpose' => 'Menguji daftar dan modal Alpine.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    Surat::create([
        'user_id' => $this->wargaTwo->id,
        'rt_id' => $this->rtTwo->id,
        'type' => 'umum',
        'subject' => 'Pengajuan warga lain',
        'purpose' => 'Tidak boleh tampil.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->wargaOne)
        ->get(route('surat.index'))
        ->assertOk()
        ->assertSee('Pengajuan warga satu')
        ->assertDontSee('Pengajuan warga lain')
        ->assertSee('form-surat')
        ->assertSee('Form Pengajuan Surat')
        ->assertSee('x-data="{ show: false }"', false);
});

test('invalid submission reopens alpine modal with old input and errors', function () {
    $response = $this->actingAs($this->wargaOne)
        ->from(route('surat.index'))
        ->post(route('surat.store'), [
            'type' => 'domisili',
            'subject' => 'Perihal tetap tersimpan',
            'purpose' => '',
        ])
        ->assertRedirect(route('surat.index'))
        ->assertSessionHasErrors('purpose')
        ->assertSessionHasInput('subject', 'Perihal tetap tersimpan');

    $this->followingRedirects()
        ->actingAs($this->wargaOne)
        ->post(route('surat.store'), [
            'type' => 'domisili',
            'subject' => 'Perihal tetap tersimpan',
            'purpose' => '',
        ])
        ->assertOk()
        ->assertSee('x-data="{ show: true }"', false)
        ->assertSee('Perihal tetap tersimpan');
});

test('legacy create route redirects residents to the alpine modal page', function () {
    $this->actingAs($this->wargaOne)
        ->get(route('surat.create'))
        ->assertRedirect(route('surat.index'))
        ->assertSessionHas('info', 'Gunakan tombol Ajukan Surat.');
});

test('expanded letter catalogue is available in the submission modal', function () {
    $this->actingAs($this->wargaOne)
        ->get(route('surat.index'))
        ->assertOk()
        ->assertSee('Surat Keterangan Kelahiran')
        ->assertSee('Surat Keterangan Kematian')
        ->assertSee('Surat Pengantar Nikah')
        ->assertSee('Surat Pengantar SKCK')
        ->assertSee('Surat Keterangan Penghasilan');
});
