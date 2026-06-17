<?php

namespace App\Notifications;

use App\Models\PeminjamanAset;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PeminjamanDitolak extends Notification
{
    use Queueable;

    public function __construct(protected PeminjamanAset $peminjaman)
    {
        $this->peminjaman->loadMissing('aset');
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
            'alasan' => $this->peminjaman->catatan_pengurus,
            'pesan' => 'Peminjaman ' . $this->peminjaman->aset->nama . ' ditolak.',
            'url' => '/peminjaman-aset/' . $this->peminjaman->id,
        ];
    }
}
