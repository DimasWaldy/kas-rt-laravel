<?php

use App\Models\Pengumuman;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-25 10:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create(['name' => 'RW 01', 'is_active' => true]);
    $this->rtSatu = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->rtDua = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 02', 'is_active' => true]);

    $this->rwLain = Rw::create(['name' => 'RW 09 Nonaktif', 'is_active' => false]);
    $this->rtLain = Rt::create(['rw_id' => $this->rwLain->id, 'name' => 'RT 99', 'is_active' => true]);

    $roleId = fn (string $name) => Role::where('name', $name)->value('id');

    $this->ketuaRw = User::factory()->create(['role_id' => $roleId('ketua_rw'), 'rt_id' => $this->rtSatu->id]);
    $this->sekretarisRt = User::factory()->create(['role_id' => $roleId('sekretaris_rt'), 'rt_id' => $this->rtSatu->id]);
    $this->bendahara = User::factory()->create(['role_id' => $roleId('bendahara_rt'), 'rt_id' => $this->rtSatu->id]);
    $this->wargaSatu = User::factory()->create(['role_id' => $roleId('warga'), 'rt_id' => $this->rtSatu->id]);
    $this->wargaDua = User::factory()->create(['role_id' => $roleId('warga'), 'rt_id' => $this->rtDua->id]);

    $this->buatPengumuman = function (array $overrides = []): Pengumuman {
        return Pengumuman::create(array_merge([
            'rw_id' => $this->rw->id,
            'rt_id' => null,
            'created_by' => $this->ketuaRw->id,
            'judul' => 'Jadwal Fogging Serentak RW 01',
            'isi' => 'Pelaksanaan fogging akan dimulai pukul 08.00 WIB di seluruh blok perumahan warga.',
            'kategori' => 'kesehatan',
            'prioritas' => 'penting',
            'is_pinned' => false,
            'is_public' => true,
            'is_published' => true,
        ], $overrides));
    };
});

afterEach(function () {
    Carbon::setTestNow();
});

test('tamu tidak dapat mengakses rute papan pengumuman internal', function () {
    $pengumuman = ($this->buatPengumuman)();

    $this->get(route('pengumuman.index'))->assertRedirect(route('login'));
    $this->get(route('pengumuman.create'))->assertRedirect(route('login'));
    $this->post(route('pengumuman.store'), [])->assertRedirect(route('login'));
    $this->delete(route('pengumuman.destroy', $pengumuman))->assertRedirect(route('login'));
});

test('warga dan bendahara hanya bisa melihat pengumuman dan dilarang mengelola pengumuman', function () {
    $pengumuman = ($this->buatPengumuman)();

    foreach ([$this->wargaSatu, $this->bendahara] as $aktor) {
        $this->actingAs($aktor)->get(route('pengumuman.index'))
            ->assertOk()
            ->assertSee($pengumuman->judul);

        $this->actingAs($aktor)->get(route('pengumuman.create'))->assertForbidden();
        $this->actingAs($aktor)->post(route('pengumuman.store'), [
            'judul' => 'Percobaan Ilegal',
            'isi' => 'Isi pengumuman yang tidak seharusnya tersimpan.',
            'kategori' => 'umum',
            'prioritas' => 'normal',
        ])->assertForbidden();
        $this->actingAs($aktor)->get(route('pengumuman.edit', $pengumuman))->assertForbidden();
        $this->actingAs($aktor)->patch(route('pengumuman.toggle-pin', $pengumuman))->assertForbidden();
        $this->actingAs($aktor)->delete(route('pengumuman.destroy', $pengumuman))->assertForbidden();
    }
});

