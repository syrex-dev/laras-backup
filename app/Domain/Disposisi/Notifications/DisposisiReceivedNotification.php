<?php

namespace App\Domain\Disposisi\Notifications;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisposisiReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Disposisi $disposisi,
        public DisposisiRecipient $recipient
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $suratMasuk = $this->disposisi->suratMasuk;
        $pemberiName = $this->disposisi->pemberi?->name ?? 'Pimpinan';
        $pltText = $this->recipient->is_delegated ? " ({$this->recipient->delegation_label})" : '';

        return [
            'type' => 'disposisi_received',
            'disposisi_id' => $this->disposisi->id,
            'recipient_id' => $this->recipient->id,
            'surat_masuk_id' => $suratMasuk?->id,
            'nomor_agenda' => $suratMasuk?->nomor_agenda,
            'perihal' => $suratMasuk?->perihal,
            'pemberi' => $pemberiName,
            'instruksi' => $this->disposisi->instruksi,
            'prioritas' => $this->disposisi->prioritas,
            'tenggat_waktu' => $this->disposisi->tenggat_waktu?->toDateString(),
            'title' => "Disposisi Baru{$pltText}",
            'message' => "Anda menerima lembar disposisi dari {$pemberiName} terkait naskah '{$suratMasuk?->perihal}'",
            'url' => route('disposisi.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $suratMasuk = $this->disposisi->suratMasuk;
        $pemberiName = $this->disposisi->pemberi?->name ?? 'Pimpinan';
        $pltText = $this->recipient->is_delegated ? " ({$this->recipient->delegation_label})" : '';

        $mail = (new MailMessage)
            ->subject("[E-Office] Disposisi Baru: {$suratMasuk?->perihal}")
            ->greeting("Yth. {$notifiable->name}{$pltText},")
            ->line("Anda telah menerima disposisi naskah dinas baru dari {$pemberiName}.")
            ->line("**Nomor Agenda:** {$suratMasuk?->nomor_agenda}")
            ->line("**Perihal Surat:** {$suratMasuk?->perihal}")
            ->line("**Instruksi:** {$this->disposisi->instruksi}");

        if ($this->disposisi->catatan) {
            $mail->line("**Catatan Khusus:** {$this->disposisi->catatan}");
        }

        if ($this->disposisi->tenggat_waktu) {
            $mail->line("**Tenggat Waktu:** {$this->disposisi->tenggat_waktu->format('d/m/Y')}");
        }

        return $mail->action('Buka Lembar Disposisi', url('/disposisi'))
            ->line('Mohon segera menindaklanjuti naskah dinas sesuai instruksi pimpinan.');
    }
}
