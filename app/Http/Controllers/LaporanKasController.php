<?php

namespace App\Http\Controllers;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class LaporanKasController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);

        $tanggalMulai = Carbon::parse($validated['tanggal_mulai'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $tanggalSelesai = Carbon::parse($validated['tanggal_selesai'] ?? now()->endOfMonth()->toDateString())->endOfDay();
        $kategoriFilter = $validated['kategori'] ?? '';

        $saldoAwal = (int) KasMasuk::whereDate('tanggal', '<', $tanggalMulai->toDateString())->sum('jumlah')
            - (int) KasKeluar::whereDate('tanggal', '<', $tanggalMulai->toDateString())->sum('jumlah');

        $kasMasuk = KasMasuk::with(['user', 'tagihan'])
            ->whereBetween('tanggal', [$tanggalMulai->toDateString(), $tanggalSelesai->toDateString()])
            ->latest('tanggal')
            ->get();

        $kasKeluar = KasKeluar::whereBetween('tanggal', [$tanggalMulai->toDateString(), $tanggalSelesai->toDateString()])
            ->latest('tanggal')
            ->get();

        $riwayat = $kasMasuk
            ->map(fn ($item) => [
                'tanggal' => $item->tanggal,
                'tipe' => 'Masuk',
                'kategori' => $this->kategoriMasuk($item),
                'keterangan' => $item->keterangan,
                'debit' => (int) $item->jumlah,
                'kredit' => 0,
                'user' => $item->user?->name ?? 'Anonim',
            ])
            ->concat($kasKeluar->map(fn ($item) => [
                'tanggal' => $item->tanggal,
                'tipe' => 'Keluar',
                'kategori' => $this->kategoriKeluar($item),
                'keterangan' => $item->keterangan,
                'debit' => 0,
                'kredit' => (int) $item->jumlah,
                'user' => '-',
            ]))
            ->when($kategoriFilter, fn ($items) => $items->where('kategori', $kategoriFilter))
            ->sortByDesc('tanggal')
            ->values();

        $totalMasuk = (int) $riwayat->sum('debit');
        $totalKeluar = (int) $riwayat->sum('kredit');
        $saldoPeriode = $totalMasuk - $totalKeluar;
        $saldoAkhir = $saldoAwal + $saldoPeriode;
        $kategoriOptions = $riwayat->pluck('kategori')
            ->merge($kasMasuk->map(fn ($item) => $this->kategoriMasuk($item)))
            ->merge($kasKeluar->map(fn ($item) => $this->kategoriKeluar($item)))
            ->unique()
            ->sort()
            ->values();
        $ringkasanKategori = $riwayat
            ->groupBy('kategori')
            ->map(fn ($items, $kategori) => [
                'kategori' => $kategori,
                'masuk' => (int) $items->sum('debit'),
                'keluar' => (int) $items->sum('kredit'),
                'saldo' => (int) $items->sum('debit') - (int) $items->sum('kredit'),
                'transaksi' => $items->count(),
            ])
            ->values();
        $periodeLabel = $tanggalMulai->translatedFormat('d F Y') . ' - ' . $tanggalSelesai->translatedFormat('d F Y');

        return view('laporan_kas.index', compact(
            'tanggalMulai',
            'tanggalSelesai',
            'kategoriFilter',
            'kategoriOptions',
            'kasMasuk',
            'kasKeluar',
            'saldoAwal',
            'totalMasuk',
            'totalKeluar',
            'saldoPeriode',
            'saldoAkhir',
            'ringkasanKategori',
            'riwayat',
            'periodeLabel',
        ));
    }

    private function kategoriMasuk(KasMasuk $item): string
    {
        if ($item->tagihan_id) {
            return 'Pembayaran Iuran';
        }

        $text = strtolower($item->keterangan);

        return match (true) {
            str_contains($text, 'saldo awal') => 'Saldo Awal',
            str_contains($text, 'donasi') || str_contains($text, 'bantuan') || str_contains($text, 'sumbangan') => 'Donasi/Bantuan',
            default => 'Pemasukan Manual',
        };
    }

    private function kategoriKeluar(KasKeluar $item): string
    {
        $text = strtolower($item->keterangan);

        return match (true) {
            str_contains($text, 'bersih') || str_contains($text, 'sampah') || str_contains($text, 'kebersihan') => 'Operasional Kebersihan',
            str_contains($text, 'aman') || str_contains($text, 'satpam') || str_contains($text, 'keamanan') => 'Operasional Keamanan',
            str_contains($text, 'rapat') || str_contains($text, 'konsumsi') || str_contains($text, 'acara') || str_contains($text, 'maulid') || str_contains($text, 'gotong royong') => 'Kegiatan Warga',
            str_contains($text, 'lampu') || str_contains($text, 'perbaikan') || str_contains($text, 'jalan') || str_contains($text, 'fasilitas') => 'Perbaikan Fasilitas',
            default => 'Pengeluaran Operasional',
        };
    }
}
