<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdukUmkm extends Model
{
    protected $fillable = [
        'umkm_id',
        'nama_produk',
        'deskripsi',
        'harga',
        'satuan_harga',
        'foto',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function getHargaFormattedAttribute(): string
    {
        if (is_null($this->harga)) {
            return 'Hubungi Penjual';
        }

        $formatted = 'Rp '.number_format($this->harga, 0, ',', '.');

        if ($this->satuan_harga) {
            $formatted .= ' / '.$this->satuan_harga;
        }

        return $formatted;
    }

    public function getWhatsappOrderUrlAttribute(): string
    {
        return $this->generateWhatsappOrderUrl();
    }

    public function generateWhatsappOrderUrl(int $qty = 1, ?string $catatan = null): string
    {
        $nomorBersih = preg_replace('/\D+/', '', $this->umkm->nomor_whatsapp ?? '') ?? '';

        if (str_starts_with($nomorBersih, '0')) {
            $nomorBersih = '62'.substr($nomorBersih, 1);
        }

        $user = auth()->user();
        $namaPembeli = $user?->name ?? 'Warga';
        $rtPembeli = $user?->rt?->name ? 'RT '.$user->rt->name : 'Lingkungan RW';
        $namaUsaha = $this->umkm->nama_usaha ?? 'Usaha Warga';
        $hargaTeks = $this->harga_formatted;
        $satuan = $this->satuan_harga ? ' '.$this->satuan_harga : ' item';

        $totalTeks = '';
        if (! is_null($this->harga) && $qty > 1) {
            $totalHarga = $this->harga * $qty;
            $totalTeks = ' (Estimasi: Rp '.number_format($totalHarga, 0, ',', '.').')';
        }

        $pesan = "Halo {$namaUsaha}, perkenalkan saya {$namaPembeli} ({$rtPembeli}).\n\n".
                 "Saya melihat etalase produk di Smart RW dan tertarik untuk memesan:\n".
                 "• Produk: *{$this->nama_produk}*\n".
                 "• Harga: {$hargaTeks}\n".
                 "• Jumlah: {$qty}{$satuan}{$totalTeks}\n";

        if ($catatan) {
            $pesan .= "• Catatan: {$catatan}\n";
        }

        $pesan .= "\nApakah produk ini masih tersedia untuk dipesan? Terima kasih! 🙏";

        return 'https://wa.me/'.$nomorBersih.'?text='.rawurlencode($pesan);
    }
}
