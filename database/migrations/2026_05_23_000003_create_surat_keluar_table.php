<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_keluar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->nullable();
            $table->foreignId('klasifikasi_id')->constrained('klasifikasi_surat')->onDelete('restrict');
            $table->string('tujuan');
            $table->string('perihal');
            $table->text('isi')->nullable();
            $table->enum('status_approval', ['draft', 'approved', 'rejected'])->default('draft');
            $table->string('file_pdf')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keluar');
    }
};
