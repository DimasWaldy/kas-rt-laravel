<?php

namespace App\Http\Controllers;

use App\Models\Balita;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Kegiatan;
use App\Models\ProdukUmkm;
use App\Models\Rt;
use App\Models\SetoranSampah;
use App\Models\Umkm;
use App\Models\Warga;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WelcomeController extends Controller
{
    /**
     * Tampilkan halaman landing publik beserta katalog UMKM, agenda kegiatan, dan statistik agregat RW.
     */
    public function index(): View
    {
        $publicUmkms = Umkm::query()
            ->visible()
            ->whereHas('rw', fn ($q) => $q->where('is_active', true))
            ->with([
                'rt:id,name',
                'jamOperasional',
                'produkUmkms' => fn ($q) => $q->where('is_available', true)->latest(),
            ])
            ->withCount(['produkUmkms' => fn ($q) => $q->where('is_available', true)])
            ->latest()
            ->get();

        $publicProduks = ProdukUmkm::query()
            ->with(['umkm.rt:id,name', 'umkm.jamOperasional'])
            ->whereHas('umkm', function ($q) {
                $q->visible()->whereHas('rw', fn ($rw) => $rw->where('is_active', true));
            })
            ->where('is_available', true)
            ->latest()
            ->get();

        $umkmCategories = Umkm::CATEGORIES;

        $upcomingKegiatanQuery = Kegiatan::query()
            ->whereHas('rw', fn ($q) => $q->where('is_active', true))
            ->whereNotIn('status', ['dibatalkan', 'selesai'])
            ->where(function ($q) {
                $q->where('tanggal_mulai', '>=', now()->startOfDay())
                    ->orWhere('tanggal_selesai', '>', now());
            });

        $agendaKegiatans = (clone $upcomingKegiatanQuery)
            ->with(['rt:id,name', 'rw:id,name'])
            ->withCount('hadirs')
            ->orderBy('tanggal_mulai')
            ->limit(6)
            ->get();

        $activeRtIds = Rt::query()
            ->where('is_active', true)
            ->whereHas('rw', fn ($q) => $q->where('is_active', true))
            ->pluck('id');

        $kasMasukScope = KasMasuk::query()
            ->where(function ($q) use ($activeRtIds) {
                $q->whereIn('rt_id', $activeRtIds)->orWhereNull('rt_id');
            });

        $kasKeluarScope = KasKeluar::query()
            ->where(function ($q) use ($activeRtIds) {
                $q->whereIn('rt_id', $activeRtIds)->orWhereNull('rt_id');
            });

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $totalKasMasuk = (int) (clone $kasMasukScope)->sum('jumlah');
        $totalKasKeluar = (int) (clone $kasKeluarScope)->sum('jumlah');
        $kasMasukBulanIni = (int) (clone $kasMasukScope)->whereBetween('tanggal', [$monthStart, $monthEnd])->sum('jumlah');
        $kasKeluarBulanIni = (int) (clone $kasKeluarScope)->whereBetween('tanggal', [$monthStart, $monthEnd])->sum('jumlah');

        $bankSampahQuery = SetoranSampah::query()
            ->where('status', 'diverifikasi')
            ->whereHas('rw', fn ($q) => $q->where('is_active', true));

        $rwStats = [
            'rt_aktif' => $activeRtIds->count(),
            'warga_terverifikasi' => Warga::query()
                ->where('status_verifikasi', 'terverifikasi')
                ->whereHas('user', fn ($q) => $q->whereIn('rt_id', $activeRtIds))
                ->count(),
            'umkm_aktif' => $publicUmkms->count(),
            'produk_aktif' => $publicProduks->count(),
            'agenda_mendatang' => (clone $upcomingKegiatanQuery)->count(),
            'saldo_kas' => $totalKasMasuk - $totalKasKeluar,
            'kas_masuk_bulan_ini' => $kasMasukBulanIni,
            'kas_keluar_bulan_ini' => $kasKeluarBulanIni,
            'periode_kas' => now()->translatedFormat('F Y'),
            'bank_sampah_kg' => round((float) (clone $bankSampahQuery)->sum('berat_aktual'), 1),
            'bank_sampah_setoran' => (clone $bankSampahQuery)->count(),
            'balita_posyandu' => Balita::query()
                ->where('is_active', true)
                ->whereHas('rw', fn ($q) => $q->where('is_active', true))
                ->count(),
        ];

        return view('welcome', compact('publicUmkms', 'publicProduks', 'umkmCategories', 'agendaKegiatans', 'rwStats'));
    }

    /**
     * Sajikan foto usaha UMKM berstatus approved untuk halaman publik tanpa login.
     */
    public function fotoUmkm(Umkm $umkm): StreamedResponse
    {
        $umkm->loadMissing('rw');

        abort_unless(
            $umkm->status === 'approved'
                && ($umkm->rw?->is_active ?? false)
                && $umkm->foto_usaha
                && Storage::disk('local')->exists($umkm->foto_usaha),
            404
        );

        return Storage::disk('local')->response($umkm->foto_usaha);
    }

    /**
     * Sajikan foto produk UMKM tersedia milik usaha approved untuk halaman publik.
     */
    public function fotoProduk(ProdukUmkm $produk): StreamedResponse
    {
        $produk->loadMissing('umkm.rw');

        abort_unless(
            $produk->is_available
                && $produk->umkm
                && $produk->umkm->status === 'approved'
                && ($produk->umkm->rw?->is_active ?? false)
                && $produk->foto
                && Storage::disk('local')->exists($produk->foto),
            404
        );

        return Storage::disk('local')->response($produk->foto);
    }
}