test('pengurus rt hanya bisa membuat pengumuman untuk rt sendiri dan tidak bisa mengubah milik rw atau rt lain', function () {
    $this->actingAs($this->sekretarisRt)
        ->post(route('pengumuman.store'), [
            'rt_id' => $this->rtDua->id, // mencoba menarget RT lain
            'judul' => 'Kerja Bakti Khusus RT 01',
            'isi' => 'Mohon seluruh warga RT 01 berkumpul di pos ronda pukul 07.00 WIB.',
            'kategori' => 'umum',
            'prioritas' => 'normal',
            'is_public' => '1',
            'is_published' => '1',
        ])
        ->assertRedirect(route('pengumuman.index'))
        ->assertSessionHas('success');

    $milikRtSatu = Pengumuman::where('judul', 'Kerja Bakti Khusus RT 01')->firstOrFail();
    expect($milikRtSatu->rt_id)->toBe($this->rtSatu->id)
        ->and($milikRtSatu->rw_id)->toBe($this->rw->id);

    $milikRw = ($this->buatPengumuman)(['rt_id' => null, 'judul' => 'Pengumuman Resmi RW']);
    $milikRtDua = ($this->buatPengumuman)(['rt_id' => $this->rtDua->id, 'judul' => 'Pengumuman Internal RT 02']);

    $this->actingAs($this->sekretarisRt)->get(route('pengumuman.edit', $milikRw))->assertForbidden();
    $this->actingAs($this->sekretarisRt)->delete(route('pengumuman.destroy', $milikRw))->assertForbidden();
    $this->actingAs($this->sekretarisRt)->patch(route('pengumuman.toggle-pin', $milikRtDua))->assertForbidden();
    $this->actingAs($this->sekretarisRt)->get(route('pengumuman.edit', $milikRtSatu))->assertOk();
});

test('isolasi wilayah pengumuman di dashboard dan halaman papan pengumuman berjalan ketat', function () {
    $pengumumanRw = ($this->buatPengumuman)([
        'rt_id' => null,
        'judul' => 'Info Serentak RW 01',
        'is_public' => false,
    ]);
    $pengumumanRtSatu = ($this->buatPengumuman)([
        'rt_id' => $this->rtSatu->id,
        'judul' => 'Ronda Malam RT 01',
        'is_public' => false,
    ]);
    $pengumumanRtDua = ($this->buatPengumuman)([
        'rt_id' => $this->rtDua->id,
        'judul' => 'Rahasia Internal RT 02',
        'is_public' => false,
    ]);

    $this->actingAs($this->wargaSatu)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($pengumumanRw->judul)
        ->assertSee($pengumumanRtSatu->judul)
        ->assertDontSee($pengumumanRtDua->judul);

    $this->actingAs($this->wargaDua)
        ->get(route('pengumuman.index'))
        ->assertOk()
        ->assertSee($pengumumanRw->judul)
        ->assertSee($pengumumanRtDua->judul)
        ->assertDontSee($pengumumanRtSatu->judul);
});

