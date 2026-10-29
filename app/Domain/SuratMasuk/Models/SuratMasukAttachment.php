<?php

namespace App\Domain\SuratMasuk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratMasukAttachment extends Model
{
    use HasFactory;

    protected $table = 'surat_masuk_attachments';

    protected $fillable = [
        'surat_masuk_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'description',
        'uploader_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf' || str_ends_with(strtolower($this->file_name), '.pdf');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
