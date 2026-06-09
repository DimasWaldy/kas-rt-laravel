<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Tagihan;
use App\Models\KasMasuk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Gunakan firstOrCreate agar tidak duplikat antar test case
    $this->wargaRole = Role::firstOrCreate(
        ['name' => 'warga'],
        ['description' => 'Resident Role']
    );

    $this->adminRole = Role::firstOrCreate(
        ['name' => 'admin'],
        ['description' => 'Admin Role']
    );
});

test('resident can pay bill using bank transfer with valid file', function () {
    Storage::fake('local');

    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    $bukti = UploadedFile::fake()->image('payment_proof.jpg');

    $this->actingAs($resident)->get(route('tagihan.index'));

    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti'          => $bukti,
        'note'           => 'Paid via Mobile Banking',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tagihan.index'));

    $tagihan->refresh();
    expect($tagihan->status)->toBe('pending_transfer');
    expect($tagihan->payment_method)->toBe('transfer');
    expect($tagihan->verification_status)->toBe('menunggu');
    expect($tagihan->transaction_number)->toStartWith('TRX-');
    expect($tagihan->note)->toBe('Paid via Mobile Banking');
    expect($tagihan->bukti)->not->toBeNull();
    expect(AuditLog::where('auditable_type', Tagihan::class)
        ->where('auditable_id', $tagihan->id)
        ->where('event', 'payment_submitted')
        ->count())->toBe(1);
    expect(AuditLog::where('auditable_type', Tagihan::class)
        ->where('auditable_id', $tagihan->id)
        ->where('event', 'updated')
        ->count())->toBe(0);

    Storage::disk('local')->assertExists($tagihan->bukti);

    $this->actingAs($resident)
        ->get(route('tagihan.bukti', $tagihan))
        ->assertOk();

    $otherResident = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '9876543210987654',
    ]);

    $this->actingAs($otherResident)
        ->get(route('tagihan.bukti', $tagihan))
        ->assertForbidden();
});

test('transfer payment fails validation when file is missing, invalid type, or too large', function () {
    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    // Case 1: Missing file
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
    ]);

    $response->assertSessionHasErrors(['bukti' => 'Bukti pembayaran wajib diunggah untuk metode transfer.']);

    // Case 2: Invalid extension (e.g. txt)
    $invalidFile = UploadedFile::fake()->create('proof.txt', 100);
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti'          => $invalidFile,
    ]);

    $response->assertSessionHasErrors(['bukti' => 'Format bukti pembayaran harus berupa gambar JPG/PNG atau PDF.']);

    // Case 3: Too large (3MB > 2MB limit)
    $largeFile = UploadedFile::fake()->image('proof.jpg')->size(3000);
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti'          => $largeFile,
    ]);

    $response->assertSessionHasErrors(['bukti' => 'Ukuran berkas bukti pembayaran maksimal adalah 2 MB (2048 KB).']);
});

test('resident can pay offline with a valid note', function () {
    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    $this->actingAs($resident)->get(route('tagihan.index'));

    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'offline',
        'note'           => 'Diserahkan langsung ke Pak RT',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tagihan.index'));

    $tagihan->refresh();
    expect($tagihan->status)->toBe('pending_offline');
    expect($tagihan->payment_method)->toBe('offline');
    expect($tagihan->verification_status)->toBe('menunggu');
    expect($tagihan->transaction_number)->toStartWith('TRX-');
    expect($tagihan->note)->toBe('Diserahkan langsung ke Pak RT');
    expect($tagihan->bukti)->toBeNull();
});

test('offline payment fails validation when note is missing or too short', function () {
    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    // Case 1: Missing note
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'offline',
    ]);

    $response->assertSessionHasErrors(['note' => 'Catatan wajib diisi untuk pembayaran offline (misal: diserahkan ke siapa & tanggal).']);

    // Case 2: Note is too short (under 5 chars)
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'offline',
        'note'           => 'RT',
    ]);

    $response->assertSessionHasErrors(['note' => 'Catatan pembayaran offline minimal harus terdiri dari 5 karakter.']);
});

test('resident cannot resubmit payment while bill is already pending', function () {
    $resident = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'total' => 50000,
        'status' => 'pending_offline',
        'payment_method' => 'offline',
        'verification_status' => 'menunggu',
        'note' => 'Sudah diserahkan ke bendahara',
    ]);

    $response = $this->actingAs($resident)->post(route('tagihan.pay'), [
        'tagihan_id' => $tagihan->id,
        'payment_method' => 'offline',
        'note' => 'Coba bayar ulang',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Tagihan ini sudah dibayar atau sedang dalam proses verifikasi.');

    expect($tagihan->fresh()->status)->toBe('pending_offline');
    expect($tagihan->fresh()->note)->toBe('Sudah diserahkan ke bendahara');
});

