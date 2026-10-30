<?php

namespace App\Domain\Disposisi\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiFollowup;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ForwardDisposisiAction
{
    /**
     * Meneruskan disposisi berjenjang dari penerima saat ini ke bawahannya.
     *
     * @param  User  $sender
     * @param  DisposisiRecipient  $parentRecipient
     * @param  array  $data
     * @return Disposisi
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $sender, DisposisiRecipient $parentRecipient, array $data): Disposisi
    {
        // 1. Validasi wewenang penerus
        if (! $sender->can('disposisi.teruskan')) {
            throw new AuthorizationException('Anda tidak memiliki wewenang untuk meneruskan disposisi.');
        }

        $effectiveUser = $parentRecipient->effectiveUser();
        $isRecipientUser = $effectiveUser && $effectiveUser->id === $sender->id;
        $isGlobalAdmin = $sender->can('disposisi.kelola');

        if (! $isRecipientUser && ! $isGlobalAdmin) {
            throw new AuthorizationException('Hanya penerima sah dari lembar disposisi ini yang dapat meneruskannya ke bawahan.');
        }

        // 2. Validasi status parent recipient
        if (in_array($parentRecipient->status, [
            DisposisiRecipient::STATUS_SELESAI,
            DisposisiRecipient::STATUS_DITOLAK,
            DisposisiRecipient::STATUS_DIKEMBALIKAN,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Disposisi yang telah diselesaikan atau dikembalikan tidak dapat diteruskan lagi.',
            ]);
        }

        $recipientsData = $data['recipients'] ?? [];
        if (empty($recipientsData) || ! is_array($recipientsData)) {
            throw ValidationException::withMessages([
                'recipients' => 'Penerusan disposisi wajib memilih minimal satu bawahan atau unit penerima.',
            ]);
        }

        // 3. Resolusi rantai leluhur untuk deteksi siklus (Cegah A -> B -> A)
        $ancestorUserIds = collect();
        $ancestorPositionIds = collect();

        // Kumpulkan pengirim saat ini
        $ancestorUserIds->push($sender->id);
        if ($sender->position_id) {
            $ancestorPositionIds->push($sender->position_id);
        }

        // Telusuri pohon disposisi ke atas
        $cursor = $parentRecipient->disposisi;
        while ($cursor) {
            if ($cursor->pemberi_id) {
                $ancestorUserIds->push($cursor->pemberi_id);
            }
            if ($cursor->pengirim_id) {
                $ancestorUserIds->push($cursor->pengirim_id);
            }
            if ($cursor->pemberi_position_id) {
                $ancestorPositionIds->push($cursor->pemberi_position_id);
            }

            // Kumpulkan seluruh penerima pada lembar disposisi leluhur
            foreach ($cursor->recipients as $rec) {
                if ($rec->recipient_user_id) {
                    $ancestorUserIds->push($rec->recipient_user_id);
                }
                if ($rec->delegated_user_id) {
                    $ancestorUserIds->push($rec->delegated_user_id);
                }
                if ($rec->recipient_position_id) {
                    $ancestorPositionIds->push($rec->recipient_position_id);
                }
            }

            $cursor = $cursor->parent;
        }

        $ancestorUserIds = $ancestorUserIds->unique()->filter()->values();
        $ancestorPositionIds = $ancestorPositionIds->unique()->filter()->values();

        // 4. Batasi cakupan hierarki unit (hanya bawahan dalam cakupan unit kerja pengirim)
        $allowedUnitIds = null;
        $isTopLeadership = $sender->can('disposisi.kelola')
            || ($sender->position && $sender->position->level === 1);

        if (! $isTopLeadership && $sender->position && $sender->position->orgUnit) {
            $allowedUnitIds = $this->getDescendantUnitIds($sender->position->orgUnit);
        }

        // 5. Validasi instruksi dan template
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
            $instruksi = 'Tindak lanjuti disposisi berjenjang sesuai instruksi pimpinan.';
        }

        // 6. Validasi setiap calon penerima
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
                // Deteksi siklus via posisi
                if ($ancestorPositionIds->contains($positionId)) {
                    $pos = Position::find($positionId);
                    $name = $pos ? $pos->name : "ID {$positionId}";
                    throw ValidationException::withMessages([
                        "recipients.{$idx}" => "Terdeteksi siklus disposisi: Jabatan '{$name}' sudah berada dalam riwayat rantai disposisi naskah ini.",
                    ]);
                }

                $recipientPosition = Position::with(['orgUnit', 'users'])->find($positionId);
                if (! $recipientPosition) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}.position_id" => 'Jabatan bawahan tidak ditemukan.',
                    ]);
                }

                // Cek batasan hierarki unit
                if ($allowedUnitIds !== null && ! in_array($recipientPosition->org_unit_id, $allowedUnitIds, true)) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}" => "Jabatan '{$recipientPosition->name}' berada di luar cakupan unit kerja Anda.",
                    ]);
                }

                // Cek Plt aktif
                $delegation = $recipientPosition->activeDelegation();
                if ($delegation) {
                    $isDelegated = true;
                    $delegatedUser = User::find($delegation->delegate_user_id);
                    if ($delegatedUser && $ancestorUserIds->contains($delegatedUser->id)) {
                        throw ValidationException::withMessages([
                            "recipients.{$idx}" => "Terdeteksi siklus disposisi: Plt jabatan '{$recipientPosition->name}' ({$delegatedUser->name}) sudah pernah terlibat dalam rantai disposisi surat ini.",
                        ]);
                    }
                }

                $recipientUser = $recipientPosition->users->where('is_active', true)->first();
            }

            if ($userId) {
                // Deteksi siklus via pengguna
                if ($ancestorUserIds->contains($userId)) {
                    $u = User::find($userId);
                    $name = $u ? $u->name : "ID {$userId}";
                    throw ValidationException::withMessages([
                        "recipients.{$idx}" => "Terdeteksi siklus disposisi: Pegawai '{$name}' sudah berada dalam riwayat rantai disposisi naskah ini.",
                    ]);
                }

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

                $recipientUser = $user;
                if (! $recipientPosition && $user->position_id) {
                    $recipientPosition = Position::find($user->position_id);
                }

                // Cek batasan hierarki unit jika posisi pengguna diketahui
                if ($allowedUnitIds !== null && $recipientPosition && ! in_array($recipientPosition->org_unit_id, $allowedUnitIds, true)) {
                    throw ValidationException::withMessages([
                        "recipients.{$idx}" => "Pegawai '{$user->name}' berada di luar cakupan unit kerja Anda.",
                    ]);
                }
            }

            $effectiveUserId = $isDelegated ? $delegatedUser?->id : $recipientUser?->id;
            if ($effectiveUserId && $ancestorUserIds->contains($effectiveUserId)) {
                throw ValidationException::withMessages([
                    "recipients.{$idx}" => 'Terdeteksi siklus disposisi: Penerima efektif sudah berada dalam rantai disposisi.',
                ]);
            }

            $resolvedRecipients[] = [
                'recipient_user_id' => $recipientUser?->id,
                'recipient_position_id' => $recipientPosition?->id,
                'recipient_unit_id' => $recipientPosition?->org_unit_id ?? null,
                'delegated_user_id' => $delegatedUser?->id,
                'is_delegated' => $isDelegated,
            ];
        }

        // 7. Eksekusi Database Transaction
        return DB::transaction(function () use ($sender, $parentRecipient, $data, $instruksi, $resolvedRecipients, $isRecipientUser, $isGlobalAdmin, $effectiveUser) {
            $parentDisposisi = $parentRecipient->disposisi;

            $childDisposisi = Disposisi::create([
                'surat_masuk_id' => $parentDisposisi->surat_masuk_id,
                'parent_disposisi_id' => $parentDisposisi->id,
                'pemberi_id' => $sender->id,
                'pengirim_id' => $sender->id,
                'pemberi_position_id' => $sender->position_id,
                'instruksi' => $instruksi,
                'catatan' => $data['catatan'] ?? null,
                'tenggat_waktu' => $data['tenggat_waktu'] ?? $parentDisposisi->tenggat_waktu,
                'prioritas' => $data['prioritas'] ?? $parentDisposisi->prioritas,
                'status' => Disposisi::STATUS_DIDISPOSISIKAN,
            ]);

            foreach ($resolvedRecipients as $r) {
                DisposisiRecipient::create([
                    'disposisi_id' => $childDisposisi->id,
                    'parent_recipient_id' => $parentRecipient->id,
                    'recipient_user_id' => $r['recipient_user_id'],
                    'recipient_position_id' => $r['recipient_position_id'],
                    'recipient_unit_id' => $r['recipient_unit_id'],
                    'delegated_user_id' => $r['delegated_user_id'],
                    'is_delegated' => $r['is_delegated'],
                    'status' => DisposisiRecipient::STATUS_BELUM_DIBACA,
                ]);
            }

            // Tandai status penerima induk menjadi 'diteruskan'
            $parentRecipient->update([
                'status' => DisposisiRecipient::STATUS_DITERUSKAN,
            ]);

            // Catat ke riwayat tindak lanjut parent
            DisposisiFollowup::create([
                'disposisi_recipient_id' => $parentRecipient->id,
                'user_id' => $sender->id,
                'catatan' => 'Meneruskan disposisi berjenjang: '.($data['catatan'] ?? $instruksi),
                'status_perubahan' => DisposisiRecipient::STATUS_DITERUSKAN,
            ]);

            // Kirim notifikasi in-app dan queued mail ke setiap penerima bawahan
            foreach ($childDisposisi->recipients as $recipient) {
                $targetUser = $recipient->effectiveUser();
                if ($targetUser) {
                    $targetUser->notify(new \App\Domain\Disposisi\Notifications\DisposisiReceivedNotification($childDisposisi, $recipient));
                }
            }

            // Jejak Audit
            $isActingOnBehalf = ! $isRecipientUser && $isGlobalAdmin;
            $atasNamaNote = $isActingOnBehalf
                ? ' (atas nama '.($effectiveUser?->name ?? 'Penerima Disposisi').')'
                : '';

            AuditLogger::log(
                action: 'disposisi.forwarded',
                subject: $childDisposisi,
                description: "Meneruskan disposisi naskah ke ".count($resolvedRecipients)." bawahan{$atasNamaNote}",
                properties: [
                    'parent_disposisi_id' => $parentDisposisi->id,
                    'parent_recipient_id' => $parentRecipient->id,
                    'surat_masuk_id' => $parentDisposisi->surat_masuk_id,
                    'recipients_count' => count($resolvedRecipients),
                    'atas_nama' => $isActingOnBehalf ? ($effectiveUser?->name ?? 'Penerima Disposisi') : null,
                ],
                causer: $sender
            );

            return $childDisposisi->load([
                'recipients.recipientUser',
                'recipients.recipientPosition',
                'recipients.delegatedUser',
                'pemberi',
                'pemberiPosition',
                'parent.recipients',
            ]);
        });
    }

    /**
     * Mengambil ID seluruh unit turunan (descendant) secara rekursif
     */
    protected function getDescendantUnitIds(OrgUnit $unit): array
    {
        $ids = [$unit->id];
        $children = OrgUnit::where('parent_id', $unit->id)->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getDescendantUnitIds($child));
        }

        return array_unique($ids);
    }
}
