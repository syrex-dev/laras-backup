<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengoptimalkan indeks gabungan untuk Kotak Masuk Disposisi (Sub-langkah A1).
     */
    public function up(): void
    {
        // 1. Indeks komposit pada tabel disposisi_recipients
        Schema::table('disposisi_recipients', function (Blueprint $table) {
            // Indeks pada foreign key disposisi_id untuk percepatan JOIN
            $table->index('disposisi_id', 'idx_disp_rec_disposisi_id');

            // Indeks komposit (user, status, id DESC) untuk filter tab status & pagination
            $table->index(['recipient_user_id', 'status', 'id'], 'idx_disp_rec_user_status_id');
            $table->index(['delegated_user_id', 'status', 'id'], 'idx_disp_rec_delegated_status_id');

            // Indeks komposit (user, id DESC) untuk inbox umum (status = semua)
            $table->index(['recipient_user_id', 'id'], 'idx_disp_rec_user_id_desc');
            $table->index(['delegated_user_id', 'id'], 'idx_disp_rec_delegated_id_desc');
        });

        // 2. Indeks komposit pada tabel disposisi
        Schema::table('disposisi', function (Blueprint $table) {
            // Indeks komposit (prioritas, id DESC) untuk filter prioritas
            $table->index(['prioritas', 'id'], 'idx_disp_prioritas_id');

            // Indeks komposit untuk scheduler dan pengecekan tenggat
            $table->index(['tenggat_waktu', 'status', 'id'], 'idx_disp_tenggat_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposisi_recipients', function (Blueprint $table) {
            $table->dropIndex('idx_disp_rec_disposisi_id');
            $table->dropIndex('idx_disp_rec_user_status_id');
            $table->dropIndex('idx_disp_rec_delegated_status_id');
            $table->dropIndex('idx_disp_rec_user_id_desc');
            $table->dropIndex('idx_disp_rec_delegated_id_desc');
        });

        Schema::table('disposisi', function (Blueprint $table) {
            $table->dropIndex('idx_disp_prioritas_id');
            $table->dropIndex('idx_disp_tenggat_status_id');
        });
    }
};
