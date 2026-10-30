<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Disposisi\Models\DisposisiFollowup;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateReplyFromDisposisiAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Membuka draf surat keluar baru yang terhubung ke lembar disposisi dan surat masuk asal
     */
    public function execute(
        User $actor,
        DisposisiRecipient $recipient,
        array $overrides = []
    ): SuratKeluar {
        $disposisi = $recipient->disposisi;
        if (! $disposisi) {
            throw ValidationException::withMessages([
                'recipient' => 'Penerima disposisi tidak valid atau data disposisi induk tidak ditemukan.',
            ]);
        }

        $suratMasuk = $disposisi->suratMasuk;
        if (! $suratMasuk) {
            throw ValidationException::withMessages([
                'surat_masuk' => 'Surat masuk asal disposisi tidak ditemukan.',
            ]);
        }

        return DB::transaction(function () use ($actor, $recipient, $disposisi, $suratMasuk, $overrides) {
            $perihal = $overrides['perihal'] ?? "Tanggapan atas: {$suratMasuk->perihal}";
            $tujuan = $overrides['tujuan'] ?? ($suratMasuk->pengirim ?: 'Pihak Pengirim');
            $isiSurat = $overrides['isi_surat'] ?? "<p>Menindaklanjuti surat masuk nomor <strong>{$suratMasuk->nomor_surat}</strong> tertanggal {$suratMasuk->tanggal_surat}, perihal <em>{$suratMasuk->perihal}</em>, bersama ini kami sampaikan bahwa...</p>";

            $suratKeluar = SuratKeluar::create([
                'template_id' => $overrides['template_id'] ?? null,
                'klasifikasi_id' => $overrides['klasifikasi_id'] ?? $suratMasuk->klasifikasi_id,
                'org_unit_id' => $overrides['org_unit_id'] ?? $recipient->recipient_unit_id ?? $actor->position?->org_unit_id,
                'user_id' => $actor->id,
                'penandatangan_id' => $overrides['penandatangan_id'] ?? null,
                'reply_to_surat_masuk_id' => $suratMasuk->id,
                'tujuan' => $tujuan,
                'perihal' => $perihal,
                'isi_surat' => $isiSurat,
                'isi' => $isiSurat,
                'sifat' => $overrides['sifat'] ?? $suratMasuk->sifat ?? SuratKeluar::SIFAT_BIASA,
                'status' => SuratKeluar::STATUS_DRAF,
                'status_approval' => 'draft',
                'metadata' => array_merge($overrides['metadata'] ?? [], [
                    'reply_to_disposisi_recipient_id' => $recipient->id,
                    'reply_to_disposisi_id' => $disposisi->id,
                    'surat_masuk_nomor' => $suratMasuk->nomor_surat,
                ]),
                'qr_token' => Str::random(32),
            ]);

            // Buat versi 1 di riwayat draf
            SuratKeluarVersion::create([
                'surat_keluar_id' => $suratKeluar->id,
                'user_id' => $actor->id,
                'version_number' => 1,
                'perihal' => $suratKeluar->perihal,
                'isi_surat' => $suratKeluar->isi_surat,
                'tujuan' => $suratKeluar->tujuan,
                'metadata' => $suratKeluar->metadata,
                'catatan_perubahan' => 'Inisialisasi draf surat balasan dari lembar disposisi',
                'created_at' => now(),
            ]);

            // Kaitkan surat keluar ke baris penerima disposisi (OQ-10 relation)
            $recipient->update([
                'surat_keluar_id' => $suratKeluar->id,
                'status' => DisposisiRecipient::STATUS_DIPROSES,
            ]);

            // Buat catatan tindak lanjut awal di disposisi jika tabel ada
            if (class_exists(DisposisiFollowup::class)) {
                DisposisiFollowup::create([
                    'disposisi_recipient_id' => $recipient->id,
                    'user_id' => $actor->id,
                    'status_perubahan' => DisposisiRecipient::STATUS_DIPROSES,
                    'catatan' => "Menyiapkan draf surat balasan naskah keluar ID #{$suratKeluar->id} (Perihal: {$suratKeluar->perihal})",
                    'created_at' => now(),
                ]);
            }

            $this->auditLogger->log(
                'disposisi.create_reply',
                $suratKeluar,
                "Membuat draf surat balasan '#{$suratKeluar->id}' untuk disposisi surat masuk '{$suratMasuk->nomor_surat}'",
                causer: $actor
            );

            return $suratKeluar->fresh(['suratMasuk', 'disposisiRecipient.disposisi', 'versions']);
        });
    }
}
