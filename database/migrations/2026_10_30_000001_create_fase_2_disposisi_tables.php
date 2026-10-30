<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Template Instruksi Disposisi
        if (! Schema::hasTable('instruction_templates')) {
            Schema::create('instruction_templates', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 50)->unique();
                $table->string('nama', 255);
                $table->text('deskripsi')->nullable();
                $table->integer('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Upgrade tabel disposisi
        Schema::table('disposisi', function (Blueprint $table) {
            if (! Schema::hasColumn('disposisi', 'parent_disposisi_id')) {
                $table->foreignId('parent_disposisi_id')->nullable()->after('surat_masuk_id')->constrained('disposisi')->onDelete('cascade');
            }
            if (! Schema::hasColumn('disposisi', 'pemberi_id')) {
                $table->foreignId('pemberi_id')->nullable()->after('parent_disposisi_id')->constrained('users')->onDelete('cascade');
            }
            if (! Schema::hasColumn('disposisi', 'pemberi_position_id')) {
                $table->foreignId('pemberi_position_id')->nullable()->after('pemberi_id')->constrained('positions')->onDelete('set null');
            }
            if (! Schema::hasColumn('disposisi', 'catatan')) {
                $table->text('catatan')->nullable()->after('instruksi');
            }
            if (! Schema::hasColumn('disposisi', 'tenggat_waktu')) {
                $table->date('tenggat_waktu')->nullable()->after('catatan');
            }

            // Jadikan penerima_id nullable untuk mendukung penerima jamak di disposisi_recipients
            if (Schema::hasColumn('disposisi', 'penerima_id')) {
                $table->unsignedBigInteger('penerima_id')->nullable()->change();
            }

            // Ganti tipe prioritas & status ke string agar fleksibel jika sebelumnya enum
            $table->string('prioritas', 50)->default('biasa')->change();
            $table->string('status', 50)->default('didisposisikan')->change();

            // Index performa & navigasi
            $table->index('parent_disposisi_id');
            $table->index('surat_masuk_id');
            $table->index('tenggat_waktu');
            $table->index('status');
        });

        // 3. Tabel Penerima Disposisi (Multi-Recipient & Multi-Tier)
        if (! Schema::hasTable('disposisi_recipients')) {
            Schema::create('disposisi_recipients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('disposisi_id')->constrained('disposisi')->onDelete('cascade');
                $table->foreignId('parent_recipient_id')->nullable()->constrained('disposisi_recipients')->onDelete('set null');
                $table->foreignId('recipient_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('recipient_position_id')->nullable()->constrained('positions')->onDelete('set null');
                $table->foreignId('recipient_unit_id')->nullable()->constrained('org_units')->onDelete('set null');
                $table->foreignId('delegated_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->boolean('is_delegated')->default(false);
                $table->string('status', 50)->default('belum_dibaca'); // belum_dibaca, dibaca, diproses, selesai, diteruskan, ditolak, dikembalikan
                $table->timestamp('dibaca_pada')->nullable();
                $table->timestamp('selesai_pada')->nullable();
                $table->text('catatan_tindak_lanjut')->nullable();
                
                // Referensi surat keluar balasan (nullable tanpa FK keras, lihat OQ-10 di OPEN_QUESTIONS.md)
                $table->unsignedBigInteger('surat_keluar_id')->nullable();

                $table->timestamps();

                // Index kotak masuk per penerima, posisi, Plt, tenggat
                $table->index(['recipient_user_id', 'status'], 'idx_disp_recipient_user_status');
                $table->index(['recipient_position_id', 'status'], 'idx_disp_recipient_pos_status');
                $table->index(['delegated_user_id', 'status'], 'idx_disp_delegated_user_status');
                $table->index('status', 'idx_disp_recipient_status');
                $table->index('surat_keluar_id', 'idx_disp_surat_keluar');
            });
        }

        // 4. Tabel Riwayat Tindak Lanjut Disposisi
        if (! Schema::hasTable('disposisi_followups')) {
            Schema::create('disposisi_followups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('disposisi_recipient_id')->constrained('disposisi_recipients')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('catatan');
                $table->string('status_perubahan', 50); // diproses, selesai, diteruskan, dikembalikan
                $table->timestamps();

                $table->index('disposisi_recipient_id');
            });
        }

        // 5. Tabel Lampiran Bukti Penyelesaian Disposisi
        if (! Schema::hasTable('disposisi_attachments')) {
            Schema::create('disposisi_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('disposisi_followup_id')->constrained('disposisi_followups')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('file_name', 255);
                $table->string('file_path', 255);
                $table->bigInteger('file_size'); // bytes untuk pencatatan kuota
                $table->string('mime_type', 100);
                $table->timestamps();

                $table->index('disposisi_followup_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi_attachments');
        Schema::dropIfExists('disposisi_followups');
        Schema::dropIfExists('disposisi_recipients');

        Schema::table('disposisi', function (Blueprint $table) {
            $table->dropIndex(['parent_disposisi_id']);
            $table->dropIndex(['surat_masuk_id']);
            $table->dropIndex(['tenggat_waktu']);
            $table->dropIndex(['status']);

            $table->dropForeign(['parent_disposisi_id']);
            $table->dropForeign(['pemberi_id']);
            $table->dropForeign(['pemberi_position_id']);

            $table->dropColumn([
                'parent_disposisi_id',
                'pemberi_id',
                'pemberi_position_id',
                'catatan',
                'tenggat_waktu',
            ]);
        });

        Schema::dropIfExists('instruction_templates');
    }
};
