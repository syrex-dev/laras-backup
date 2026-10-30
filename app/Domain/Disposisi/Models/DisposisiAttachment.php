<?php

namespace App\Domain\Disposisi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 */
class DisposisiAttachment extends Model
{
    use HasFactory;

    protected $table = 'disposisi_attachments';

    protected $fillable = [
        'disposisi_followup_id',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function followup(): BelongsTo
    {
        return $this->belongsTo(DisposisiFollowup::class, 'disposisi_followup_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
