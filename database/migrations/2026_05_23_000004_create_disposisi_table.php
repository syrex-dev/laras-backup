<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->constrained('surat_masuk')->onDelete('cascade');
            $table->foreignId('pengirim_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('penerima_id')->constrained('users')->onDelete('cascade');
            $table->text('instruksi');
            $table->enum('status', ['belum_dibaca', 'sudah_dibaca', 'selesai'])->default('belum_dibaca');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi');
    }
};
