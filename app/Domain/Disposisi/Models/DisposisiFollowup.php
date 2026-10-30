<?php

namespace App\Domain\Disposisi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 */
class DisposisiFollowup extends Model
{
    use HasFactory;

    protected $table = 'disposisi_followups';

    protected $fillable = [
        'disposisi_recipient_id',
        'user_id',
        'catatan',
        'status_perubahan',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(DisposisiRecipient::class, 'disposisi_recipient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DisposisiAttachment::class, 'disposisi_followup_id');
    }
}
