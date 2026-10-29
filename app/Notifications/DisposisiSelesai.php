<?php

namespace App\Notifications;

use App\Models\Disposisi;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DisposisiSelesai extends Notification
{
    use Queueable;

    protected $disposisi;

    public function __construct(Disposisi $disposisi)
    {
        $this->disposisi = $disposisi;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'disposisi_id' => $this->disposisi->id,
            'message' => 'Disposisi untuk surat '.$this->disposisi->suratMasuk->nomor_surat.' telah diselesaikan oleh '.$this->disposisi->penerima->nama,
            'url' => route('disposisi.show', $this->disposisi->id),
        ];
    }
}
