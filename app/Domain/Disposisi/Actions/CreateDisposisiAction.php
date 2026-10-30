<?php

namespace App\Domain\Disposisi\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDisposisiAction
{
    /**
     * Mengeksekusi pembuatan disposisi awal (root) dari pimpinan/pejabat berwenang.
     *
     * @param  User  $sender
     * @param  SuratMasuk  $suratMasuk
     * @param  array  $data
     * @return Disposisi
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $sender, SuratMasuk $suratMasuk, array $data): Disposisi
    {
        // 1. Validasi wewenang pimpinan
        if (! $sender->can('disposisi.buat') && ! $sender->hasRole(['Admin Instansi', 'Admin', 'Pimpinan'])) {
            throw new AuthorizationException('Anda tidak memiliki wewenang untuk membuat disposisi naskah.');
        }

        // 2. Validasi status naskah
        if ($suratMasuk->status === 'dibatalkan') {
            throw ValidationException::withMessages([
                'surat_masuk_id' => 'Surat masuk yang telah dibatalkan tidak dapat didisposisikan.',
            ]);
        }

        // 3. Validasi daftar penerima
        $recipientsData = $data['recipients'] ?? [];
        if (empty($recipientsData) || ! is_array($recipientsData)) {
            throw ValidationException::withMessages([
                'recipients' => 'Disposisi wajib ditujukan ke minimal satu penerima (jabatan atau pegawai).',
            ]);
        }

        // 4. Resolusi instruksi cepat dari template
        $instruksi = trim($data['instruksi'] ?? '');
        if (! empty($data['instruction_template_id'])) {
            $template = InstructionTemplate::find($data['instruction_template_id']);
            if ($template) {
                $instruksi = empty($instruksi) 
                    ? $template->nama 
                    : "{$template->nama}. Catatan: {$instruksi}";
            }
        }

        if (empty($instruksi)) {
            throw ValidationException::withMessages([
                'instruksi' => 'Instruksi disposisi wajib diisi atau dipilih dari template instruksi.',
            ]);
        }

        // 5. Validasi dan resolusi setiap penerima (cegah diri sendiri & cek delegasi Plt)
        $resolvedRecipients = [];
        foreach ($recipientsData as $idx => $r) {
            $positionId = $r['position_id'] ?? null;
            $userId = $r['user_id'] ?? null;

            if (! $positionId && ! $userId) {
                throw ValidationException::withMessages([
                    "recipients.{$idx}" => 'Penerima harus mencantumkan ID jabatan atau ID pegawai.',
                ]);
            }

            $recipientUser = null;
            $recipientPosition = null;
            $delegatedUser = null;
            $isDelegated = false;

            if ($positionId) {
                $recipientPosition = Position::with(['orgUnit', 'users'])->find($positionId);
                if (! $recipientPosition) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}.position_id" => "Jabatan penerima tidak ditemukan.",
                    ]);
                }

                // Cek apakah posisi ini adalah posisi si pengirim
                if ($sender->position_id && $sender->position_id === $recipientPosition->id) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}" => 'Tidak diperbolehkan mendisposisikan surat ke jabatan sendiri.',
                    ]);
                }

                // Cek pendelegasian Plt aktif
                $delegation = $recipientPosition->activeDelegation();
                if ($delegation) {
                    $isDelegated = true;
                    $delegatedUser = User::find($delegation->delegate_user_id);
                    if ($delegatedUser && $delegatedUser->id === $sender->id) {
                        throw ValidationException::withMessages([
                            "recipients.{$idx}" => 'Tidak diperbolehkan mendisposisikan surat ke diri sendiri yang sedang bertindak sebagai Plt jabatan tersebut.',
                        ]);
                    }
                }

                $recipientUser = $recipientPosition->users->where('is_active', true)->first();
            }

            if ($userId) {
                $user = User::find($userId);
                if (! $user) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}.user_id" => 'Pegawai penerima tidak ditemukan.',
                    ]);
                }

                if (! $user->is_active) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}.user_id" => "Pegawai penerima ({$user->name}) berstatus nonaktif.",
                    ]);
                }

                if ($user->id === $sender->id) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}" => 'Tidak diperbolehkan mendisposisikan surat ke diri sendiri.',
                    ]);
                }

                $recipientUser = $user;
                if (! $recipientPosition && $user->position_id) {
                    $recipientPosition = Position::find($user->position_id);
                }
            }

            // Pastikan penerima efektif bukan pengirim
            $effectiveUserId = $isDelegated ? $delegatedUser?->id : $recipientUser?->id;
            if ($effectiveUserId && $effectiveUserId === $sender->id) {
                throw ValidationException::withMessages([
                    "recipients.{$idx}" => 'Tidak diperbolehkan mendisposisikan surat ke diri sendiri.',
                ]);
            }

            $resolvedRecipients[] = [
                'recipient_user_id' => $recipientUser?->id,
                'recipient_position_id' => $recipientPosition?->id,
                'recipient_unit_id' => $recipientPosition?->org_unit_id ?? $r['unit_id'] ?? null,
                'delegated_user_id' => $delegatedUser?->id,
                'is_delegated' => $isDelegated,
            ];
        }

        // 6. Transaksi Database
        return DB::transaction(function () use ($sender, $suratMasuk, $data, $instruksi, $resolvedRecipients) {
            $disposisi = Disposisi::create([
                'surat_masuk_id' => $suratMasuk->id,
                'parent_disposisi_id' => null,
                'pemberi_id' => $sender->id,
                'pengirim_id' => $sender->id, // backward-compat alias
                'pemberi_position_id' => $sender->position_id,
                'instruksi' => $instruksi,
                'catatan' => $data['catatan'] ?? null,
                'tenggat_waktu' => $data['tenggat_waktu'] ?? null,
                'prioritas' => $data['prioritas'] ?? Disposisi::PRIORITAS_BIASA,
                'status' => Disposisi::STATUS_DIDISPOSISIKAN,
            ]);

            foreach ($resolvedRecipients as $r) {
                DisposisiRecipient::create([
                    'disposisi_id' => $disposisi->id,
                    'parent_recipient_id' => null,
                    'recipient_user_id' => $r['recipient_user_id'],
                    'recipient_position_id' => $r['recipient_position_id'],
                    'recipient_unit_id' => $r['recipient_unit_id'],
                    'delegated_user_id' => $r['delegated_user_id'],
                    'is_delegated' => $r['is_delegated'],
                    'status' => DisposisiRecipient::STATUS_BELUM_DIBACA,
                ]);
            }

            // Status surat masuk berubah menjadi didisposisikan
            if ($suratMasuk->status === 'terdaftar' || $suratMasuk->status === 'menunggu_disposisi') {
                $suratMasuk->update(['status' => 'didisposisikan']);
            }

            // Kirim notifikasi in-app dan queued mail ke setiap penerima efektif
            foreach ($disposisi->recipients as $recipient) {
                $targetUser = $recipient->effectiveUser();
                if ($targetUser) {
                    $targetUser->notify(new \App\Domain\Disposisi\Notifications\DisposisiReceivedNotification($disposisi, $recipient));
                }
            }

            // Catat Jejak Audit
            AuditLogger::log(
                action: 'disposisi.created',
                subject: $disposisi,
                description: "Mendisposisikan surat agenda {$suratMasuk->nomor_agenda} kepada ".count($resolvedRecipients).' penerima',
                properties: [
                    'surat_masuk_id' => $suratMasuk->id,
                    'nomor_agenda' => $suratMasuk->nomor_agenda,
                    'recipients_count' => count($resolvedRecipients),
                    'prioritas' => $disposisi->prioritas,
                    'tenggat_waktu' => $disposisi->tenggat_waktu?->toDateString(),
                ],
                causer: $sender
            );

            return $disposisi->load([
                'recipients.recipientUser',
                'recipients.recipientPosition.orgUnit',
                'recipients.delegatedUser',
                'pemberi',
                'pemberiPosition',
                'suratMasuk',
            ]);
        });
    }
}
