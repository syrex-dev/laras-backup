<?php

namespace App\Domain\SuratKeluar\Notifications;

use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuratKeluarApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SuratKeluar $suratKeluar,
        public ?SuratKeluarApproval $approval = null,
        public string $event = 'turn_started',
        public ?string $catatan = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $roleName = match ($this->approval?->role_type) {
            SuratKeluarApproval::ROLE_PARAF => 'Paraf Naskah',
            SuratKeluarApproval::ROLE_PENANDATANGAN => 'Penandatanganan',
            default => 'Pemeriksaan Naskah',
        };

        $title = match ($this->event) {
            'turn_started' => "Giliran {$roleName} Surat Keluar",
            'revision_requested' => "Draf Surat Keluar Perlu Revisi",
            'rejected' => "Draf Surat Keluar Ditolak",
            'approved' => "Draf Surat Keluar Telah Disetujui",
            default => "Pembaruan Alur Persetujuan Surat Keluar",
        };

        $message = match ($this->event) {
            'turn_started' => "Draf '{$this->suratKeluar->perihal}' kini menunggu {$roleName} dari Anda.",
            'revision_requested' => "Pemeriksa meminta revisi atas draf '{$this->suratKeluar->perihal}': \"{$this->catatan}\"",
            'rejected' => "Draf '{$this->suratKeluar->perihal}' ditolak dengan alasan: \"{$this->catatan}\"",
            'approved' => "Draf '{$this->suratKeluar->perihal}' telah disetujui penuh oleh penandatangan.",
            default => "Status naskah dinas: {$this->suratKeluar->status}",
        };

        return [
            'type' => 'surat_keluar_approval',
            'surat_keluar_id' => $this->suratKeluar->id,
            'approval_id' => $this->approval?->id,
            'event' => $this->event,
            'step_order' => $this->approval?->step_order,
            'perihal' => $this->suratKeluar->perihal,
            'title' => $title,
            'message' => $message,
            'catatan' => $this->catatan,
            'url' => "/surat-keluar/{$this->suratKeluar->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleName = match ($this->approval?->role_type) {
            SuratKeluarApproval::ROLE_PARAF => 'Paraf Naskah',
            SuratKeluarApproval::ROLE_PENANDATANGAN => 'Penandatanganan',
            default => 'Pemeriksaan Naskah',
        };

        $subject = match ($this->event) {
            'turn_started' => "[E-Office] Giliran {$roleName}: {$this->suratKeluar->perihal}",
            'revision_requested' => "[E-Office] Perlu Revisi: {$this->suratKeluar->perihal}",
            'rejected' => "[E-Office] Ditolak: {$this->suratKeluar->perihal}",
            'approved' => "[E-Office] Disetujui: {$this->suratKeluar->perihal}",
            default => "[E-Office] Status Surat Keluar: {$this->suratKeluar->perihal}",
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Yth. {$notifiable->name},")
            ->line("Pemberitahuan terkait naskah dinas keluar di sistem E-Office:")
            ->line("**Perihal:** {$this->suratKeluar->perihal}")
            ->line("**Tujuan:** {$this->suratKeluar->tujuan}");

        if ($this->catatan) {
            $mail->line("**Catatan/Alasan:** {$this->catatan}");
        }

        return $mail->action('Buka Naskah Dinas', url("/surat-keluar/{$this->suratKeluar->id}"))
            ->line('Terima kasih atas kerja sama Anda.');
    }
}
