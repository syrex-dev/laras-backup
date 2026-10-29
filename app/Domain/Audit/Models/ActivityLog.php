<?php

namespace App\Domain\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    public $timestamps = false;

    protected $fillable = [
        'causer_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function () {
            throw new \RuntimeException('TABEL activity_logs BERSIFAT APPEND-ONLY: Operasi UPDATE dilarang.');
        });

        static::deleting(function () {
            throw new \RuntimeException('TABEL activity_logs BERSIFAT APPEND-ONLY: Operasi DELETE dilarang.');
        });
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public static function record(
        string $action,
        Model|string $subject,
        string $description,
        ?array $properties = null,
        ?User $causer = null
    ): self {
        $subjectType = is_string($subject) ? $subject : get_class($subject);
        $subjectId = is_string($subject) ? 0 : ($subject->getKey() ?? 0);
        $causerId = $causer ? $causer->id : Auth::id();

        return static::create([
            'causer_id' => $causerId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'properties' => $properties ?? [],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