test('cash income is created only after verification and removed when bill is reopened', function () {
    $admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
    ]);

    $resident = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'billing_group' => 'iuran_rutin',
        'judul' => 'Iuran Kebersihan & Keamanan',
        'total' => 50000,
        'status' => 'pending_offline',
        'payment_method' => 'offline',
        'note' => 'Diserahkan ke bendahara',
    ]);

    expect(KasMasuk::where('tagihan_id', $tagihan->id)->exists())->toBeFalse();

    $this->actingAs($admin)->post(route('tagihan.confirm'), [
        'tagihan_id' => $tagihan->id,
        'status' => 'lunas',
    ])->assertRedirect(route('tagihan.admin'));

    $kasMasuk = KasMasuk::where('tagihan_id', $tagihan->id)->first();

    expect($kasMasuk)->not->toBeNull();
    expect((int) $kasMasuk->jumlah)->toBe(50000);
    expect(AuditLog::where('auditable_type', Tagihan::class)
        ->where('auditable_id', $tagihan->id)
        ->where('event', 'tagihan_status_updated')
        ->count())->toBe(1);
    expect(AuditLog::where('auditable_type', Tagihan::class)
        ->where('auditable_id', $tagihan->id)
        ->where('event', 'updated')
        ->count())->toBe(0);

    $this->actingAs($admin)->post(route('tagihan.confirm'), [
        'tagihan_id' => $tagihan->id,
        'status' => 'belum_bayar',
    ])->assertRedirect(route('tagihan.admin'));

    $tagihan->refresh();

    expect(KasMasuk::where('tagihan_id', $tagihan->id)->exists())->toBeFalse();
    expect($tagihan->status)->toBe('belum_bayar');
    expect($tagihan->payment_method)->toBe('none');
    expect($tagihan->note)->toBeNull();
    expect($tagihan->verification_status)->toBe('belum_dikirim');
    expect($tagihan->transaction_number)->toBeNull();
});

test('admin can reject payment proof with reason and resident can resubmit', function () {
    Storage::fake('local');

    $admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
    ]);

    $resident = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'total' => 50000,
        'status' => 'belum_bayar',
    ]);

    $this->actingAs($resident)->post(route('tagihan.pay'), [
        'tagihan_id' => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti' => UploadedFile::fake()->image('wrong-proof.jpg'),
    ])->assertRedirect(route('tagihan.index'));

    $tagihan->refresh();
    $firstTransactionNumber = $tagihan->transaction_number;

    $this->actingAs($admin)->post(route('tagihan.confirm'), [
        'tagihan_id' => $tagihan->id,
        'status' => 'ditolak',
        'verification_note' => 'Sudah dicek bendahara.',
        'rejection_reason' => 'Nominal pada bukti tidak sesuai tagihan.',
    ])->assertRedirect(route('tagihan.admin'));

    $tagihan->refresh();

    expect($tagihan->status)->toBe('failed');
    expect($tagihan->verification_status)->toBe('ditolak');
    expect($tagihan->rejection_reason)->toBe('Nominal pada bukti tidak sesuai tagihan.');
    expect($tagihan->rejected_by)->toBe($admin->id);
    expect($tagihan->rejected_at)->not->toBeNull();
    expect($tagihan->verified_by)->toBe($admin->id);
    expect($tagihan->verified_at)->not->toBeNull();

    $this->actingAs($resident)->post(route('tagihan.pay'), [
        'tagihan_id' => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti' => UploadedFile::fake()->image('correct-proof.jpg'),
    ])->assertRedirect(route('tagihan.index'));

    $tagihan->refresh();

    expect($tagihan->status)->toBe('pending_transfer');
    expect($tagihan->verification_status)->toBe('menunggu');
    expect($tagihan->rejection_reason)->toBeNull();
    expect($tagihan->rejected_by)->toBeNull();
    expect($tagihan->rejected_at)->toBeNull();
    expect($tagihan->transaction_number)->toBe($firstTransactionNumber);
});

