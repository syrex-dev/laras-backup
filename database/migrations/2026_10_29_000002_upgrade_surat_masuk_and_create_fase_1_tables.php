<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade tabel surat_masuk
        Schema::table('surat_masuk', function (Blueprint $table) {
            if (! Schema::hasColumn('surat_masuk', 'nomor_agenda')) {
                $table->string('nomor_agenda', 50)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('surat_masuk', 'unit_tujuan_id')) {
                $table->foreignId('unit_tujuan_id')->nullable()->after('klasifikasi_id')->constrained('org_units')->onDelete('set null');
            }
            if (! Schema::hasColumn('surat_masuk', 'sifat')) {
                $table->string('sifat', 50)->default('biasa')->after('perihal'); // biasa, penting, segera, rahasia
            }
            if (! Schema::hasColumn('surat_masuk', 'jenis')) {
                $table->string('jenis', 50)->default('fisik')->after('sifat'); // fisik, email, undangan, edaran, umum
            }
            if (! Schema::hasColumn('surat_masuk', 'status')) {
                $table->string('status', 50)->default('terdaftar')->after('jenis'); // terdaftar, menunggu_disposisi, didisposisi, diproses, selesai, diarsipkan, dibatalkan
            }
            if (! Schema::hasColumn('surat_masuk', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('ringkasan');
            }
            if (! Schema::hasColumn('surat_masuk', 'alasan_batal')) {
                $table->text('alasan_batal')->nullable()->after('keterangan');
            }
            if (! Schema::hasColumn('surat_masuk', 'dibatalkan_oleh_id')) {
                $table->foreignId('dibatalkan_oleh_id')->nullable()->after('alasan_batal')->constrained('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('surat_masuk', 'dibatalkan_pada')) {
                $table->timestamp('dibatalkan_pada')->nullable()->after('dibatalkan_oleh_id');
            }
            if (! Schema::hasColumn('surat_masuk', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->unique()->after('status_ekstraksi_ai');
            }
            if (! Schema::hasColumn('surat_masuk', 'search_text')) {
                $table->text('search_text')->nullable()->after('qr_token');
            }

            // Index pendukung filter & performa
            $table->index('status');
            $table->index('tanggal_diterima');
            $table->index('tanggal_surat');
            $table->index('sifat');
        });

        // Index Full-Text di PostgreSQL untuk pencarian awal: perihal, pengirim, nomor_surat
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS surat_masuk_fts_idx ON surat_masuk USING gin(to_tsvector(\'simple\', coalesce(search_text, \'\')));');
        } elseif ($driver === 'sqlite') {
            Schema::table('surat_masuk', function (Blueprint $table) {
                $table->index('search_text');
            });
        }

        // 2. Tabel surat_masuk_attachments (Penyimpanan Berkas Privat)
        if (! Schema::hasTable('surat_masuk_attachments')) {
            Schema::create('surat_masuk_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surat_masuk_id')->constrained('surat_masuk')->onDelete('cascade');
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('file_size');
                $table->string('description')->nullable();
                $table->foreignId('uploader_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->index('surat_masuk_id');
            });
        }

        // 3. Tabel numbering_sequences (Nomor Agenda & Penomoran Atomik)
        if (! Schema::hasTable('numbering_sequences')) {
            Schema::create('numbering_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50); // surat_masuk, surat_keluar
                $table->integer('year');
                $table->string('unit_code', 50)->default('PUSAT');
                $table->string('prefix', 50)->default('SM');
                $table->unsignedInteger('last_number')->default(0);
                $table->string('format_pattern', 100)->default('{prefix}-{year}-{number:4}');
                $table->timestamps();

                $table->unique(['type', 'year', 'unit_code'], 'num_seq_type_year_unit_unique');
                $table->index(['type', 'year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_masuk_attachments');
        Schema::dropIfExists('numbering_sequences');

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS surat_masuk_fts_idx;');
        }

        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->dropForeign(['unit_tujuan_id']);
            $table->dropForeign(['dibatalkan_oleh_id']);
            $table->dropColumn([
                'nomor_agenda',
                'unit_tujuan_id',
                'sifat',
                'jenis',
                'status',
                'keterangan',
                'alasan_batal',
                'dibatalkan_oleh_id',
                'dibatalkan_pada',
                'qr_token',
                'search_text',
            ]);
        });
    }
};
