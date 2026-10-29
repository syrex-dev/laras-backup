<?php

namespace App\Domain\Lisensi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseState extends Model
{
    use HasFactory;

    protected $table = 'license_states';

    protected $fillable = [
        'license_key',
        'institution_slug',
        'plan',
        'status',
        'expires_at',
        'grace_period_days',
        'max_users',
        'max_storage_bytes',
        'active_features',
        'last_verified_at',
        'signature_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'grace_period_days' => 'integer',
        'max_users' => 'integer',
        'max_storage_bytes' => 'integer',
        'active_features' => 'array',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(
            ['institution_slug' => 'poltek-demo'],
            [
                'license_key' => 'LIC-DEMO-STANDAR-2026-OK',
                'plan' => 'standar',
                'status' => 'active',
                'expires_at' => Carbon::now()->addYear(),
                'grace_period_days' => 14,
                'max_users' => 100,
                'max_storage_bytes' => 53687091200, // 50 GB
                'active_features' => [
                    'ocr' => true,
                    'dashboard' => true,
                    'delegations' => true,
                    'tte' => true,
                    'whatsapp' => true,
                    'sso' => false,
                    'api' => false,
                ],
                'last_verified_at' => Carbon::now(),
                'signature_hash' => hash('sha256', 'LIC-DEMO-STANDAR-2026-OK|poltek-demo|active'),
            ]
        );
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at->copy()->addDays($this->grace_period_days))) {
            return true;
        }

        return false;
    }

    public function isInGracePeriod(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        $now = Carbon::now();

        return $now->isAfter($this->expires_at) && $now->isBefore($this->expires_at->copy()->addDays($this->grace_period_days));
    }

    public function isReadOnly(): bool
    {
        return $this->isExpired();
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->active_features ?? [];

        return ! empty($features[$feature]);
    }
}
