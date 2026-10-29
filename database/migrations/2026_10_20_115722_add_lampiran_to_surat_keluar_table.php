<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom lampiran ke tabel surat_keluar.
     * Digunakan untuk placeholder {{ lampiran }} dan [LAMPIRAN] di template HTML surat.
     */
    public function up(): void
    {
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->string('lampiran')->nullable()->after('perihal')
                ->comment('Keterangan lampiran surat, misal: 1 berkas, Terlampir, -');
        });
    }

    public function down(): void
    {
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->dropColumn('lampiran');
        });
    }
};
