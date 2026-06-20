<?php

use App\Models\ProdukUmkm;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-20 09:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW UMKM',
        'address' => 'Jl. Usaha Warga No. 1',
        'kota' => 'Bandung',
        'is_active' => true,
    ]);

    $this->rtSatu = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $this->rtDua = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 02',
        'is_active' => true,
    ]);

    $this->rwLain = Rw::create([
        'name' => 'RW Lain',
        'address' => 'Jl. Wilayah Lain No. 2',
        'kota' => 'Bandung',
        'is_active' => true,
    ]);

    $this->rtLain = Rt::create([
        'rw_id' => $this->rwLain->id,
        'name' => 'RT 99',
        'is_active' => true,
    ]);

    $roleId = fn (string $name) => Role::where('name', $name)->value('id');

    $this->pengurus = User::factory()->create([
        'role_id' => $roleId('ketua_rw'),
        'rt_id' => null,
    ]);

    $this->warga = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->wargaRtDua = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtDua->id,
    ]);

    $this->wargaLain = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtLain->id,
    ]);

    $this->buatUmkm = function (array $attributes = []): Umkm {
        return Umkm::create(array_merge([
            'rw_id' => $this->rw->id,
            'rt_id' => $this->rtSatu->id,
            'pemilik_id' => $this->warga->id,
            'nama_usaha' => 'Dapur Bu Sari',
            'kategori' => 'makanan_minuman',
            'deskripsi' => 'Menjual makanan rumahan segar untuk warga sekitar setiap hari.',
            'alamat_lokasi' => 'Blok A Nomor 10',
            'nomor_whatsapp' => '081234567890',
            'jam_operasional' => '08:00-17:00 Senin-Sabtu',
            'status' => 'pending',
        ], $attributes));
    };
});

afterEach(function () {
    Carbon::setTestNow();
});

test('warga dapat mendaftarkan umkm dan statusnya pending', function () {
    Storage::fake('local');

    $response = $this->actingAs($this->warga)
        ->post(route('umkm.store'), [
            'nama_usaha' => 'Kue Kering Bu Rina',
            'kategori' => 'makanan_minuman',
            'deskripsi' => 'Menyediakan aneka kue kering rumahan untuk acara dan pesanan warga.',
            'alamat_lokasi' => 'Blok A Nomor 12',
            'nomor_whatsapp' => '0812-3456-7890',
            'jam_operasional' => '09:00-16:00 Senin-Sabtu',
            'foto_usaha' => UploadedFile::fake()->image('usaha.jpg', 800, 600),
        ]);

    $umkm = Umkm::firstOrFail();

    $response->assertRedirect(route('umkm.saya'))
        ->assertSessionHas('success');
    $this->assertDatabaseHas('umkms', [
        'id' => $umkm->id,
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtSatu->id,
        'pemilik_id' => $this->warga->id,
        'status' => 'pending',
    ]);
    expect($umkm->foto_usaha)->toStartWith('umkm/');
    Storage::disk('local')->assertExists($umkm->foto_usaha);
});

test('umkm pending tidak muncul di direktori warga', function () {
    $umkm = ($this->buatUmkm)();

    $this->actingAs($this->wargaRtDua)
        ->get(route('umkm.index'))
        ->assertOk()
        ->assertDontSee($umkm->nama_usaha);

    $this->actingAs($this->wargaRtDua)
        ->get(route('umkm.show', $umkm))
        ->assertForbidden();
});

test('pengurus dapat approve umkm dan usaha langsung muncul di direktori', function () {
    $umkm = ($this->buatUmkm)();

    $this->actingAs($this->pengurus)
        ->patch(route('umkm.approve', $umkm))
        ->assertRedirect()
        ->assertSessionHas('success');

    $umkm->refresh();
    expect($umkm->status)->toBe('approved')
        ->and($umkm->diproses_oleh)->toBe($this->pengurus->id)
        ->and($umkm->diproses_at)->not->toBeNull();

    $this->actingAs($this->wargaRtDua)
        ->get(route('umkm.index'))
        ->assertOk()
        ->assertSee($umkm->nama_usaha);
});

