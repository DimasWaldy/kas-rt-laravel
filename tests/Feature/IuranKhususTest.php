<?php

use App\Models\IuranKhusus;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW Test',
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

    $roleId = fn (string $name) => Role::where('name', $name)->value('id');

    $this->bendahara = User::factory()->create([
        'role_id' => $roleId('bendahara_rt'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->wargaSatu = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtSatu->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '3174010101010001',
    ]);

    $this->wargaDua = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtSatu->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '3174010101010002',
    ]);

    $this->wargaRtDua = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtDua->id,
        'is_kepala_keluarga' => true,
        'no_kk' => '3174010101010003',
    ]);

    $this->payload = [
        'jenis' => 'sosial',
        'judul' => 'Iuran Sosial Warga Sakit',
        'keterangan' => 'Bantuan sukarela untuk warga yang sedang dirawat.',
        'nominal_per_warga' => 50000,
        'tanggal_kejadian' => now()->toDateString(),
    ];

    $this->buatIuran = function (array $overrides = []): IuranKhusus {
        $this->actingAs($this->bendahara)
            ->post(route('iuran-khusus.store'), array_merge($this->payload, $overrides))
            ->assertRedirect();

        return IuranKhusus::latest('id')->firstOrFail();
    };
});

test('bendahara rt membuat iuran khusus dan tagihan untuk semua target warga rt', function () {
    $response = $this->actingAs($this->bendahara)
        ->post(route('iuran-khusus.store'), $this->payload);

    $iuran = IuranKhusus::firstOrFail();

    $response->assertRedirect(route('iuran-khusus.show', $iuran));
    expect($iuran->rt_id)->toBe($this->rtSatu->id)
        ->and($iuran->billing_group)->toBe('insidental_' . $iuran->id)
        ->and($iuran->total_tagihan)->toBe(2);

    $tagihans = Tagihan::where('billing_group', $iuran->billing_group)->get();

    expect($tagihans)->toHaveCount(2)
        ->and($tagihans->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$this->wargaSatu->id, $this->wargaDua->id])->sort()->values()->all())
        ->and($tagihans->every(fn (Tagihan $tagihan) => $tagihan->isInsidental()))->toBeTrue()
        ->and($tagihans->every(fn (Tagihan $tagihan) => $tagihan->rt_id === $this->rtSatu->id))->toBeTrue();

    $this->assertDatabaseMissing('tagihans', [
        'billing_group' => $iuran->billing_group,
        'user_id' => $this->wargaRtDua->id,
    ]);
});

test('warga tidak dapat mengakses iuran khusus', function () {
    $this->actingAs($this->wargaSatu)
        ->get(route('iuran-khusus.index'))
        ->assertForbidden();
});

test('bendahara dapat mengecualikan warga tertentu', function () {
    $iuran = ($this->buatIuran)();
    $tagihan = $iuran->tagihans()->where('user_id', $this->wargaSatu->id)->firstOrFail();

    $this->actingAs($this->bendahara)
        ->patch(route('iuran-khusus.kecualikan', $tagihan), [
            'alasan_dikecualikan' => 'Sedang mengalami kesulitan ekonomi.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $tagihan->refresh();

    expect($tagihan->dikecualikan_at)->not->toBeNull()
        ->and($tagihan->dikecualikan_oleh)->toBe($this->bendahara->id)
        ->and($tagihan->alasan_dikecualikan)->toBe('Sedang mengalami kesulitan ekonomi.');
});

test('tagihan lunas tidak dapat dikecualikan', function () {
    $iuran = ($this->buatIuran)();
    $tagihan = $iuran->tagihans()->where('user_id', $this->wargaSatu->id)->firstOrFail();
    $tagihan->update(['status' => 'lunas', 'paid_at' => now()]);

    $this->actingAs($this->bendahara)
        ->patch(route('iuran-khusus.kecualikan', $tagihan), [
            'alasan_dikecualikan' => 'Tidak seharusnya dapat diproses.',
        ])
        ->assertForbidden();

    expect($tagihan->fresh()->dikecualikan_at)->toBeNull();
});

test('tagihan iuran khusus mengikuti alur pembayaran biasa', function () {
    Storage::fake('local');

    $iuran = ($this->buatIuran)();
    $tagihan = $iuran->tagihans()->where('user_id', $this->wargaSatu->id)->firstOrFail();

    $this->actingAs($this->wargaSatu)
        ->post(route('tagihan.pay'), [
            'tagihan_id' => $tagihan->id,
            'payment_method' => 'transfer',
            'bukti' => UploadedFile::fake()->image('bukti-iuran-khusus.jpg'),
        ])
        ->assertRedirect(route('tagihan.index'));

    expect($tagihan->fresh()->status)->toBe('pending_transfer');

    $this->actingAs($this->bendahara)
        ->post(route('tagihan.confirm'), [
            'tagihan_id' => $tagihan->id,
            'status' => 'lunas',
        ])
        ->assertRedirect(route('tagihan.admin'));

    expect($tagihan->fresh()->status)->toBe('lunas')
        ->and($tagihan->fresh()->paid_at)->not->toBeNull();
});

test('bendahara rt tidak dapat mengakses iuran khusus rt lain', function () {
    $iuranRtDua = IuranKhusus::create([
        'rt_id' => $this->rtDua->id,
        'created_by' => $this->bendahara->id,
        'jenis' => 'pembangunan',
        'judul' => 'Perbaikan Pos RT 02',
        'nominal_per_warga' => 75000,
        'billing_group' => 'insidental_rt_dua',
    ]);

    $this->actingAs($this->bendahara)
        ->get(route('iuran-khusus.show', $iuranRtDua))
        ->assertForbidden();
});

test('billing group unik untuk setiap batch iuran khusus', function () {
    $iuranPertama = ($this->buatIuran)();
    $iuranKedua = ($this->buatIuran)([
        'jenis' => 'pembangunan',
        'judul' => 'Iuran Perbaikan Jalan RT',
    ]);

    expect($iuranPertama->billing_group)->not->toBe($iuranKedua->billing_group)
        ->and($iuranPertama->billing_group)->toBe('insidental_' . $iuranPertama->id)
        ->and($iuranKedua->billing_group)->toBe('insidental_' . $iuranKedua->id);
});
