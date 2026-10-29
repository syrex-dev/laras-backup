<?php

namespace App\Domain\Operations\Actions;

use Illuminate\Support\Facades\Artisan;

class RunBackupAction
{
    public function execute(): void
    {
        Artisan::call('backup:run');
    }
}
