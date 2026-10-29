<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_keluar', function (Blueprint $table) {
            if (! Schema::hasColumn('surat_keluar', 'qr_token')) {
                $table->string('qr_token')->unique()->nullable()->after('status_approval');
            }
        });
    }

    public function down(): void
    {
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};
