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
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->unsignedBigInteger('templat_surat_id')->nullable()->after('penyetuju_id');
            $table->foreign('templat_surat_id')
                ->references('id')
                ->on('templat_surat')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->dropForeign(['templat_surat_id']);
            $table->dropColumn('templat_surat_id');
        });
    }
};
