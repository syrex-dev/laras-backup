<?php

namespace App\Domain\Disposisi\Notifications;

use App\Domain\Disposisi\Models\DisposisiRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisposisiOverdueEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DisposisiRecipient $recipient
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $disposisi = $this->recipient->disposisi;
        $suratMasuk = $disposisi->suratMasuk;
        $recipientName = $this->recipient->display_name;

        return [
            'type' => 'disposisi_overdue_escalation',
            'disposisi_id' => $disposisi->id,
            'recipient_id' => $this->recipient->id,
            'surat_masuk_id' => $suratMasuk?->id,
            'nomor_agenda' => $suratMasuk?->nomor_agenda,
            'perihal' => $suratMasuk?->perihal,
            'penerima_terlambat' => $recipientName,
            'tenggat_waktu' => $disposisi->tenggat_waktu?->toDateString(),
            'title' => 'Eskalasi: Keterlambatan Tindak Lanjut Disposisi',
            'message' => "Tindak lanjut disposisi naskah '{$suratMasuk?->perihal}' oleh {$recipientName} telah melewati tenggat waktu ({$disposisi->tenggat_waktu?->format('d/m/Y')}).",
            'url' => route('disposisi.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $disposisi = $this->recipient->disposisi;
        $suratMasuk = $disposisi->suratMasuk;
        $recipientName = $this->recipient->display_name;

        return (new MailMessage)
            ->subject("[ESKALASI KETERLAMBATAN] Disposisi Naskah: {$suratMasuk?->perihal}")
            ->greeting("Yth. {$notifiable->name},")
            ->line("Pemberitahuan eskalasi keterlambatan tindak lanjut disposisi naskah dinas:")
            ->line("**Agenda:** {$suratMasuk?->nomor_agenda}")
            ->line("**Perihal:** {$suratMasuk?->perihal}")
            ->line("**Penerima Bertugas:** {$recipientName}")
            ->line("**Tenggat Waktu:** {$disposisi->tenggat_waktu?->format('d/m/Y')}")
            ->line("**Status Saat Ini:** {$this->recipient->status}")
            ->action('Periksa Progres Disposisi', url('/disposisi'))
            ->line('Naskah belum diselesaikan oleh penerima meskipun batas waktu telah terlewati.');
    }
}
