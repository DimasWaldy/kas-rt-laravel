<?php

namespace App\Http\Controllers;

use App\Models\Rumah;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanIuranController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->computeLaporanIuranData($request);

        $tagihans = (clone $data['query'])->orderBy('rumah_id')->paginate(25)->withQueryString();

        return view('laporan_iuran.index', array_merge($data, [
            'tagihans' => $tagihans,
        ]));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->hasPermission('export-finance') || $request->user()->canManageFinance(), 403);

        $data = $this->computeLaporanIuranData($request);

        $tagihans = (clone $data['query'])->orderBy('rumah_id')->get();

        return view('laporan_iuran.export', array_merge($data, [
            'tagihans' => $tagihans,
        ]));
    }

    private function computeLaporanIuranData(Request $request): array
    {
        $bulan = $request->filled('bulan') ? (int) $request->query('bulan') : now()->month;
        $tahun = $request->filled('tahun') ? (int) $request->query('tahun') : now()->year;
        $statusFilter = $request->query('status', 'semua');
        $rumahId = $request->filled('rumah_id') ? (int) $request->query('rumah_id') : null;

        $baseQuery = Tagihan::with(['user.warga', 'rumah', 'rt:id,name', 'verifier:id,name'])
            ->visibleTo($request->user())
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($rumahId) {
            $baseQuery->where('rumah_id', $rumahId);
        }

        // Hitung statistik sebelum filter status diterapkan agar metrik rekapitulasi tetap akurat
        $totalTagihan = (int) (clone $baseQuery)->count();
        $totalNominal = (int) (clone $baseQuery)->sum('total');
        $totalLunas = (int) (clone $baseQuery)->where('status', 'lunas')->sum('total');
        $totalTunggakan = (int) (clone $baseQuery)->where('status', '!=', 'lunas')->sum('total');
        $countLunas = (int) (clone $baseQuery)->where('status', 'lunas')->count();
        $countBelumLunas = max(0, $totalTagihan - $countLunas);
        $countPending = (int) (clone $baseQuery)->whereIn('status', ['pending_transfer', 'pending_offline'])->count();
        $persentaseLunas = $totalTagihan > 0 ? round(($countLunas / $totalTagihan) * 100, 1) : 0;

        // Terapkan filter status pada query data
        $query = clone $baseQuery;
        if ($statusFilter === 'lunas') {
            $query->where('status', 'lunas');
        } elseif ($statusFilter === 'belum_bayar') {
            $query->whereIn('status', ['belum_bayar', 'failed']);
        } elseif ($statusFilter === 'pending') {
            $query->whereIn('status', ['pending_transfer', 'pending_offline']);
        }

        $bulanList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanList[$i] = Carbon::create(null, $i)->translatedFormat('F');
        }
        $tahunList = range(now()->year - 3, now()->year + 1);

        $rumahList = Rumah::visibleTo($request->user())
            ->orderBy('kode_rumah')
            ->get(['id', 'kode_rumah', 'alamat']);

        $periodeNama = Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F Y');

        $user = $request->user();
        $rtLabel = $user->rt ? 'RT '.$user->rt->name : ($user->isRwOfficial() ? 'RW Lingkungan' : 'Seluruh Wilayah');

        return compact(
            'bulan',
            'tahun',
            'statusFilter',
            'rumahId',
            'periodeNama',
            'rtLabel',
            'totalTagihan',
            'totalNominal',
            'totalLunas',
            'totalTunggakan',
            'countLunas',
            'countBelumLunas',
            'countPending',
            'persentaseLunas',
            'bulanList',
            'tahunList',
            'rumahList',
            'query'
        );
    }
}
