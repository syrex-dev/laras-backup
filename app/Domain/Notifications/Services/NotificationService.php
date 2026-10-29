<?php

namespace App\Domain\Notifications\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Kirim notifikasi internal dalam aplikasi (FR-NTF-01).
     */
    public function notifyUser(User $user, string $title, string $message, ?string $actionUrl = null): void
    {
        Log::info("Notification sent to User [{$user->id} - {$user->nama}]: {$title} - {$message}");
    }

    /**
     * Broadcast pengingat disposisi mendekati tenggat (FR-DSP-05).
     */
    public function sendDisposisiReminder(User $recipient, string $suratPerihal, string $deadline): void
    {
        $this->notifyUser(
            $recipient,
            'Pengingat Disposisi',
            "Disposisi untuk surat '{$suratPerihal}' memiliki tenggat waktu: {$deadline}."
        );
    }
}
