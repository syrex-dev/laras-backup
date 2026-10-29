<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class SuratKeluar extends Model
{
    use SoftDeletes;

    protected $table = 'surat_keluar';

    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'klasifikasi_id',
        'tujuan',
        'perihal',
        'lampiran',        // ← keterangan lampiran surat
        'isi',
        'status_approval',
        'file_pdf',
        'path_qrcode',
        'qr_token',
        'user_id',
        'penyetuju_id',
        'templat_surat_id',
    ];

    // Relationship to template surat
    public function templatSurat()
    {
        return $this->belongsTo(TemplatSurat::class, 'templat_surat_id');
    }

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    public function klasifikasi()
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'klasifikasi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'penyetuju_id');
    }
}
