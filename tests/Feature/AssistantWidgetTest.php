<?php

use App\Models\ProdukUmkm;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW Asisten',
        'is_active' => true,
    ]);

    $this->rt = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $wargaRoleId = Role::where('name', 'warga')->value('id');

    $this->rumah = Rumah::create([
        'kode_rumah' => 'BLOK-A1',
        'rt_id' => $this->rt->id,
    ]);

    $this->warga = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rt->id,
        'rumah_id' => $this->rumah->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $this->wargaLain = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rt->id,
    ]);

    Warga::create([
        'user_id' => $this->warga->id,
        'nama_lengkap' => $this->warga->name,
        'nik' => '3201010101010099',
        'status_dalam_kk' => 'kepala_keluarga',
        'status_verifikasi' => 'terverifikasi',
    ]);
});

test('widget customer service tampil di landing page untuk tamu dan di dashboard untuk user login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Asisten Smart RW')
        ->assertSee('Tanya CS')
        ->assertSee('asisten\\/chat', false);

    $this->actingAs($this->warga)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Asisten Smart RW')
        ->assertSee('Tanya CS')
        ->assertSee('asisten\\/chat', false);
});

test('asisten memanggil gemini api dengan header key dan mengabaikan part thought internal', function () {
    config()->set('services.gemini.keys', ['key-utama', 'key-cadangan']);
    config()->set('services.gemini.models', ['gemini-3.5-flash']);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [
                        ['text' => 'Ini proses berpikir internal.', 'thought' => true],
                        ['text' => 'Untuk membuat surat pengantar, silakan login lalu buka menu Surat.'],
                    ],
                ],
            ]],
        ], 200),
    ]);

    $response = $this->postJson(route('assistant.chat'), [
        'message' => 'Gimana cara bikin surat pengantar?',
    ]);

    $response->assertOk()
        ->assertJsonPath('source', 'gemini')
        ->assertJsonPath('reply', 'Untuk membuat surat pengantar, silakan login lalu buka menu Surat.');

    Http::assertSent(function (HttpRequest $request) {
        return $request->header('x-goog-api-key')[0] === 'key-utama'
            && str_contains($request->url(), 'models/gemini-3.5-flash:generateContent');
    });
});

test('failover otomatis pindah ke api key kedua saat key pertama kena limit 429 dan masuk cooldown', function () {
    config()->set('services.gemini.keys', ['key-limit', 'key-kedua']);
    config()->set('services.gemini.models', ['gemini-3.5-flash']);

    Http::fake(function (HttpRequest $request) {
        $key = $request->header('x-goog-api-key')[0] ?? '';

        if ($key === 'key-limit') {
            return Http::response(['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED']], 429);
        }

        return Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'Dijawab oleh key kedua!']],
                ],
            ]],
        ], 200);
    });

    // Request pertama: coba key-limit (429) -> otomatis pindah ke key-kedua (200)
    $this->postJson(route('assistant.chat'), ['message' => 'Cara bayar iuran?'])
        ->assertOk()
        ->assertJsonPath('source', 'gemini')
        ->assertJsonPath('reply', 'Dijawab oleh key kedua!');

    // Request kedua: langsung pakai key-kedua tanpa memukul key-limit yang sedang cooldown
    $this->postJson(route('assistant.chat'), ['message' => 'Jadwal bank sampah?'])
        ->assertOk()
        ->assertJsonPath('source', 'gemini')
        ->assertJsonPath('reply', 'Dijawab oleh key kedua!');

    Http::assertSentCount(3);
});

