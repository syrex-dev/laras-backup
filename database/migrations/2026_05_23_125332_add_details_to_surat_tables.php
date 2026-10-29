<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->foreignId('klasifikasi_id')->after('nomor_surat')->nullable()->constrained('klasifikasi_surat')->onDelete('restrict');
            $table->date('tanggal_diterima')->after('tanggal_surat')->nullable();
            $table->softDeletes();
        });

        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->foreignId('penyetuju_id')->after('user_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->date('tanggal_surat')->after('nomor_surat')->nullable();
            $table->string('path_qrcode')->after('file_pdf')->nullable();
            $table->softDeletes();
        });

        Schema::table('disposisi', function (Blueprint $table) {
            $table->enum('prioritas', ['segera', 'rahasia', 'biasa'])->after('instruksi')->default('biasa');
            $table->timestamp('dibaca_pada')->after('status')->nullable();
            $table->timestamp('selesai_pada')->after('dibaca_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->dropForeign(['klasifikasi_id']);
            $table->dropColumn(['klasifikasi_id', 'tanggal_diterima', 'deleted_at']);
        });

        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->dropForeign(['penyetuju_id']);
            $table->dropColumn(['penyetuju_id', 'tanggal_surat', 'path_qrcode', 'deleted_at']);
        });

        Schema::table('disposisi', function (Blueprint $table) {
            $table->dropColumn(['prioritas', 'dibaca_pada', 'selesai_pada']);
        });
    }
};
