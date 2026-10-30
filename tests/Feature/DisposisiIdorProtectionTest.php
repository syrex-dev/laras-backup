<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiAttachment;
use App\Domain\Disposisi\Models\DisposisiFollowup;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiIdorProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $creatorUser;
    protected User $recipientUser;
    protected User $unrelatedUser;
    protected SuratMasuk $suratMasuk;
    protected Disposisi $disposisi;
    protected DisposisiRecipient $recipient;
    protected DisposisiAttachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        // Permissions
        $permissions = [
            'surat-masuk.lihat', 'surat-masuk.registrasi',
            'disposisi.lihat', 'disposisi.buat', 'disposisi.teruskan', 'disposisi.tindaklanjut', 'disposisi.kelola',
            'organisasi.kelola',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $pimpinanRole->syncPermissions(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.buat', 'disposisi.teruskan']);

        $stafRole = Role::firstOrCreate(['name' => 'Staf', 'guard_name' => 'web']);
        $stafRole->syncPermissions(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.tindaklanjut']);

        // Units & Positions
        $unit1 = OrgUnit::create(['name' => 'Pimpinan', 'code' => 'DIR', 'type' => 'pimpinan']);
        $unit2 = OrgUnit::create(['name' => 'Jurusan Mesin', 'code' => 'JTM', 'type' => 'jurusan', 'parent_id' => $unit1->id]);
        $unit3 = OrgUnit::create(['name' => 'Jurusan Elektro', 'code' => 'JTE', 'type' => 'jurusan', 'parent_id' => $unit1->id]);

        $pos1 = Position::create(['name' => 'Direktur', 'org_unit_id' => $unit1->id, 'level' => 1]);
        $pos2 = Position::create(['name' => 'Kajur Mesin', 'org_unit_id' => $unit2->id, 'level' => 2]);
        $pos3 = Position::create(['name' => 'Kajur Elektro', 'org_unit_id' => $unit3->id, 'level' => 2]);

        $this->creatorUser = User::create([
            'name' => 'Dr. Budi (Direktur)',
            'email' => 'direktur@contoh.ac.id',
            'password' => Hash::make('password'),
            'position_id' => $pos1->id,
            'is_active' => true,
        ]);
        $this->creatorUser->assignRole($pimpinanRole);

        $this->recipientUser = User::create([
            'name' => 'Ir. Hendra (Kajur Mesin)',
            'email' => 'kajur.mesin@contoh.ac.id',
            'password' => Hash::make('password'),
            'position_id' => $pos2->id,
            'is_active' => true,
        ]);
        $this->recipientUser->assignRole($stafRole);

        $this->unrelatedUser = User::create([
            'name' => 'Ir. Wahyu (Kajur Elektro - Unrelated)',
            'email' => 'kajur.elektro@contoh.ac.id',
            'password' => Hash::make('password'),
            'position_id' => $pos3->id,
            'is_active' => true,
        ]);
        $this->unrelatedUser->assignRole($stafRole);

        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'UM.01',
            'nama' => 'Umum',
            'retensi_aktif' => 1,
            'retensi_inaktif' => 2,
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'nomor_agenda' => '000001/SM/2026',
            'nomor_surat' => 'B/001/KEM/2026',
            'pengirim' => 'Kemdikbudristek',
            'perihal' => 'Pemberitahuan Akreditasi Khusus Mesin',
            'tanggal_surat' => '2026-03-01',
            'tanggal_diterima' => '2026-03-02',
            'status' => 'didisposisikan',
            'sifat' => 'penting',
            'klasifikasi_id' => $klasifikasi->id,
            'unit_tujuan_id' => $unit2->id, // Mesin
            'user_id' => $this->creatorUser->id,
            'qr_token' => 'secure-tracking-token-12345',
        ]);

        $this->disposisi = Disposisi::create([
            'surat_masuk_id' => $this->suratMasuk->id,
            'pengirim_id' => $this->creatorUser->id,
            'pemberi_id' => $this->creatorUser->id,
            'pemberi_position_id' => $pos1->id,
            'instruksi' => 'Tindak lanjuti dokumen rahasia ini.',
            'prioritas' => 'penting',
            'status' => 'didisposisikan',
        ]);

        $this->recipient = DisposisiRecipient::create([
            'disposisi_id' => $this->disposisi->id,
            'recipient_user_id' => $this->recipientUser->id,
            'recipient_position_id' => $pos2->id,
            'status' => 'belum_dibaca',
        ]);

        $followup = DisposisiFollowup::create([
            'disposisi_recipient_id' => $this->recipient->id,
            'user_id' => $this->recipientUser->id,
            'status_perubahan' => 'selesai',
            'catatan' => 'Laporan audit selesai disusun.',
        ]);

        // Simpan file bukti di fake storage
        $storedPath = 'private/disposisi/' . $this->suratMasuk->id . '/bukti_rahasia.pdf';
        Storage::disk('local')->put($storedPath, '%PDF-1.4 Fake Secret Content');

        $this->attachment = DisposisiAttachment::create([
            'disposisi_followup_id' => $followup->id,
            'user_id' => $this->recipientUser->id,
            'file_name' => 'bukti_rahasia.pdf',
            'file_path' => $storedPath,
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_unrelated_user_cannot_access_disposition_detail_idor(): void
    {
        // 1. Unrelated user mencoba membuka detail disposisi
        $response = $this->actingAs($this->unrelatedUser)
            ->get(route('disposisi.recipient.show', $this->recipient));

        $response->assertStatus(403);

        // 2. Authorized recipient dapat membuka detail
        $authResponse = $this->actingAs($this->recipientUser)
            ->get(route('disposisi.recipient.show', $this->recipient));

        $authResponse->assertOk();

        // 3. Creator/Pemberi disposisi juga dapat membuka detail
        $creatorResponse = $this->actingAs($this->creatorUser)
            ->get(route('disposisi.recipient.show', $this->recipient));

        $creatorResponse->assertOk();
    }

    public function test_unrelated_user_cannot_download_disposition_attachment_idor(): void
    {
        // 1. Unrelated user langsung menembak URL download bukti lampiran
        $response = $this->actingAs($this->unrelatedUser)
            ->get(route('disposisi.attachment.download', $this->attachment));

        $response->assertStatus(403);

        // 2. Authorized recipient berhak mengunduh bukti lampiran
        $authResponse = $this->actingAs($this->recipientUser)
            ->get(route('disposisi.attachment.download', $this->attachment));

        $authResponse->assertOk();

        // 3. Creator/Pemberi disposisi berhak mengunduh bukti lampiran
        $creatorResponse = $this->actingAs($this->creatorUser)
            ->get(route('disposisi.attachment.download', $this->attachment));

        $creatorResponse->assertOk();
    }

    public function test_unrelated_user_cannot_open_tracking_page_for_outside_letter_idor(): void
    {
        // 1. Unrelated user (Jurusan Elektro) mencoba melacak surat milik Jurusan Mesin
        $response = $this->actingAs($this->unrelatedUser)
            ->get(route('surat-masuk.lacak', $this->suratMasuk->qr_token));

        $response->assertStatus(403);

        // 2. Recipient yang terlibat dalam surat dapat melacak
        $authResponse = $this->actingAs($this->recipientUser)
            ->get(route('surat-masuk.lacak', $this->suratMasuk->qr_token));

        $authResponse->assertOk();

        // 3. Creator/Pembuat surat dapat melacak
        $creatorResponse = $this->actingAs($this->creatorUser)
            ->get(route('surat-masuk.lacak', $this->suratMasuk->qr_token));

        $creatorResponse->assertOk();
    }
}
