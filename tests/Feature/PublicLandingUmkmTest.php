<?php

use App\Models\JamOperasionalUmkm;
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
    Carbon::setTestNow(Carbon::parse('2026-06-22 10:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rwAktif = Rw::create([
        'name' => 'RW 01 Aktif',
        'is_active' => true,
    ]);

    $this->rtAktif = Rt::create([
        'rw_id' => $this->rwAktif->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $this->rwNonaktif = Rw::create([
        'name' => 'RW 99 Nonaktif',
        'is_active' => false,
    ]);

    $this->rtNonaktif = Rt::create([
        'rw_id' => $this->rwNonaktif->id,
        'name' => 'RT 99',
        'is_active' => false,
    ]);

    $roleWargaId = Role::where('name', 'warga')->value('id');

    $this->pemilik = User::factory()->create([
        'role_id' => $roleWargaId,
        'rt_id' => $this->rtAktif->id,
    ]);

    $this->buatUmkm = function (array $overrides = []): Umkm {
        $umkm = Umkm::create(array_merge([
            'rw_id' => $this->rwAktif->id,
            'rt_id' => $this->rtAktif->id,
            'pemilik_id' => $this->pemilik->id,
            'nama_usaha' => 'Warung Nasi Bu Ani',
            'kategori' => 'makanan_minuman',
            'deskripsi' => 'Menyediakan sarapan dan makan siang rumahan untuk warga sekitar.',
            'alamat_lokasi' => 'Blok C No. 4',
            'nomor_whatsapp' => '0812-3456-7890',
            'status' => 'approved',
        ], $overrides));

        foreach (JamOperasionalUmkm::HARI as $hari => $label) {
            $umkm->jamOperasional()->create([
                'hari' => $hari,
                'is_tutup' => false,
                'jam_buka' => '07:00',
                'jam_tutup' => '20:00',
            ]);
        }

        return $umkm;
    };
});

afterEach(function () {
    Carbon::setTestNow();
});

test('pengunjung tanpa login dapat melihat umkm approved dan order produk langsung ke whatsapp di landing page', function () {
    $umkm = ($this->buatUmkm)();
    $produk = ProdukUmkm::create([
        'umkm_id' => $umkm->id,
        'nama_produk' => 'Nasi Uduk Komplit',
        'deskripsi' => 'Nasi uduk hangat dengan ayam goreng dan sambal kacang.',
        'harga' => 18000,
        'satuan_harga' => 'porsi',
        'is_available' => true,
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Etalase &amp; Direktori UMKM Warga', false)
        ->assertSee('Warung Nasi Bu Ani')
        ->assertSee('Nasi Uduk Komplit')
        ->assertSee('Rp 18.000 / porsi')
        ->assertSee('https://wa.me/6281234567890?text=', false)
        ->assertSee('Order via WhatsApp');
});

test('umkm non approved, rw nonaktif, dan produk habis tidak tampil di landing page', function () {
    $approvedUmkm = ($this->buatUmkm)(['nama_usaha' => 'Toko Sembako Jaya']);
    $pendingUmkm = ($this->buatUmkm)([
        'nama_usaha' => 'Usaha Masih Pending',
        'status' => 'pending',
    ]);
    $rejectedUmkm = ($this->buatUmkm)([
        'nama_usaha' => 'Usaha Ditolak',
        'status' => 'rejected',
    ]);
    $nonaktifUmkm = ($this->buatUmkm)([
        'nama_usaha' => 'Usaha Nonaktif',
        'status' => 'nonaktif',
    ]);
    $rwOffUmkm = ($this->buatUmkm)([
        'rw_id' => $this->rwNonaktif->id,
        'rt_id' => $this->rtNonaktif->id,
        'nama_usaha' => 'Usaha RW Tidak Aktif',
        'status' => 'approved',
    ]);

    ProdukUmkm::create([
        'umkm_id' => $approvedUmkm->id,
        'nama_produk' => 'Beras Pandan Wangi 5kg',
        'harga' => 75000,
        'is_available' => true,
    ]);
    ProdukUmkm::create([
        'umkm_id' => $approvedUmkm->id,
        'nama_produk' => 'Minyak Goreng Habis',
        'harga' => 35000,
        'is_available' => false,
    ]);
    ProdukUmkm::create([
        'umkm_id' => $pendingUmkm->id,
        'nama_produk' => 'Produk Usaha Pending',
        'harga' => 15000,
        'is_available' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Toko Sembako Jaya')
        ->assertSee('Beras Pandan Wangi 5kg')
        ->assertDontSee('Usaha Masih Pending')
        ->assertDontSee('Usaha Ditolak')
        ->assertDontSee('Usaha Nonaktif')
        ->assertDontSee('Usaha RW Tidak Aktif')
        ->assertDontSee('Minyak Goreng Habis')
        ->assertDontSee('Produk Usaha Pending');
});

test('foto umkm dan produk approved dapat diakses publik sedangkan status lain ditolak', function () {
    Storage::fake('local');

    $umkmPath = UploadedFile::fake()->image('toko.jpg', 400, 300)->store('umkm', 'local');
    $produkPath = UploadedFile::fake()->image('produk.jpg', 400, 300)->store('umkm/produk', 'local');
    $pendingPath = UploadedFile::fake()->image('pending.jpg', 400, 300)->store('umkm', 'local');
    $pendingProdukPath = UploadedFile::fake()->image('pending-produk.jpg', 400, 300)->store('umkm/produk', 'local');

    $approvedUmkm = ($this->buatUmkm)([
        'foto_usaha' => $umkmPath,
        'status' => 'approved',
    ]);
    $pendingUmkm = ($this->buatUmkm)([
        'nama_usaha' => 'Pending Store',
        'foto_usaha' => $pendingPath,
        'status' => 'pending',
    ]);

    $produkTersedia = ProdukUmkm::create([
        'umkm_id' => $approvedUmkm->id,
        'nama_produk' => 'Ayam Bakar Madu',
        'harga' => 25000,
        'foto' => $produkPath,
        'is_available' => true,
    ]);
    $produkHabis = ProdukUmkm::create([
        'umkm_id' => $approvedUmkm->id,
        'nama_produk' => 'Es Teh Habis',
        'harga' => 5000,
        'foto' => $pendingProdukPath,
        'is_available' => false,
    ]);
    $produkPendingUmkm = ProdukUmkm::create([
        'umkm_id' => $pendingUmkm->id,
        'nama_produk' => 'Produk Pending',
        'harga' => 10000,
        'foto' => $pendingProdukPath,
        'is_available' => true,
    ]);

    $this->get(route('public.umkm.foto', $approvedUmkm))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');

    $this->get(route('public.produk-umkm.foto', $produkTersedia))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');

    $this->get(route('public.umkm.foto', $pendingUmkm))->assertNotFound();
    $this->get(route('public.produk-umkm.foto', $produkHabis))->assertNotFound();
    $this->get(route('public.produk-umkm.foto', $produkPendingUmkm))->assertNotFound();
});

test('landing page tetap tampil rapi saat belum ada umkm terpublikasi', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Etalase UMKM Segera Hadir')
        ->assertSee('Masuk untuk Daftarkan UMKM');
});
