<?php

namespace App\Http\Controllers;

use App\Models\PenarikanSampah;
use App\Models\PenjualanSampah;
use App\Models\SaldoSampah;
use App\Models\Rw;
use App\Models\SetoranSampah;
use App\Models\TransaksiSampah;
use App\Models\User;
use Illuminate\Http\Request;

class BankSampahController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);
        $canManage = $user->hasPermission('manage-bank-sampah');

        $statistik = [
            'total_saldo_beredar' => (int) SaldoSampah::where('rw_id', $rwId)->sum('saldo'),
            'total_setoran_bulan_ini' => (int) SetoranSampah::where('rw_id', $rwId)
                ->where('status', 'diverifikasi')
                ->whereBetween('verified_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('nilai'),
            'total_warga_aktif' => SetoranSampah::where('rw_id', $rwId)
                ->where('status', 'diverifikasi')
                ->distinct('warga_id')
                ->count('warga_id'),
            'penarikan_menunggu' => PenarikanSampah::where('rw_id', $rwId)
                ->where('status', 'menunggu')
                ->count(),
            'kas_bank_sampah' => (int) PenjualanSampah::where('rw_id', $rwId)->sum('total'),
        ];

        $setoranMenunggu = collect();
        $saldoSaya = null;
        $riwayatSaya = collect();

        if ($canManage) {
            $setoranMenunggu = SetoranSampah::with(['warga.rt', 'jenisSampah'])
                ->where('rw_id', $rwId)
                ->where('status', 'menunggu')
                ->latest()
                ->limit(10)
                ->get();
        } else {
            $saldoSaya = SaldoSampah::getOrCreate($user, $rwId);
            $riwayatSaya = TransaksiSampah::where('rw_id', $rwId)
                ->where('warga_id', $user->id)
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('bank_sampah.index', compact(
            'statistik',
            'setoranMenunggu',
            'saldoSaya',
            'riwayatSaya',
            'canManage'
        ));
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($this->isRwLevelBankSampahOperator($user) || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    private function isRwLevelBankSampahOperator(User $user): bool
    {
        return $user->isRwOfficial() || $user->role_name === 'petugas_bank_sampah';
    }
}