test('landing page hanya menampilkan pengumuman publik aktif dan menyembunyikan internal, draf, serta kedaluwarsa', function () {
    $daruratPublik = ($this->buatPengumuman)([
        'judul' => 'Pemadaman Listrik PLN Blok A-D',
        'prioritas' => 'darurat',
        'is_pinned' => true,
        'is_public' => true,
        'is_published' => true,
    ]);
    $internalSaja = ($this->buatPengumuman)([
        'judul' => 'Kunci Portal Malam Internal',
        'is_public' => false,
        'is_published' => true,
    ]);
    $draf = ($this->buatPengumuman)([
        'judul' => 'Pengumuman Draf Belum Terbit',
        'is_public' => true,
        'is_published' => false,
    ]);
    $kedaluwarsa = ($this->buatPengumuman)([
        'judul' => 'Pengumuman Minggu Lalu Sudah Lewat',
        'is_public' => true,
        'is_published' => true,
        'tanggal_berakhir' => now()->subHour(),
    ]);
    $rwMati = ($this->buatPengumuman)([
        'rw_id' => $this->rwLain->id,
        'rt_id' => $this->rtLain->id,
        'judul' => 'Pengumuman RW Nonaktif',
        'is_public' => true,
        'is_published' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee($daruratPublik->judul)
        ->assertSee('Baca Pengumuman')
        ->assertDontSee($internalSaja->judul)
        ->assertDontSee($draf->judul)
        ->assertDontSee($kedaluwarsa->judul)
        ->assertDontSee($rwMati->judul);
});

test('pengurus rw dapat pin, arsipkan, edit, dan hapus pengumuman serta validasi menolak input tidak valid', function () {
    $this->actingAs($this->ketuaRw)
        ->post(route('pengumuman.store'), [
            'judul' => 'A',
            'isi' => 'Pendek',
            'kategori' => 'salah',
            'prioritas' => 'salah',
            'tanggal_mulai' => '2026-11-26 10:00',
            'tanggal_berakhir' => '2026-11-25 10:00',
        ])
        ->assertSessionHasErrors(['judul', 'isi', 'kategori', 'prioritas', 'tanggal_berakhir']);

    $this->actingAs($this->ketuaRw)
        ->post(route('pengumuman.store'), [
            'rt_id' => $this->rtLain->id,
            'judul' => 'Judul Valid Pengumuman',
            'isi' => 'Isi pengumuman sudah valid dan cukup panjang untuk diterbitkan.',
            'kategori' => 'umum',
            'prioritas' => 'normal',
        ])
        ->assertSessionHasErrors(['rt_id']);

    $pengumuman = ($this->buatPengumuman)();

    $this->actingAs($this->ketuaRw)
        ->patch(route('pengumuman.toggle-pin', $pengumuman))
        ->assertRedirect();
    expect($pengumuman->fresh()->is_pinned)->toBeTrue();

    $this->actingAs($this->ketuaRw)
        ->patch(route('pengumuman.toggle-publish', $pengumuman))
        ->assertRedirect();
    expect($pengumuman->fresh()->is_published)->toBeFalse();

    $this->actingAs($this->ketuaRw)
        ->put(route('pengumuman.update', $pengumuman), [
            'rt_id' => $this->rtDua->id,
            'judul' => 'Judul Pengumuman Diperbarui',
            'isi' => 'Isi pengumuman terbaru setelah diperiksa oleh pengurus RW.',
            'kategori' => 'keamanan',
            'prioritas' => 'darurat',
            'is_pinned' => '1',
            'is_public' => '0',
            'is_published' => '1',
        ])
        ->assertRedirect(route('pengumuman.index'));

    $pengumuman->refresh();
    expect($pengumuman->judul)->toBe('Judul Pengumuman Diperbarui')
        ->and($pengumuman->rt_id)->toBe($this->rtDua->id)
        ->and($pengumuman->prioritas)->toBe('darurat')
        ->and($pengumuman->is_public)->toBeFalse()
        ->and($pengumuman->is_published)->toBeTrue();

    $this->actingAs($this->ketuaRw)
        ->delete(route('pengumuman.destroy', $pengumuman))
        ->assertRedirect(route('pengumuman.index'));

    expect(Pengumuman::find($pengumuman->id))->toBeNull();
});

test('asisten cs membaca pengumuman aktif sesuai wilayah dan tidak membocorkan pengumuman internal ke tamu', function () {
    config()->set('services.gemini.keys', []);

    ($this->buatPengumuman)([
        'judul' => 'Jadwal Fogging Besok Pagi',
        'isi' => 'Warga diminta menutup makanan saat petugas melakukan fogging pukul 07.00 WIB.',
        'is_public' => true,
        'prioritas' => 'penting',
    ]);
    ($this->buatPengumuman)([
        'judul' => 'Password Gerbang Baru Internal',
        'isi' => 'Informasi internal ini hanya untuk warga terverifikasi yang login.',
        'is_public' => false,
        'is_pinned' => true,
        'prioritas' => 'darurat',
    ]);

    $guestResp = $this->postJson(route('assistant.chat'), [
        'message' => 'Ada pengumuman atau jadwal fogging terbaru gak?',
    ])->assertOk();

    expect($guestResp->json('reply'))
        ->toContain('Jadwal Fogging Besok Pagi')
        ->not->toContain('Password Gerbang Baru Internal');
});
