<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(
        string $action,
        Model|string $subject,
        string $description,
        ?array $properties = null,
        ?User $causer = null
    ): ActivityLog {
        return ActivityLog::record($action, $subject, $description, $properties, $causer);
    }
}
