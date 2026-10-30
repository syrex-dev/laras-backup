<?php

namespace App\Domain\Disposisi\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiAttachment;
use App\Domain\Disposisi\Models\DisposisiFollowup;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\SuratMasuk\Models\SuratMasukAttachment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowupDisposisiAction
{
    /**
     * Memperbarui status, menulis catatan tindak lanjut, dan mengunggah bukti penyelesaian disposisi.
     *
     * @param  User  $user
     * @param  DisposisiRecipient  $recipient
     * @param  array  $data
     * @param  UploadedFile|null  $attachment
     * @return DisposisiFollowup
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(
        User $user,
        DisposisiRecipient $recipient,
        array $data,
        ?UploadedFile $attachment = null
    ): DisposisiFollowup {
        // 1. Validasi wewenang: harus penerima sah (definitif atau Plt) atau Admin
        $effectiveUser = $recipient->effectiveUser();
        $isRecipient = $effectiveUser && $effectiveUser->id === $user->id;
        $isAdmin = $user->hasRole(['Admin Instansi', 'Admin']);

        if (! $isRecipient && ! $isAdmin && ! $user->can('disposisi.tindaklanjut')) {
            throw new AuthorizationException('Anda tidak memiliki wewenang untuk menindaklanjuti disposisi ini.');
        }

        // 2. Validasi status perubahan
        $status = $data['status'] ?? DisposisiRecipient::STATUS_DIPROSES;
        $validStatuses = [
            DisposisiRecipient::STATUS_DIPROSES,
            DisposisiRecipient::STATUS_SELESAI,
            DisposisiRecipient::STATUS_DITOLAK,
            DisposisiRecipient::STATUS_DIKEMBALIKAN,
        ];

        if (! in_array($status, $validStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Status tindak lanjut tidak valid.',
            ]);
        }

        $catatan = trim($data['catatan'] ?? '');
        if (empty($catatan)) {
            throw ValidationException::withMessages([
                'catatan' => 'Uraian catatan tindak lanjut wajib diisi.',
            ]);
        }

        // 3. Validasi kuota storage bila mengunggah berkas bukti penyelesaian
        if ($attachment) {
            $this->validateStorageQuota($attachment);
        }

        // 4. Eksekusi database transaction
        return DB::transaction(function () use ($user, $recipient, $data, $status, $catatan, $attachment) {
            // Update recipient
            $recipientUpdates = [
                'status' => $status,
                'catatan_tindak_lanjut' => $catatan,
            ];

            if ($recipient->dibaca_pada === null) {
                $recipientUpdates['dibaca_pada'] = now();
            }

            if ($status === DisposisiRecipient::STATUS_SELESAI) {
                $recipientUpdates['selesai_pada'] = now();
            }

            if (! empty($data['surat_keluar_id'])) {
                $recipientUpdates['surat_keluar_id'] = (int) $data['surat_keluar_id'];
            }

            $recipient->update($recipientUpdates);

            // Buat record riwayat tindak lanjut (DisposisiFollowup)
            $followup = DisposisiFollowup::create([
                'disposisi_recipient_id' => $recipient->id,
                'user_id' => $user->id,
                'catatan' => $catatan,
                'status_perubahan' => $status,
            ]);

            // Simpan lampiran bukti ke disk privat bila ada
            if ($attachment) {
                $originalName = $attachment->getClientOriginalName();
                $fileSize = $attachment->getSize();
                $mimeType = $attachment->getClientMimeType() ?: 'application/octet-stream';

                // Simpan ke direktori disk privat
                $storagePath = $attachment->store("private/disposisi/{$recipient->id}", 'local');

                DisposisiAttachment::create([
                    'disposisi_followup_id' => $followup->id,
                    'user_id' => $user->id,
                    'file_name' => $originalName,
                    'file_path' => $storagePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                ]);
            }

            // 5. Cek apakah seluruh penerima pada lembar disposisi ini telah selesai/mengembalikan
            $disposisi = $recipient->disposisi;
            if ($disposisi->isAllRecipientsFinished()) {
                $disposisi->update([
                    'status' => Disposisi::STATUS_SELESAI,
                    'selesai_pada' => now(),
                ]);

                // Cek apakah seluruh pohon disposisi surat masuk ini sudah tuntas
                $suratMasuk = $disposisi->suratMasuk;
                if ($suratMasuk) {
                    $allDisposisi = Disposisi::where('surat_masuk_id', $suratMasuk->id)->get();
                    $allFinished = $allDisposisi->every(fn ($d) => $d->isAllRecipientsFinished());

                    if ($allFinished && $suratMasuk->status !== 'dibatalkan') {
                        $suratMasuk->update(['status' => 'selesai']);
                    }
                }
            }

            // 6. Jejak Audit
            $pltInfo = $recipient->is_delegated ? " (selaku Plt {$recipient->recipientPosition?->name})" : '';
            AuditLogger::log(
                action: 'disposisi.followup',
                subject: $recipient,
                description: "Menindaklanjuti disposisi naskah: status {$status}{$pltInfo}",
                properties: [
                    'disposisi_id' => $disposisi->id,
                    'surat_masuk_id' => $disposisi->surat_masuk_id,
                    'status' => $status,
                    'catatan' => $catatan,
                    'has_attachment' => $attachment !== null,
                ],
                causer: $user
            );

            return $followup->load(['attachments', 'user']);
        });
    }

    /**
     * Memvalidasi batas kuota storage instansi
     */
    protected function validateStorageQuota(UploadedFile $file): void
    {
        $newBytes = $file->getSize();
        $license = LicenseState::current();

        $currentUsedBytes = (int) (
            SuratMasukAttachment::sum('file_size') +
            DisposisiAttachment::sum('file_size')
        );

        if (($currentUsedBytes + $newBytes) > $license->max_storage_bytes) {
            $maxGb = round($license->max_storage_bytes / 1073741824, 2);
            $usedMb = round($currentUsedBytes / 1048576, 2);

            throw ValidationException::withMessages([
                'lampiran' => "Kapasitas penyimpanan telah melebihi batas kuota lisensi ({$maxGb} GB). Pemakaian saat ini: {$usedMb} MB. Silakan hubungi admin instansi untuk memperluas kuota storage.",
            ]);
        }
    }
}
