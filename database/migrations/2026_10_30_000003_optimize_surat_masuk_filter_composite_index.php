<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengoptimalkan indeks gabungan untuk filter daftar surat masuk (Sub-langkah A2).
     */
    public function up(): void
    {
        Schema::table('surat_masuk', function (Blueprint $table) {
            // Indeks komposit untuk filter status + sifat + urutan tanggal_diterima & id
            $table->index(['status', 'sifat', 'tanggal_diterima', 'id'], 'idx_sm_status_sifat_tgl_id');

            // Indeks komposit untuk filter status + urutan
            $table->index(['status', 'tanggal_diterima', 'id'], 'idx_sm_status_tgl_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->dropIndex('idx_sm_status_sifat_tgl_id');
            $table->dropIndex('idx_sm_status_tgl_id');
        });
    }
};
