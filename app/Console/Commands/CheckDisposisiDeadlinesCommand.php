<?php

namespace App\Console\Commands;

use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Disposisi\Notifications\DisposisiDeadlineReminderNotification;
use App\Domain\Disposisi\Notifications\DisposisiOverdueEscalationNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDisposisiDeadlinesCommand extends Command
{
    protected $signature = 'app:check-disposisi-deadlines';

    protected $description = 'Memeriksa tenggat waktu disposisi, mengirim pengingat H-1, dan mengeskalasi keterlambatan ke atasan penerima secara idempoten.';

    public function handle(): int
    {
        $today = now()->toDateString();
        $this->info("Menjalankan pemeriksaan tenggat waktu disposisi per tanggal {$today}...");

        $recipients = DisposisiRecipient::query()
            ->with([
                'disposisi.suratMasuk',
                'disposisi.pemberi',
                'recipientUser',
                'delegatedUser',
                'recipientPosition',
            ])
            ->whereNotIn('status', [
                DisposisiRecipient::STATUS_SELESAI,
                DisposisiRecipient::STATUS_DITOLAK,
                DisposisiRecipient::STATUS_DIKEMBALIKAN,
            ])
            ->whereHas('disposisi', function ($q) {
                $q->whereNotNull('tenggat_waktu');
            })
            ->get();

        $remindersSent = 0;
        $escalationsSent = 0;

        foreach ($recipients as $recipient) {
            $effectiveUser = $recipient->effectiveUser();
            if (! $effectiveUser) {
                continue;
            }

            // 1. Pengingat Mendekati Tenggat (H-1 sampai Hari H)
            if ($recipient->isDueSoon(1)) {
                $alreadyReminded = DB::table('notifications')
                    ->where('notifiable_id', $effectiveUser->id)
                    ->whereDate('created_at', $today)
                    ->where('data', 'like', '%"type":"disposisi_deadline_reminder"%')
                    ->where('data', 'like', '%"recipient_id":' . $recipient->id . '%')
                    ->exists();

                if (! $alreadyReminded) {
                    $effectiveUser->notify(new DisposisiDeadlineReminderNotification($recipient));
                    $remindersSent++;
                    $this->line("  [PENGINGAT] Dikirim ke {$effectiveUser->name} untuk naskah '{$recipient->disposisi->suratMasuk?->perihal}'");
                }
            }

            // 2. Eskalasi Keterlambatan ke Atasan / Pemberi Disposisi
            if ($recipient->isOverdue()) {
                $supervisor = $recipient->disposisi->pemberi ?? User::role('Pimpinan')->first();

                if ($supervisor && $supervisor->id !== $effectiveUser->id) {
                    $alreadyEscalated = DB::table('notifications')
                        ->where('notifiable_id', $supervisor->id)
                        ->whereDate('created_at', $today)
                        ->where('data', 'like', '%"type":"disposisi_overdue_escalation"%')
                        ->where('data', 'like', '%"recipient_id":' . $recipient->id . '%')
                        ->exists();

                    if (! $alreadyEscalated) {
                        $supervisor->notify(new DisposisiOverdueEscalationNotification($recipient));
                        $escalationsSent++;
                        $this->line("  [ESKALASI] Dikirim ke atasan {$supervisor->name} atas keterlambatan {$recipient->display_name}");
                    }
                }
            }
        }

        $this->info("Pemeriksaan selesai: {$remindersSent} pengingat dikirim, {$escalationsSent} eskalasi dikirim.");

        return Command::SUCCESS;
    }
}
