<?php

namespace App\Http\Controllers;

use App\Models\ProdukUmkm;
use App\Models\Umkm;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WelcomeController extends Controller
{
    /**
     * Tampilkan halaman landing publik beserta katalog UMKM yang sudah disetujui.
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

        return view('welcome', compact('publicUmkms', 'publicProduks', 'umkmCategories'));
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