test('pengurus dapat reject umkm dengan alasan', function () {
    $umkm = ($this->buatUmkm)();

    $this->actingAs($this->pengurus)
        ->patch(route('umkm.reject', $umkm), [
            'catatan_pengurus' => 'Nomor WhatsApp perlu diperbaiki.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($umkm->fresh()->status)->toBe('rejected')
        ->and($umkm->fresh()->catatan_pengurus)->toBe('Nomor WhatsApp perlu diperbaiki.')
        ->and($umkm->fresh()->diproses_oleh)->toBe($this->pengurus->id);
});

test('pemilik mengedit umkm rejected dan status kembali pending', function () {
    $umkm = ($this->buatUmkm)([
        'status' => 'rejected',
        'catatan_pengurus' => 'Deskripsi usaha belum lengkap.',
        'diproses_oleh' => $this->pengurus->id,
        'diproses_at' => now(),
    ]);

    $this->actingAs($this->warga)
        ->put(route('umkm.update', $umkm), [
            'nama_usaha' => 'Dapur Bu Sari Baru',
            'kategori' => 'makanan_minuman',
            'deskripsi' => 'Menjual makanan rumahan segar dengan menu harian lengkap dan layanan pesan antar.',
            'alamat_lokasi' => 'Blok A Nomor 10',
            'nomor_whatsapp' => '081234567890',
            'jam_operasional' => '08:00-18:00 Setiap Hari',
        ])
        ->assertRedirect(route('umkm.show', $umkm))
        ->assertSessionHas('success');

    $umkm->refresh();
    expect($umkm->status)->toBe('pending')
        ->and($umkm->nama_usaha)->toBe('Dapur Bu Sari Baru')
        ->and($umkm->catatan_pengurus)->toBeNull()
        ->and($umkm->diproses_oleh)->toBeNull()
        ->and($umkm->diproses_at)->toBeNull();
});

test('pemilik hanya dapat menambah produk setelah umkm approved', function () {
    Storage::fake('local');
    $umkm = ($this->buatUmkm)();

    $this->actingAs($this->warga)
        ->post(route('produk-umkm.store', $umkm), [
            'nama_produk' => 'Nasi Box',
            'harga' => 25000,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(ProdukUmkm::count())->toBe(0);

    $umkm->update(['status' => 'approved']);

    $this->actingAs($this->warga)
        ->post(route('produk-umkm.store', $umkm), [
            'nama_produk' => 'Nasi Box',
            'deskripsi' => 'Nasi box lengkap dengan lauk dan sayur.',
            'harga' => 25000,
            'satuan_harga' => 'per box',
            'foto' => UploadedFile::fake()->image('nasi-box.jpg', 800, 600),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $produk = ProdukUmkm::firstOrFail();
    expect($produk->umkm_id)->toBe($umkm->id)
        ->and($produk->is_available)->toBeTrue()
        ->and($produk->harga)->toBe(25000);
    Storage::disk('local')->assertExists($produk->foto);

    $this->actingAs($this->wargaRtDua)
        ->get(route('produk-umkm.foto', $produk))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});

test('pemilik dapat nonaktifkan dan aktifkan kembali umkm sendiri', function () {
    $umkm = ($this->buatUmkm)(['status' => 'approved']);

    $this->actingAs($this->warga)
        ->patch(route('umkm.nonaktifkan', $umkm))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($umkm->fresh()->status)->toBe('nonaktif');

    $this->actingAs($this->warga)
        ->patch(route('umkm.aktifkan-kembali', $umkm))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($umkm->fresh()->status)->toBe('approved');
});

test('warga rt lain dalam rw sama dapat melihat umkm approved', function () {
    $umkm = ($this->buatUmkm)(['status' => 'approved']);

    $this->actingAs($this->wargaRtDua)
        ->get(route('umkm.index'))
        ->assertOk()
        ->assertSee($umkm->nama_usaha);

    $this->actingAs($this->wargaRtDua)
        ->get(route('umkm.show', $umkm))
        ->assertOk()
        ->assertSee($umkm->nomor_whatsapp);

    $this->actingAs($this->wargaLain)
        ->get(route('umkm.index'))
        ->assertOk()
        ->assertDontSee($umkm->nama_usaha);
});

test('warga tidak bisa edit approve atau menambah produk umkm orang lain', function () {
    $umkm = ($this->buatUmkm)(['status' => 'approved']);

    $this->actingAs($this->wargaRtDua)
        ->get(route('umkm.edit', $umkm))
        ->assertForbidden();

    $this->actingAs($this->wargaRtDua)
        ->put(route('umkm.update', $umkm), [
            'nama_usaha' => 'Usaha Diambil Alih',
            'kategori' => 'jasa',
            'deskripsi' => 'Percobaan perubahan data usaha milik warga lain secara tidak sah.',
            'nomor_whatsapp' => '081234567891',
        ])
        ->assertForbidden();

    $this->actingAs($this->wargaRtDua)
        ->patch(route('umkm.approve', $umkm))
        ->assertForbidden();

    $this->actingAs($this->wargaRtDua)
        ->post(route('produk-umkm.store', $umkm), [
            'nama_produk' => 'Produk Tidak Sah',
        ])
        ->assertForbidden();

    expect($umkm->fresh()->nama_usaha)->toBe('Dapur Bu Sari')
        ->and(ProdukUmkm::count())->toBe(0);
});

test('whatsapp url membersihkan nomor dan mengganti awalan nol dengan kode indonesia', function () {
    $umkm = ($this->buatUmkm)([
        'nomor_whatsapp' => '0812-345 6789',
    ]);

    expect($umkm->whatsapp_url)->toBe('https://wa.me/628123456789');

    $umkm->nomor_whatsapp = '+62 812-345-6789';
    expect($umkm->whatsapp_url)->toBe('https://wa.me/628123456789');
});
