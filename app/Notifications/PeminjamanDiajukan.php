<?php

namespace App\Notifications;

use App\Models\PeminjamanAset;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PeminjamanDiajukan extends Notification
{
    use Queueable;

    public function __construct(protected PeminjamanAset $peminjaman)
    {
        $this->peminjaman->loadMissing(['aset', 'pemohon']);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'peminjaman_id' => $this->peminjaman->id,
            'aset_nama' => $this->peminjaman->aset->nama,
            'pemohon_nama' => $this->peminjaman->pemohon->name,
            'tanggal_mulai' => $this->peminjaman->tanggal_mulai->format('d M Y'),
            'tanggal_selesai' => $this->peminjaman->tanggal_selesai->format('d M Y'),
            'keperluan' => $this->peminjaman->keperluan,
            'pesan' => 'Pengajuan peminjaman baru: ' . $this->peminjaman->aset->nama
                . ' oleh ' . $this->peminjaman->pemohon->name,
            'url' => '/peminjaman-aset/' . $this->peminjaman->id,
        ];
    }
}
