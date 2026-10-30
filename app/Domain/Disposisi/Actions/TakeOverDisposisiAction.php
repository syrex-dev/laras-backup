<?php

namespace App\Domain\Disposisi\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Disposisi\Models\DisposisiFollowup;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TakeOverDisposisiAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Mengambil alih disposisi yang dipegang Plt kembali ke pejabat definitif atau pejabat yang ditunjuk
     */
    public function execute(
        User $actor,
        DisposisiRecipient $recipient,
        ?string $alasan = null,
        ?int $targetUserId = null
    ): DisposisiRecipient {
        if ($recipient->status === DisposisiRecipient::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'status' => 'Disposisi yang sudah berstatus selesai tidak dapat diambil alih.',
            ]);
        }

        return DB::transaction(function () use ($actor, $recipient, $alasan, $targetUserId) {
            $previousDelegatedUser = $recipient->delegatedUser;
            $newUserId = $targetUserId ?? $recipient->recipient_user_id;
            $targetUser = User::find($newUserId);

            $recipient->update([
                'is_delegated' => false,
                'delegated_user_id' => null,
                'recipient_user_id' => $newUserId,
            ]);

            $alasanText = $alasan ? " Alasan: {$alasan}" : '';
            $catatan = "Disposisi diambil alih oleh {$actor->name} dari pejabat Plt (" .
                ($previousDelegatedUser?->name ?? 'Plt') . ") dan dialihkan ke " .
                ($targetUser?->name ?? 'Pejabat Definitif') . ".{$alasanText}";

            DisposisiFollowup::create([
                'disposisi_recipient_id' => $recipient->id,
                'user_id' => $actor->id,
                'status_perubahan' => $recipient->status,
                'catatan' => $catatan,
            ]);

            $this->auditLogger->log(
                'disposisi.ambil_alih',
                $catatan,
                $recipient->disposisi,
                [
                    'recipient_id' => $recipient->id,
                    'actor_id' => $actor->id,
                    'previous_delegated_user_id' => $previousDelegatedUser?->id,
                    'new_user_id' => $newUserId,
                    'alasan' => $alasan,
                ]
            );

            return $recipient->fresh();
        });
    }
}
