<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel template_surat (jenis: nota dinas, surat tugas, edaran, SK, dsb.)
        if (! Schema::hasTable('template_surat')) {
            Schema::create('template_surat', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('jenis', 50); // nota_dinas, surat_tugas, edaran, sk, undangan, biasa
                $table->string('kode', 50)->nullable()->unique();
                $table->string('format_nomor')->nullable(); // format nomor kustom jika ada
                $table->longText('konten_default'); // konten rich-text dengan variabel {nomor_surat}, {tanggal_surat}, dsb.
                $table->jsonb('variabel')->default('[]'); // daftar variabel yang dipakai
                $table->boolean('use_institution_header')->default(true); // kop resmi instansi
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['jenis', 'is_active']);
            });
        }

        // 2. Modifikasi aman tabel surat_keluar dari prototipe lama (jangan ditimpa)
        if (Schema::hasTable('surat_keluar')) {
            Schema::table('surat_keluar', function (Blueprint $table) {
                if (! Schema::hasColumn('surat_keluar', 'template_id')) {
                    $table->foreignId('template_id')->nullable()->constrained('template_surat')->nullOnDelete();
                }
                if (! Schema::hasColumn('surat_keluar', 'org_unit_id')) {
                    $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
                }
                if (! Schema::hasColumn('surat_keluar', 'penandatangan_id')) {
                    $table->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('surat_keluar', 'reply_to_surat_masuk_id')) {
                    $table->foreignId('reply_to_surat_masuk_id')->nullable()->constrained('surat_masuk')->nullOnDelete();
                }
                if (! Schema::hasColumn('surat_keluar', 'isi_surat')) {
                    $table->longText('isi_surat')->nullable();
                }
                if (! Schema::hasColumn('surat_keluar', 'sifat')) {
                    $table->string('sifat', 50)->default('biasa');
                }
                if (! Schema::hasColumn('surat_keluar', 'status')) {
                    $table->string('status', 50)->default('draf');
                }
                if (! Schema::hasColumn('surat_keluar', 'nomor_agenda')) {
                    $table->string('nomor_agenda', 50)->nullable();
                }
                if (! Schema::hasColumn('surat_keluar', 'tanggal_surat')) {
                    $table->date('tanggal_surat')->nullable();
                }
                if (! Schema::hasColumn('surat_keluar', 'alasan_batal')) {
                    $table->text('alasan_batal')->nullable();
                }
                if (! Schema::hasColumn('surat_keluar', 'alasan_tolak')) {
                    $table->text('alasan_tolak')->nullable();
                }
                if (! Schema::hasColumn('surat_keluar', 'catatan_revisi')) {
                    $table->text('catatan_revisi')->nullable();
                }
                if (! Schema::hasColumn('surat_keluar', 'current_approval_step')) {
                    $table->integer('current_approval_step')->default(1);
                }
                if (! Schema::hasColumn('surat_keluar', 'metadata')) {
                    $table->jsonb('metadata')->nullable();
                }
            });

            // Sinkronisasi data prototipe lama jika ada baris
            if (Schema::hasColumn('surat_keluar', 'isi')) {
                DB::statement("UPDATE surat_keluar SET isi_surat = isi WHERE isi_surat IS NULL AND isi IS NOT NULL");
            }
            if (Schema::hasColumn('surat_keluar', 'status_approval')) {
                DB::statement("UPDATE surat_keluar SET status = status_approval WHERE status = 'draf' AND status_approval IS NOT NULL");
            }

            // Tambahkan indeks komposit untuk optimasi query daftar dan filter
            Schema::table('surat_keluar', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_sk_status_created_at');
                $table->index(['org_unit_id', 'status'], 'idx_sk_org_unit_status');
            });
        }

        // 3. Tabel surat_keluar_versions (Riwayat Draf)
        Schema::create('surat_keluar_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluar')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('version_number');
            $table->string('perihal');
            $table->longText('isi_surat')->nullable();
            $table->text('tujuan')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->text('catatan_perubahan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['surat_keluar_id', 'version_number']);
            $table->index(['surat_keluar_id', 'created_at']);
        });

        // 4. Tabel surat_keluar_approvals (Alur Pemeriksaan Berjenjang: Pemeriksa, Paraf, Penandatangan)
        Schema::create('surat_keluar_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluar')->cascadeOnDelete();
            $table->integer('step_order')->default(1);
            $table->string('role_type', 50); // pemeriksa, paraf, penandatangan
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('delegated_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('menunggu'); // menunggu, disetujui, diparaf, revisi, ditolak, dilewati
            $table->text('catatan')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->foreignId('acted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['surat_keluar_id', 'step_order']);
            $table->index(['status', 'user_id']);
        });

        // 5. Tabel surat_keluar_attachments (Lampiran Berkas)
        Schema::create('surat_keluar_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluar')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->index('surat_keluar_id');
        });

        // 6. Aktifkan FK surat_keluar_id pada disposisi_recipients (OQ-10)
        Schema::table('disposisi_recipients', function (Blueprint $table) {
            $table->foreign('surat_keluar_id')
                ->references('id')
                ->on('surat_keluar')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 1. Drop FK di disposisi_recipients
        Schema::table('disposisi_recipients', function (Blueprint $table) {
            $table->dropForeign(['surat_keluar_id']);
        });

        // 2. Drop tabel-tabel baru
        Schema::dropIfExists('surat_keluar_attachments');
        Schema::dropIfExists('surat_keluar_approvals');
        Schema::dropIfExists('surat_keluar_versions');

        // 3. Rollback modifikasi kolom surat_keluar
        if (Schema::hasTable('surat_keluar')) {
            Schema::table('surat_keluar', function (Blueprint $table) {
                $table->dropIndex('idx_sk_status_created_at');
                $table->dropIndex('idx_sk_org_unit_status');
                $table->dropForeign(['template_id']);
                $table->dropForeign(['org_unit_id']);
                $table->dropForeign(['penandatangan_id']);
                $table->dropForeign(['reply_to_surat_masuk_id']);
                $table->dropColumn([
                    'template_id', 'org_unit_id', 'penandatangan_id', 'reply_to_surat_masuk_id',
                    'isi_surat', 'sifat', 'status', 'nomor_agenda', 'alasan_batal',
                    'alasan_tolak', 'catatan_revisi', 'current_approval_step', 'metadata'
                ]);
            });
        }

        Schema::dropIfExists('template_surat');
    }
};