test('manual bill uses deterministic billing group and rejects duplicate manual bill only', function () {
    $admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
    ]);

    $resident = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '1234567890123456',
    ]);

    Tagihan::create([
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'billing_group' => 'iuran_rutin',
        'judul' => 'Iuran Kebersihan & Keamanan',
        'total' => 50000,
        'status' => 'belum_bayar',
    ]);

    $this->actingAs($admin)->post(route('tagihan.store'), [
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'total' => 75000,
        'note' => 'Tagihan khusus kegiatan RT',
    ])->assertRedirect(route('tagihan.admin'));

    $this->assertDatabaseHas('tagihans', [
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'billing_group' => Tagihan::BILLING_GROUP_MANUAL,
        'judul' => 'Tagihan Manual',
        'total' => 75000,
    ]);

    $this->actingAs($admin)->post(route('tagihan.store'), [
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'total' => 85000,
        'note' => 'Tagihan khusus lain',
    ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Tagihan manual untuk warga, bulan, dan tahun ini sudah ada.');

    expect(Tagihan::where('user_id', $resident->id)
        ->where('bulan', 5)
        ->where('tahun', 2026)
        ->where('billing_group', Tagihan::BILLING_GROUP_MANUAL)
        ->count())->toBe(1);
});

test('manual bill duplicate check uses rumah when resident has rumah', function () {
    $admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
    ]);

    $rumah = Rumah::create([
        'kode_rumah' => 'A-99',
        'alamat' => 'Jl. Melati No. 99',
        'rt' => '001',
        'rw' => '002',
        'status' => 'aktif',
    ]);

    $head = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'rumah_id' => $rumah->id,
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => true,
        'no_kk' => '1234567890123456',
    ]);

    $otherHeadInSameHouse = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'rumah_id' => $rumah->id,
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => false,
        'no_kk' => '6543210987654321',
    ]);

    $rumah->update(['penanggung_jawab_id' => $head->id]);

    $this->actingAs($admin)->post(route('tagihan.store'), [
        'user_id' => $head->id,
        'bulan' => 6,
        'tahun' => 2026,
        'total' => 75000,
        'note' => 'Tagihan khusus rumah',
    ])->assertRedirect(route('tagihan.admin'));

    $this->assertDatabaseHas('tagihans', [
        'user_id' => $head->id,
        'rumah_id' => $rumah->id,
        'bulan' => 6,
        'tahun' => 2026,
        'billing_group' => Tagihan::BILLING_GROUP_MANUAL,
    ]);

    $this->actingAs($admin)->post(route('tagihan.store'), [
        'user_id' => $otherHeadInSameHouse->id,
        'bulan' => 6,
        'tahun' => 2026,
        'total' => 85000,
        'note' => 'Tagihan manual rumah kedua',
    ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Tagihan manual untuk warga, bulan, dan tahun ini sudah ada.');

    expect(Tagihan::where('rumah_id', $rumah->id)
        ->where('bulan', 6)
        ->where('tahun', 2026)
        ->where('billing_group', Tagihan::BILLING_GROUP_MANUAL)
        ->count())->toBe(1);
});

test('admin bill page paginates and filters by month and year', function () {
    $admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
    ]);

    for ($i = 1; $i <= 25; $i++) {
        $resident = User::factory()->create([
            'role_id' => $this->wargaRole->id,
            'is_kepala_keluarga' => true,
            'no_kk' => '3174' . str_pad((string) $i, 12, '0', STR_PAD_LEFT),
        ]);

        Tagihan::create([
            'user_id' => $resident->id,
            'bulan' => 6,
            'tahun' => 2026,
            'billing_group' => Tagihan::BILLING_GROUP_MANUAL,
            'judul' => 'Tagihan Manual',
            'total' => 50000 + $i,
            'status' => 'belum_bayar',
        ]);
    }

    $otherResident = User::factory()->create([
        'role_id' => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '3174999999999999',
    ]);

    Tagihan::create([
        'user_id' => $otherResident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'billing_group' => Tagihan::BILLING_GROUP_MANUAL,
        'judul' => 'Tagihan Manual Mei',
        'total' => 99000,
        'status' => 'belum_bayar',
    ]);

    $response = $this->actingAs($admin)->get(route('tagihan.admin', [
        'bulan' => 6,
        'tahun' => 2026,
    ]));

    $response->assertOk();
    $response->assertSee('Filter Bulan');
    $response->assertSee('Filter Tahun');
    $response->assertViewHas('tagihans', function ($tagihans) {
        return $tagihans->perPage() === 20
            && $tagihans->total() === 25
            && $tagihans->count() === 20
            && $tagihans->every(fn (Tagihan $tagihan) => $tagihan->bulan === 6 && $tagihan->tahun === 2026);
    });
});
