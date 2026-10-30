<?php

namespace Database\Seeders;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Actions\FollowupDisposisiAction;
use App\Domain\Disposisi\Actions\ForwardDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DisposisiDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Membuat contoh riwayat disposisi berjenjang realistis:
     * 1. Rantai 3 Tingkat (Direktur -> Kajur -> Kaprodi/Dosen)
     * 2. Disposisi dengan Delegasi / Plt aktif
     * 3. Disposisi selesai beserta bukti tindak lanjut
     * 4. Disposisi mendekati tenggat (H-1) & terlambat
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DisposisiDemoSeeder dilewati karena sistem tidak berada pada environment local/testing.');
            return;
        }

        // Ambil pimpinan & pejabat
        $direktur = User::whereHas('roles', fn ($q) => $q->where('name', 'Pimpinan'))->first();
        if (! $direktur) {
            $direktur = User::first();
        }

        $posDirektur = $direktur?->position ?? Position::first();

        // Cari atau buat unit & pejabat bawahan dengan relasi hierarki yang valid (DIR -> JTK -> D4-TI)
        $kajurUser = User::where('email', 'kajur.jtk@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Kepala Unit'))->first();
        $kaprodiUser = User::where('email', 'kaprodi.ti@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Staf'))->first();

        if (! $kajurUser || ! $kaprodiUser) {
            return;
        }

        // Ambil surat masuk yang tersedia untuk didisposisikan (bukan yang dibatalkan)
        $suratList = SuratMasuk::where('status', '!=', 'dibatalkan')->orderBy('id', 'asc')->take(5)->get();
        if ($suratList->isEmpty()) {
            return;
        }

        $createAction = app(CreateDisposisiAction::class);
        $forwardAction = app(ForwardDisposisiAction::class);
        $followupAction = app(FollowupDisposisiAction::class);

        // Template instruksi umum
        $templateTindakLanjuti = InstructionTemplate::where('nama', 'Tindak Lanjuti')->first();
        $templateHadiri = InstructionTemplate::where('nama', 'Hadiri / Wakili')->first();

        // -------------------------------------------------------------------------
        // Skenario 1: Disposisi Berjenjang 3 Tingkat (Direktur -> Kajur -> Kaprodi)
        // -------------------------------------------------------------------------
        if (isset($suratList[0])) {
            $surat1 = $suratList[0];

            // 1. Direktur mendisposisikan ke Kajur
            $disp1 = $createAction->execute(
                sender: $direktur,
                suratMasuk: $surat1,
                data: [
                    'instruction_template_id' => $templateTindakLanjuti?->id,
                    'instruksi' => 'Tindak lanjuti segera dan koordinasikan dengan program studi terkait.',
                    'prioritas' => 'penting',
                    'tenggat_waktu' => now()->addDays(4)->format('Y-m-d H:i:s'),
                    'recipients' => [
                        [
                            'user_id' => $kajurUser->id,
                            'position_id' => $kajurUser->position_id,
                        ],
                    ],
                ]
            );

            $recKajur = $disp1->recipients()->first();

            // 2. Kajur meneruskan ke Kaprodi
            if ($recKajur) {
                $disp2 = $forwardAction->execute(
                    sender: $kajurUser,
                    parentRecipient: $recKajur,
                    data: [
                        'instruction_template_id' => $templateTindakLanjuti?->id,
                        'instruksi' => 'Mohon siapkan bahan koordinasi kurikulum dan delegasi dosen.',
                        'prioritas' => 'penting',
                        'tenggat_waktu' => now()->addDays(2)->format('Y-m-d H:i:s'),
                        'recipients' => [
                            [
                                'user_id' => $kaprodiUser->id,
                                'position_id' => $kaprodiUser->position_id,
                            ],
                        ],
                    ]
                );

                $recKaprodi = $disp2->recipients()->first();

                // 3. Kaprodi menindaklanjuti (status: diproses)
                if ($recKaprodi) {
                    $followupAction->execute(
                        user: $kaprodiUser,
                        recipient: $recKaprodi,
                        data: [
                            'status' => 'diproses',
                            'catatan' => 'Rapat koordinasi tingkat prodi sedang dijadwalkan bersama tim kurikulum.',
                        ]
                    );
                }
            }
        }

        // -------------------------------------------------------------------------
        // Skenario 2: Disposisi Selesai Lengkap (Direktur -> Kajur -> Selesai)
        // -------------------------------------------------------------------------
        if (isset($suratList[1])) {
            $surat2 = $suratList[1];

            $dispSelesai = $createAction->execute(
                sender: $direktur,
                suratMasuk: $surat2,
                data: [
                    'instruction_template_id' => $templateHadiri?->id,
                    'instruksi' => 'Hadiri sosialisasi daring dan laporkan ringkasan hasil kegiatan.',
                    'prioritas' => 'biasa',
                    'tenggat_waktu' => now()->subDay()->format('Y-m-d H:i:s'),
                    'recipients' => [
                        [
                            'user_id' => $kajurUser->id,
                            'position_id' => $kajurUser->position_id,
                        ],
                    ],
                ]
            );

            $recSelesai = $dispSelesai->recipients()->first();
            if ($recSelesai) {
                $followupAction->execute(
                    user: $kajurUser,
                    recipient: $recSelesai,
                    data: [
                        'status' => 'selesai',
                        'catatan' => 'Kegiatan sosialisasi telah dihadiri secara daring. Materi telah diarsipkan.',
                    ]
                );
            }
        }

        // -------------------------------------------------------------------------
        // Skenario 3: Disposisi Mendekati Tenggat (H-1) & Keterlambatan
        // -------------------------------------------------------------------------
        if (isset($suratList[2])) {
            $surat3 = $suratList[2];

            $createAction->execute(
                sender: $direktur,
                suratMasuk: $surat3,
                data: [
                    'instruksi' => 'Mohon evaluasi usulan anggaran kegiatan mahasiswa semester genap.',
                    'prioritas' => 'sangat_segera',
                    'tenggat_waktu' => now()->addHours(18)->format('Y-m-d H:i:s'), // H-1
                    'recipients' => [
                        [
                            'user_id' => $kajurUser->id,
                            'position_id' => $kajurUser->position_id,
                        ],
                    ],
                ]
            );
        }

        if (isset($suratList[3])) {
            $surat4 = $suratList[3];

            $createAction->execute(
                sender: $direktur,
                suratMasuk: $surat4,
                data: [
                    'instruksi' => 'Kirimkan data akreditasi program studi ke LAM Infokom.',
                    'prioritas' => 'sangat_segera',
                    'tenggat_waktu' => now()->subDays(2)->format('Y-m-d H:i:s'), // Terlambat
                    'recipients' => [
                        [
                            'user_id' => $kajurUser->id,
                            'position_id' => $kajurUser->position_id,
                        ],
                    ],
                ]
            );
        }
    }
}
