<?php

namespace App\Domain\Disposisi\Notifications;

use App\Domain\Disposisi\Models\DisposisiRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisposisiDeadlineReminderNotification extends Notification implements ShouldQueue
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

        return [
            'type' => 'disposisi_deadline_reminder',
            'disposisi_id' => $disposisi->id,
            'recipient_id' => $this->recipient->id,
            'surat_masuk_id' => $suratMasuk?->id,
            'nomor_agenda' => $suratMasuk?->nomor_agenda,
            'perihal' => $suratMasuk?->perihal,
            'tenggat_waktu' => $disposisi->tenggat_waktu?->toDateString(),
            'title' => 'Pengingat: Tenggat Waktu Disposisi Mendekat',
            'message' => "Tindak lanjut disposisi naskah '{$suratMasuk?->perihal}' memiliki tenggat waktu sampai tanggal {$disposisi->tenggat_waktu?->format('d/m/Y')}.",
            'url' => route('disposisi.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $disposisi = $this->recipient->disposisi;
        $suratMasuk = $disposisi->suratMasuk;

        return (new MailMessage)
            ->subject("[PENGINGAT TENGGAT] Disposisi Naskah: {$suratMasuk?->perihal}")
            ->greeting("Yth. {$notifiable->name},")
            ->line("Ini adalah pengingat otomatis bahwa tenggat waktu penyelesaian disposisi berikut sudah mendekat:")
            ->line("**Agenda:** {$suratMasuk?->nomor_agenda}")
            ->line("**Perihal:** {$suratMasuk?->perihal}")
            ->line("**Tenggat Waktu:** {$disposisi->tenggat_waktu?->format('d/m/Y')}")
            ->line("**Instruksi:** {$disposisi->instruksi}")
            ->action('Tindak Lanjuti Sekarang', url('/disposisi'))
            ->line('Harap segera memperbarui status penyelesaian atau mengunggah bukti tindak lanjut.');
    }
}
