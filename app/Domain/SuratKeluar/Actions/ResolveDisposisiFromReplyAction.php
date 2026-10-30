<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiFollowup;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveDisposisiFromReplyAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Menandai tindak lanjut disposisi selesai berdasarkan surat keluar balasan yang telah bernomor resmi
     */
    public function execute(
        User $actor,
        SuratKeluar $suratKeluar,
        ?string $catatanKonfirmasi = null
    ): DisposisiRecipient {
        if (! $suratKeluar->isBernomor()) {
            throw ValidationException::withMessages([
                'surat_keluar' => "Tindak lanjut hanya dapat diselesaikan apabila surat balasan telah memiliki nomor resmi (status 'bernomor').",
            ]);
        }

        // Cari relasi penerima disposisi asal
        $recipient = $suratKeluar->disposisiRecipient
            ?: DisposisiRecipient::where('surat_keluar_id', $suratKeluar->id)->first()
            ?: (! empty($suratKeluar->metadata['reply_to_disposisi_recipient_id'])
                ? DisposisiRecipient::find($suratKeluar->metadata['reply_to_disposisi_recipient_id'])
                : null);

        if (! $recipient) {
            throw ValidationException::withMessages([
                'disposisi' => 'Surat keluar ini tidak terhubung dengan lembar disposisi manapun.',
            ]);
        }

        return DB::transaction(function () use ($actor, $suratKeluar, $recipient, $catatanKonfirmasi) {
            $catatanSelesai = $catatanKonfirmasi
                ?: "Tindak lanjut diselesaikan melalui surat balasan resmi nomor {$suratKeluar->nomor_surat}";

            $recipient->update([
                'status' => DisposisiRecipient::STATUS_SELESAI,
                'selesai_pada' => now(),
                'catatan_tindak_lanjut' => $catatanSelesai,
                'surat_keluar_id' => $suratKeluar->id,
            ]);

            // Catat riwayat tindak lanjut (followup)
            if (class_exists(DisposisiFollowup::class)) {
                DisposisiFollowup::create([
                    'disposisi_recipient_id' => $recipient->id,
                    'user_id' => $actor->id,
                    'status_perubahan' => DisposisiRecipient::STATUS_SELESAI,
                    'catatan' => $catatanSelesai,
                    'created_at' => now(),
                ]);
            }

            // Periksa apakah semua penerima di disposisi induk telah selesai
            $parentDisposisi = $recipient->disposisi;
            if ($parentDisposisi) {
                $hasUnfinished = $parentDisposisi->recipients()
                    ->whereNotIn('status', [
                        DisposisiRecipient::STATUS_SELESAI,
                        DisposisiRecipient::STATUS_DITOLAK,
                        DisposisiRecipient::STATUS_DIKEMBALIKAN,
                    ])
                    ->exists();

                if (! $hasUnfinished) {
                    $parentDisposisi->update([
                        'status' => Disposisi::STATUS_SELESAI,
                        'selesai_pada' => now(),
                    ]);
                }
            }

            $this->auditLogger->log(
                'disposisi.resolve_by_reply',
                $suratKeluar,
                "Menandai tindak lanjut disposisi #{$recipient->id} selesai via surat balasan no. {$suratKeluar->nomor_surat}",
                causer: $actor
            );

            return $recipient->fresh(['disposisi', 'suratKeluar', 'followups']);
        });
    }
}
