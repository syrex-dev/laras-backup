<?php

namespace Database\Seeders;

use App\Domain\Disposisi\Models\InstructionTemplate;
use Illuminate\Database\Seeder;

class InstructionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'kode' => 'TL',
                'nama' => 'Tindak lanjuti segera sesuai ketentuan',
                'deskripsi' => 'Instruksi untuk memproses atau menyelesaikan isi naskah dinas dengan segera.',
                'urutan' => 1,
                'is_active' => true,
            ],
            [
                'kode' => 'INFO',
                'nama' => 'Untuk diketahui / monitor perkembangan',
                'deskripsi' => 'Instruksi sekadar informasi dan pemantauan tanpa kewajiban surat balasan.',
                'urutan' => 2,
                'is_active' => true,
            ],
            [
                'kode' => 'TELA',
                'nama' => 'Pelajari dan siapkan bahan telaah / masukan',
                'deskripsi' => 'Instruksi untuk mengkaji substansi naskah dan menyusun bahan masukan bagi pimpinan.',
                'urutan' => 3,
                'is_active' => true,
            ],
            [
                'kode' => 'KONS',
                'nama' => 'Siapkan konsep surat balasan / tanggapan',
                'deskripsi' => 'Instruksi untuk menyusun draf surat keluar sebagai balasan atas surat masuk ini.',
                'urutan' => 4,
                'is_active' => true,
            ],
            [
                'kode' => 'KOOR',
                'nama' => 'Koordinasikan dengan unit / pihak terkait',
                'deskripsi' => 'Instruksi untuk melakukan koordinasi lintas bagian sebelum mengambil keputusan.',
                'urutan' => 5,
                'is_active' => true,
            ],
            [
                'kode' => 'WAKIL',
                'nama' => 'Hadiri / wakili pimpinan pada acara ini',
                'deskripsi' => 'Instruksi pendelegasian kehadiran pertemuan, rapat, atau undangan resmi.',
                'urutan' => 6,
                'is_active' => true,
            ],
            [
                'kode' => 'ARSIP',
                'nama' => 'Simpan / masukkan ke dalam arsip dinas',
                'deskripsi' => 'Instruksi pengarsipan dokumen setelah proses administrasi selesai.',
                'urutan' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $t) {
            InstructionTemplate::updateOrCreate(
                ['kode' => $t['kode']],
                $t
            );
        }
    }
}