test('model cascade otomatis beralih ke model cadangan saat model utama mengembalikan 503', function () {
    config()->set('services.gemini.keys', ['key-utama']);
    config()->set('services.gemini.models', ['gemini-3.5-flash', 'gemini-3.1-flash-lite']);

    Http::fake(function (HttpRequest $request) {
        if (str_contains($request->url(), 'gemini-3.5-flash')) {
            return Http::response(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503);
        }

        return Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'Berhasil dari model cadangan!']],
                ],
            ]],
        ], 200);
    });

    $this->postJson(route('assistant.chat'), ['message' => 'Info layanan warga'])
        ->assertOk()
        ->assertJsonPath('source', 'gemini')
        ->assertJsonPath('reply', 'Berhasil dari model cadangan!');

    Http::assertSentCount(2);
});

test('asisten mencari produk umkm approved lengkap dengan link whatsapp dan menyembunyikan produk pending', function () {
    config()->set('services.gemini.keys', []);

    $umkmAktif = Umkm::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rt->id,
        'pemilik_id' => $this->warga->id,
        'nama_usaha' => 'Dapur Bu Sari',
        'kategori' => 'makanan_minuman',
        'deskripsi' => 'Jual nasi uduk dan gorengan hangat.',
        'nomor_whatsapp' => '081234567890',
        'status' => 'approved',
    ]);
    $umkmPending = Umkm::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rt->id,
        'pemilik_id' => $this->wargaLain->id,
        'nama_usaha' => 'Warung Rahasia Pending',
        'kategori' => 'makanan_minuman',
        'deskripsi' => 'Belum disetujui.',
        'nomor_whatsapp' => '089999999999',
        'status' => 'pending',
    ]);

    ProdukUmkm::create([
        'umkm_id' => $umkmAktif->id,
        'nama_produk' => 'Nasi Uduk Spesial',
        'harga' => 15000,
        'satuan_harga' => 'porsi',
        'is_available' => true,
    ]);
    ProdukUmkm::create([
        'umkm_id' => $umkmPending->id,
        'nama_produk' => 'Nasi Uduk Pending',
        'harga' => 12000,
        'is_available' => true,
    ]);

    $response = $this->postJson(route('assistant.chat'), [
        'message' => 'Ada yang jual nasi uduk ga?',
    ]);

    $response->assertOk()
        ->assertJsonPath('source', 'local_fallback')
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.nama_produk', 'Nasi Uduk Spesial');

    expect($response->json('products.0.whatsapp_order_url'))
        ->toStartWith('https://wa.me/6281234567890?text=');
});

test('fallback lokal menjaga privasi tamu dan hanya menampilkan ringkasan tagihan milik user login sendiri', function () {
    config()->set('services.gemini.keys', []);

    Tagihan::create([
        'user_id' => $this->warga->id,
        'rumah_id' => $this->rumah->id,
        'rt_id' => $this->rt->id,
        'bulan' => 11,
        'tahun' => 2026,
        'total' => 50000,
        'status' => 'belum_bayar',
    ]);

    $guestReply = $this->postJson(route('assistant.chat'), [
        'message' => 'Cara cek tagihan iuran?',
    ])->assertOk()->json('reply');

    expect($guestReply)->not->toContain('1 tagihan belum dibayar');

    $authReply = $this->actingAs($this->warga)
        ->postJson(route('assistant.chat'), [
            'message' => 'Cara cek tagihan iuran?',
        ])->assertOk()->json('reply');

    expect($authReply)->toContain('1 tagihan belum dibayar');
});

test('validasi menolak pesan kosong atau terlalu panjang', function () {
    config()->set('services.gemini.keys', []);

    $this->postJson(route('assistant.chat'), ['message' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);

    $this->postJson(route('assistant.chat'), ['message' => str_repeat('a', 501)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);
});

test('throttle membatasi spam chat asisten maksimal 15 permintaan per menit', function () {
    config()->set('services.gemini.keys', []);

    for ($i = 0; $i < 15; $i++) {
        $this->postJson(route('assistant.chat'), ['message' => 'Cara buat surat?'])
            ->assertOk();
    }

    $this->postJson(route('assistant.chat'), ['message' => 'Spam ke-16'])
        ->assertStatus(429);
});
