<?php

namespace App\Domain\SuratMasuk\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Domain\SuratMasuk\Models\SuratMasukAttachment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SuratMasukService
{
    public function __construct(
        protected AgendaNumberService $agendaNumberService
    ) {}

    /**
     * Daftarkan surat masuk baru dengan nomor agenda otomatis,
     * penyimpanan privat, dan audit log lengkap.
     */
    public function create(array $data, array $files, int $userId): SuratMasuk
    {
        $this->validateStorageQuota($files);

        return DB::transaction(function () use ($data, $files, $userId) {
            $year = Carbon::parse($data['tanggal_diterima'])->year;
            $nomorAgenda = $this->agendaNumberService->generate($year);

            $suratMasuk = SuratMasuk::create([
                'nomor_agenda' => $nomorAgenda,
                'nomor_surat' => $data['nomor_surat'],
                'klasifikasi_id' => $data['klasifikasi_id'],
                'unit_tujuan_id' => $data['unit_tujuan_id'] ?? null,
                'tanggal_surat' => $data['tanggal_surat'],
                'tanggal_diterima' => $data['tanggal_diterima'],
                'pengirim' => $data['pengirim'],
                'perihal' => $data['perihal'],
                'sifat' => $data['sifat'] ?? 'biasa',
                'jenis' => $data['jenis'] ?? 'fisik',
                'status' => 'terdaftar',
                'keterangan' => $data['keterangan'] ?? null,
                'user_id' => $userId,
            ]);

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $this->storeAttachment($suratMasuk, $file, $userId);
                }
            }

            AuditLogger::log(
                action: 'surat_masuk.created',
                subject: $suratMasuk,
                description: "Registrasi surat masuk baru dengan nomor agenda {$suratMasuk->nomor_agenda} dari {$suratMasuk->pengirim}",
                properties: [
                    'id' => $suratMasuk->id,
                    'nomor_agenda' => $suratMasuk->nomor_agenda,
                    'nomor_surat' => $suratMasuk->nomor_surat,
                    'pengirim' => $suratMasuk->pengirim,
                    'perihal' => $suratMasuk->perihal,
                    'sifat' => $suratMasuk->sifat,
                    'attachments_count' => count($files),
                ]
            );

            return $suratMasuk;
        });
    }

    /**
     * Perbarui data surat masuk dan tambahkan lampiran baru jika ada.
     */
    public function update(SuratMasuk $suratMasuk, array $data, array $files, int $userId): SuratMasuk
    {
        if ($suratMasuk->isDibatalkan()) {
            throw ValidationException::withMessages([
                'status' => 'Surat masuk yang telah dibatalkan tidak dapat diperbarui lagi.',
            ]);
        }

        $this->validateStorageQuota($files);

        return DB::transaction(function () use ($suratMasuk, $data, $files, $userId) {
            $oldData = $suratMasuk->only([
                'nomor_surat', 'pengirim', 'perihal', 'sifat', 'jenis',
                'klasifikasi_id', 'unit_tujuan_id', 'tanggal_surat', 'tanggal_diterima',
            ]);

            $suratMasuk->update([
                'nomor_surat' => $data['nomor_surat'],
                'klasifikasi_id' => $data['klasifikasi_id'],
                'unit_tujuan_id' => $data['unit_tujuan_id'] ?? null,
                'tanggal_surat' => $data['tanggal_surat'],
                'tanggal_diterima' => $data['tanggal_diterima'],
                'pengirim' => $data['pengirim'],
                'perihal' => $data['perihal'],
                'sifat' => $data['sifat'] ?? $suratMasuk->sifat,
                'jenis' => $data['jenis'] ?? $suratMasuk->jenis,
                'keterangan' => $data['keterangan'] ?? $suratMasuk->keterangan,
            ]);

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $this->storeAttachment($suratMasuk, $file, $userId);
                }
            }

            AuditLogger::log(
                action: 'surat_masuk.updated',
                subject: $suratMasuk,
                description: "Pembaruan data surat masuk agenda {$suratMasuk->nomor_agenda}",
                properties: [
                    'old' => $oldData,
                    'new' => $suratMasuk->only(array_keys($oldData)),
                    'added_attachments' => count($files),
                ]
            );

            return $suratMasuk;
        });
    }

    /**
     * Batalkan surat masuk (Soft cancellation dengan alasan wajib).
     */
    public function cancel(SuratMasuk $suratMasuk, string $reason, int $userId): SuratMasuk
    {
        if ($suratMasuk->isDibatalkan()) {
            throw ValidationException::withMessages([
                'alasan_batal' => 'Surat masuk ini sudah dalam status dibatalkan.',
            ]);
        }

        $oldStatus = $suratMasuk->status;

        $suratMasuk->update([
            'status' => 'dibatalkan',
            'alasan_batal' => $reason,
            'dibatalkan_oleh_id' => $userId,
            'dibatalkan_pada' => Carbon::now(),
        ]);

        AuditLogger::log(
            action: 'surat_masuk.cancelled',
            subject: $suratMasuk,
            description: "Surat masuk agenda {$suratMasuk->nomor_agenda} dibatalkan dengan alasan: {$reason}",
            properties: [
                'old_status' => $oldStatus,
                'new_status' => 'dibatalkan',
                'alasan_batal' => $reason,
                'dibatalkan_oleh_id' => $userId,
                'dibatalkan_pada' => $suratMasuk->dibatalkan_pada->toDateTimeString(),
            ]
        );

        return $suratMasuk;
    }

    /**
     * Simpan lampiran berkas ke storage privat terenkripsi/terproteksi.
     */
    public function storeAttachment(SuratMasuk $suratMasuk, UploadedFile $file, int $userId): SuratMasukAttachment
    {
        $originalName = $file->getClientOriginalName();
        $safeFileName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $directory = 'surat-masuk/'.$suratMasuk->id;

        $path = Storage::disk('local')->putFileAs($directory, $file, $safeFileName);

        return SuratMasukAttachment::create([
            'surat_masuk_id' => $suratMasuk->id,
            'file_name' => $originalName,
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'uploader_id' => $userId,
        ]);
    }

    /**
     * Validasi kuota penyimpanan lisensi sebelum menyimpan berkas baru.
     */
    public function validateStorageQuota(array $files): void
    {
        if (empty($files)) {
            return;
        }

        $newBytes = 0;
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $newBytes += $file->getSize();
            }
        }

        $license = LicenseState::current();
        $currentUsedBytes = (int) SuratMasukAttachment::sum('file_size');

        if (($currentUsedBytes + $newBytes) > $license->max_storage_bytes) {
            $maxGb = round($license->max_storage_bytes / 1073741824, 2);
            $usedMb = round($currentUsedBytes / 1048576, 2);

            throw ValidationException::withMessages([
                'lampiran' => "Kapasitas penyimpanan telah melebihi batas kuota lisensi ({$maxGb} GB). Pemakaian saat ini: {$usedMb} MB. Silakan hubungi admin instansi untuk memperluas kuota storage.",
            ]);
        }
    }

    /**
     * Cetak PDF Lembar Disposisi Ber-QR Code
     */
    public function renderLembarDisposisiPdf(SuratMasuk $suratMasuk)
    {
        $institution = InstitutionProfile::current();
        $trackingUrl = route('surat-masuk.show', $suratMasuk->id);

        $qrCodeSvg = QrCode::format('svg')->size(100)->generate($trackingUrl);
        $qrCodeBase64 = 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);

        $pdf = Pdf::loadView('pdf.lembar-disposisi', [
            'surat' => $suratMasuk->load(['klasifikasi', 'unitTujuan', 'attachments']),
            'institution' => $institution,
            'qrCode' => $qrCodeBase64,
            'trackingUrl' => $trackingUrl,
        ]);

        return $pdf->setPaper('a4', 'portrait');
    }

    /**
     * Cetak PDF Bukti Terima Surat Masuk Ber-QR Code
     */
    public function renderBuktiTerimaPdf(SuratMasuk $suratMasuk)
    {
        $institution = InstitutionProfile::current();
        $trackingUrl = route('surat-masuk.show', $suratMasuk->id);

        $qrCodeSvg = QrCode::format('svg')->size(100)->generate($trackingUrl);
        $qrCodeBase64 = 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);

        $pdf = Pdf::loadView('pdf.bukti-terima', [
            'surat' => $suratMasuk->load(['klasifikasi', 'unitTujuan', 'user']),
            'institution' => $institution,
            'qrCode' => $qrCodeBase64,
            'trackingUrl' => $trackingUrl,
        ]);

        return $pdf->setPaper('a5', 'landscape');
    }
}
