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
        Schema::table('profil_organisasi', function (Blueprint $table) {
            $table->string('email')->nullable()->after('alamat');
            $table->string('telepon')->nullable()->after('email');
            $table->string('path_stempel')->nullable()->after('path_logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_organisasi', function (Blueprint $table) {
            $table->dropColumn(['email', 'telepon', 'path_stempel']);
        });
    }
};
