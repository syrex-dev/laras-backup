<?php

namespace Database\Seeders;

use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratKeluar\Services\TemplateVariableService;
use Illuminate\Database\Seeder;

class TemplateSuratSeeder extends Seeder
{
    public function run(): void
    {
        $variableService = app(TemplateVariableService::class);

        $templates = [
            [
                'nama' => 'Nota Dinas Internal Standar',
                'jenis' => TemplateSurat::JENIS_NOTA_DINAS,
                'kode' => 'ND-STD',
                'format_nomor' => '{urut}/ND/{kode_unit}/{bulan_romawi}/{tahun}',
                'konten_default' => '<p style="text-align: center; font-weight: bold; font-size: 14pt;">NOTA DINAS</p>
<p style="text-align: center; font-size: 11pt;">Nomor: {nomor_surat}</p>
<table style="width: 100%; margin-top: 15px; margin-bottom: 15px;">
    <tr><td style="width: 15%;">Yth.</td><td style="width: 2%;">:</td><td>{tujuan}</td></tr>
    <tr><td>Dari</td><td>:</td><td>{jabatan_penandatangan}</td></tr>
    <tr><td>Hal</td><td>:</td><td>{perihal}</td></tr>
    <tr><td>Tanggal</td><td>:</td><td>{tanggal_surat}</td></tr>
</table>
<hr style="border: 1px solid #000; margin-bottom: 15px;" />
<p>Sehubungan dengan pelaksanaan program kerja di lingkungan {unit_pembuat}, bersama ini kami sampaikan hal-hal sebagai berikut:</p>
<p>1. Pelaksanaan koordinasi berkala antar bagian tata usaha.<br />2. Penyusunan laporan pertanggungjawaban kegiatan triwulan berjalan.</p>
<p>Demikian nota dinas ini disampaikan untuk menjadi perhatian dan ditindaklanjuti sebagaimana mestinya.</p>
<p style="margin-top: 30px; text-align: right;">{jabatan_penandatangan},<br /><br /><br /><br /><strong>{nama_penandatangan}</strong></p>',
                'use_institution_header' => true,
                'is_active' => true,
            ],
            [
                'nama' => 'Surat Perintah Tugas Kedinasan',
                'jenis' => TemplateSurat::JENIS_SURAT_TUGAS,
                'kode' => 'ST-STD',
                'format_nomor' => '{urut}/ST/{kode_unit}/{bulan_romawi}/{tahun}',
                'konten_default' => '<p style="text-align: center; font-weight: bold; font-size: 14pt;">SURAT TUGAS</p>
<p style="text-align: center; font-size: 11pt;">Nomor: {nomor_surat}</p>
<p style="margin-top: 20px;">Pimpinan {nama_instansi} dengan ini menugaskan kepada:</p>
<table style="width: 100%; margin-top: 10px; margin-bottom: 15px;">
    <tr><td style="width: 25%;">Penerima Tugas</td><td style="width: 2%;">:</td><td>{tujuan}</td></tr>
    <tr><td>Perihal Tugas</td><td>:</td><td>{perihal}</td></tr>
    <tr><td>Unit Penyelenggara</td><td>:</td><td>{unit_pembuat}</td></tr>
</table>
<p>Untuk melaksanakan penugasan kedinasan tersebut dengan penuh tanggung jawab serta menyampaikan laporan pelaksanaan kepada pimpinan segera setelah kegiatan selesai.</p>
<p>Surat tugas ini diterbitkan untuk dipergunakan sebagaimana mestinya.</p>
<p style="margin-top: 30px; text-align: right;">Ditetapkan pada tanggal: {tanggal_surat}<br /><br />{jabatan_penandatangan},<br /><br /><br /><br /><strong>{nama_penandatangan}</strong></p>',
                'use_institution_header' => true,
                'is_active' => true,
            ],
            [
                'nama' => 'Surat Edaran Pimpinan',
                'jenis' => TemplateSurat::JENIS_EDARAN,
                'kode' => 'SE-STD',
                'format_nomor' => '{urut}/SE/{kode_unit}/{bulan_romawi}/{tahun}',
                'konten_default' => '<p style="text-align: center; font-weight: bold; font-size: 14pt;">SURAT EDARAN</p>
<p style="text-align: center; font-size: 11pt;">Nomor: {nomor_surat}</p>
<p style="text-align: center; font-weight: bold; margin-top: 10px;">TENTANG<br />{perihal}</p>
<p style="margin-top: 20px;">Kepada Yth. {tujuan}<br />di Lingkungan {nama_instansi}</p>
<p style="margin-top: 15px;">Dalam rangka ketertiban tata administrasi persuratan dan kelancaran kegiatan operasional {unit_pembuat}, disampaikan ketentuan sebagai berikut:</p>
<p>1. Pengelolaan naskah dinas keluar dilakukan secara berjenjang melalui sistem e-office terintegrasi.<br />2. Setiap unit kerja diharapkan memantau tindak lanjut persuratan sebelum tenggat waktu yang ditentukan.</p>
<p>Demikian surat edaran ini dikeluarkan untuk diketahui dan dipedomani bersama.</p>
<p style="margin-top: 30px; text-align: right;">Dikeluarkan pada tanggal: {tanggal_surat}<br /><br />{jabatan_penandatangan},<br /><br /><br /><br /><strong>{nama_penandatangan}</strong></p>',
                'use_institution_header' => true,
                'is_active' => true,
            ],
            [
                'nama' => 'Surat Keputusan (SK) Pejabat',
                'jenis' => TemplateSurat::JENIS_SK,
                'kode' => 'SK-STD',
                'format_nomor' => '{urut}/SK/{kode_unit}/{bulan_romawi}/{tahun}',
                'konten_default' => '<p style="text-align: center; font-weight: bold; font-size: 14pt;">KEPUTUSAN {jabatan_penandatangan}</p>
<p style="text-align: center; font-size: 11pt;">Nomor: {nomor_surat}</p>
<p style="text-align: center; font-weight: bold; margin-top: 10px;">TENTANG<br />{perihal}</p>
<table style="width: 100%; margin-top: 20px;">
    <tr><td style="width: 18%; vertical-align: top;"><strong>Menimbang</strong></td><td style="width: 2%; vertical-align: top;">:</td><td>bahwa untuk kelancaran tugas pokok dan fungsi pada {unit_pembuat}, dipandang perlu menetapkan Keputusan ini;</td></tr>
    <tr><td style="vertical-align: top;"><strong>Mengingat</strong></td><td style="vertical-align: top;">:</td><td>peraturan dan ketentuan perundang-undangan yang berlaku pada {nama_instansi};</td></tr>
</table>
<p style="text-align: center; font-weight: bold; margin-top: 15px;">MEMUTUSKAN:</p>
<p><strong>Menetapkan:</strong></p>
<p><strong>KESATU:</strong> Menetapkan {tujuan} sesuai perihal naskah dinas ini.<br /><strong>KEDUA:</strong> Keputusan ini berlaku sejak tanggal ditetapkan.</p>
<p style="margin-top: 30px; text-align: right;">Ditetapkan pada tanggal: {tanggal_surat}<br /><br />{jabatan_penandatangan},<br /><br /><br /><br /><strong>{nama_penandatangan}</strong></p>',
                'use_institution_header' => true,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tplData) {
            $usedVars = $variableService->extractVariables($tplData['konten_default']);
            TemplateSurat::updateOrCreate(
                ['kode' => $tplData['kode']],
                [
                    ...$tplData,
                    'variabel' => array_values($usedVars),
                ]
            );
        }
    }
}
