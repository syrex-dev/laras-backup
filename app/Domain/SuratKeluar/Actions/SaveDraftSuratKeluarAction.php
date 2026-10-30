<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarAttachment;
use App\Domain\SuratKeluar\Models\SuratKeluarVersion;
use App\Domain\SuratMasuk\Models\SuratMasukAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveDraftSuratKeluarAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Menyimpan draf baru atau pembaruan draf dengan pencatatan versi otomatis
     */
    public function execute(
        User $actor,
        array $data,
        array $files = [],
        ?SuratKeluar $existing = null
    ): SuratKeluar {
        // 1. Cek kuota penyimpanan lisensi bila ada berkas lampiran yang diunggah
        $this->validateStorageQuota($files);

        return DB::transaction(function () use ($actor, $data, $files, $existing) {
            if ($existing) {
                return $this->updateDraft($actor, $existing, $data, $files);
            }

            return $this->createDraft($actor, $data, $files);
        });
    }

    protected function createDraft(User $actor, array $data, array $files): SuratKeluar
    {
        $suratKeluar = SuratKeluar::create([
            'template_id' => $data['template_id'] ?? null,
            'klasifikasi_id' => $data['klasifikasi_id'],
            'org_unit_id' => $data['org_unit_id'] ?? $actor->position?->org_unit_id,
            'user_id' => $actor->id,
            'penandatangan_id' => $data['penandatangan_id'] ?? null,
            'reply_to_surat_masuk_id' => $data['reply_to_surat_masuk_id'] ?? null,
            'tujuan' => $data['tujuan'],
            'perihal' => $data['perihal'],
            'isi_surat' => $data['isi_surat'] ?? '',
            'isi' => $data['isi_surat'] ?? '', // sinkronisasi kolom prototipe
            'sifat' => $data['sifat'] ?? SuratKeluar::SIFAT_BIASA,
            'status' => SuratKeluar::STATUS_DRAF,
            'status_approval' => SuratKeluar::STATUS_DRAF,
            'metadata' => $data['metadata'] ?? [],
            'qr_token' => Str::random(32),
            'lampiran' => $data['lampiran'] ?? null,
        ]);

        // Simpan versi 1 di surat_keluar_versions
        SuratKeluarVersion::create([
            'surat_keluar_id' => $suratKeluar->id,
            'user_id' => $actor->id,
            'version_number' => 1,
            'perihal' => $suratKeluar->perihal,
            'isi_surat' => $suratKeluar->isi_surat,
            'tujuan' => $suratKeluar->tujuan,
            'metadata' => $suratKeluar->metadata,
            'catatan_perubahan' => $data['catatan_perubahan'] ?? 'Draf awal naskah dinas disusun',
            'created_at' => now(),
        ]);

        // Simpan berkas lampiran
        $this->saveAttachments($suratKeluar, $files, $actor->id);

        $this->auditLogger->log(
            'surat_keluar.create_draft',
            "Menyusun draf surat keluar baru: '{$suratKeluar->perihal}'",
            $suratKeluar
        );

        return $suratKeluar->fresh(['versions', 'attachments', 'template', 'klasifikasi']);
    }

    protected function updateDraft(User $actor, SuratKeluar $existing, array $data, array $files): SuratKeluar
    {
        if (! $existing->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => "Surat keluar dengan status '{$existing->status}' tidak dapat diubah lagi.",
            ]);
        }

        $isSubstantialChange =
            $existing->isi_surat !== ($data['isi_surat'] ?? $existing->isi_surat) ||
            $existing->perihal !== ($data['perihal'] ?? $existing->perihal) ||
            $existing->tujuan !== ($data['tujuan'] ?? $existing->tujuan);

        // Jika terjadi perubahan substansial pada isi, perihal, atau tujuan, buat versi baru
        if ($isSubstantialChange) {
            $nextVersion = ($existing->versions()->max('version_number') ?? 1) + 1;

            SuratKeluarVersion::create([
                'surat_keluar_id' => $existing->id,
                'user_id' => $actor->id,
                'version_number' => $nextVersion,
                'perihal' => $data['perihal'] ?? $existing->perihal,
                'isi_surat' => $data['isi_surat'] ?? $existing->isi_surat,
                'tujuan' => $data['tujuan'] ?? $existing->tujuan,
                'metadata' => $data['metadata'] ?? $existing->metadata,
                'catatan_perubahan' => $data['catatan_perubahan'] ?? "Pembaruan substansi draf versi {$nextVersion}",
                'created_at' => now(),
            ]);
        }

        $existing->update([
            'template_id' => $data['template_id'] ?? $existing->template_id,
            'klasifikasi_id' => $data['klasifikasi_id'] ?? $existing->klasifikasi_id,
            'org_unit_id' => $data['org_unit_id'] ?? $existing->org_unit_id,
            'penandatangan_id' => $data['penandatangan_id'] ?? $existing->penandatangan_id,
            'tujuan' => $data['tujuan'] ?? $existing->tujuan,
            'perihal' => $data['perihal'] ?? $existing->perihal,
            'isi_surat' => $data['isi_surat'] ?? $existing->isi_surat,
            'isi' => $data['isi_surat'] ?? $existing->isi_surat,
            'sifat' => $data['sifat'] ?? $existing->sifat,
            'metadata' => $data['metadata'] ?? $existing->metadata,
            'lampiran' => $data['lampiran'] ?? $existing->lampiran,
        ]);

        $this->saveAttachments($existing, $files, $actor->id);

        $this->auditLogger->log(
            'surat_keluar.update_draft',
            "Memperbarui draf surat keluar: '{$existing->perihal}'",
            $existing
        );

        return $existing->fresh(['versions', 'attachments', 'template', 'klasifikasi']);
    }

    protected function saveAttachments(SuratKeluar $suratKeluar, array $files, int $userId): void
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store("surat_keluar/{$suratKeluar->id}", 'local');

                SuratKeluarAttachment::create([
                    'surat_keluar_id' => $suratKeluar->id,
                    'user_id' => $userId,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                ]);
            }
        }
    }

    protected function validateStorageQuota(array $files): void
    {
        $newBytes = 0;
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $newBytes += $file->getSize();
            }
        }

        if ($newBytes === 0) {
            return;
        }

        $license = LicenseState::first() ?: LicenseState::current();
        if (! $license) {
            return;
        }

        $currentUsedBytes = (int) SuratMasukAttachment::sum('file_size') + (int) SuratKeluarAttachment::sum('file_size');

        if (($currentUsedBytes + $newBytes) > $license->max_storage_bytes) {
            $maxGb = round($license->max_storage_bytes / 1073741824, 2);
            $usedMb = round($currentUsedBytes / 1048576, 2);

            throw ValidationException::withMessages([
                'lampiran' => "Kapasitas penyimpanan telah melebihi batas kuota lisensi ({$maxGb} GB). Pemakaian saat ini: {$usedMb} MB. Hubungi admin instansi untuk memperluas kuota.",
            ]);
        }
    }
}
