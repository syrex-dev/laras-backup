<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('surat_keluar')) {
            Schema::table('surat_keluar', function (Blueprint $table) {
                // Indeks komposit untuk pagination dan filter status berurutan
                $table->index(['status', 'id'], 'idx_sk_status_id');
                $table->index(['status', 'sifat', 'id'], 'idx_sk_status_sifat_id');
                $table->index(['org_unit_id', 'status', 'id'], 'idx_sk_org_unit_status_id');
            });

            // Indeks trigram / text search untuk perihal dan tujuan di PostgreSQL
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_sk_perihal_trgm ON surat_keluar USING gin (perihal gin_trgm_ops)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_sk_tujuan_trgm ON surat_keluar USING gin (tujuan gin_trgm_ops)');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('surat_keluar')) {
            Schema::table('surat_keluar', function (Blueprint $table) {
                $table->dropIndex('idx_sk_status_id');
                $table->dropIndex('idx_sk_status_sifat_id');
                $table->dropIndex('idx_sk_org_unit_status_id');
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS idx_sk_perihal_trgm');
                DB::statement('DROP INDEX IF EXISTS idx_sk_tujuan_trgm');
            }
        }
    }
};
