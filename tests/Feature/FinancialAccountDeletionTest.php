<?php

use App\Models\KasMasuk;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiSimpanan;
use App\Models\User;
use App\Support\FinancialHistory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

test('self deletion preserves cooperative loan and installments in every status', function (string $status) {
    $user = User::factory()->create();
    $loan = KoperasiPinjam::create([
        'user_id' => $user->id, 'amount' => 1000000, 'tenor_months' => 10,
        'service_fee_percentage' => 2, 'service_fee_amount' => 200000,
        'remaining_amount' => $status === 'lunas' ? 0 : 1200000, 'status' => $status,
    ]);
    $installment = $loan->angsurans()->create([
        'amount' => 120000, 'paid_at' => now(), 'status' => 'terverifikasi',
    ]);
    $this->actingAs($user)->get('/profile')->assertOk();
    $this->from('/profile')->delete('/profile', ['password' => 'password', 'confirmation' => 'konfirmasi'])
        ->assertRedirect('/profile')->assertSessionHasErrorsIn('userDeletion', 'account');
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('koperasi_pinjams', ['id' => $loan->id, 'status' => $status]);
    $this->assertDatabaseHas('koperasi_angsurans', ['id' => $installment->id]);
    expect(session('errors')->getBag('userDeletion')->first('account'))->toBe(FinancialHistory::DELETION_MESSAGE);
})->with(['menunggu_persetujuan', 'disetujui', 'ditolak', 'lunas']);

test('model deletion cannot bypass retention of cooperative savings', function () {
    $user = User::factory()->create();
    $saving = KoperasiSimpanan::create([
        'user_id' => $user->id, 'type' => 'wajib', 'amount' => 50000,
        'transaction_date' => now(), 'status' => 'terverifikasi',
    ]);
    expect(fn () => $user->delete())->toThrow(ValidationException::class);
    $this->assertDatabaseHas('koperasi_simpanans', ['id' => $saving->id]);
});

test('soft deleted cash history still blocks account deletion', function () {
    $user = User::factory()->create();
    $cash = KasMasuk::create([
        'user_id' => $user->id, 'jumlah' => 50000, 'tanggal' => now(), 'keterangan' => 'Audit',
    ]);
    $cash->delete();
    $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'password', 'confirmation' => 'konfirmasi'])
        ->assertSessionHasErrorsIn('userDeletion', 'account');
    expect($user->fresh())->not->toBeNull();
    $this->assertSoftDeleted('kas_masuks', ['id' => $cash->id]);
});

test('database rejects deletion bypassing model events when a loan exists', function () {
    $user = User::factory()->create();
    $loan = KoperasiPinjam::create([
        'user_id' => $user->id, 'amount' => 50000, 'tenor_months' => 1,
        'service_fee_percentage' => 2, 'service_fee_amount' => 1000,
        'remaining_amount' => 51000, 'status' => 'disetujui',
    ]);
    expect(fn () => DB::table('users')->where('id', $user->id)->delete())->toThrow(QueryException::class);
    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $this->assertDatabaseHas('koperasi_pinjams', ['id' => $loan->id]);
});

test('all financial owner foreign keys restrict user deletion', function (string $table, string $column) {
    $foreign = collect(Schema::getForeignKeys($table))
        ->first(fn ($key) => $key['columns'] === [$column] && $key['foreign_table'] === 'users');
    expect($foreign)->not->toBeNull();
    expect(strtolower($foreign['on_delete']))->toBe('restrict');
})->with([
    ['kas', 'user_id'], ['kas_masuks', 'user_id'], ['tagihans', 'user_id'],
    ['koperasi_simpanans', 'user_id'], ['koperasi_pinjams', 'user_id'],
    ['saldo_sampahs', 'warga_id'], ['transaksi_sampahs', 'warga_id'],
    ['setoran_sampahs', 'warga_id'], ['penarikan_sampahs', 'warga_id'],
    ['penukaran_hadiahs', 'warga_id'], ['penjualan_sampahs', 'petugas_id'],
]);
