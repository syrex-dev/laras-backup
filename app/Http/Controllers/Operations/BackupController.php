<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Operations\Actions\RunBackupAction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function run(RunBackupAction $backupAction)
    {
        try {
            $backupAction->execute();

            return redirect()->back()->with('success', 'Backup sistem berhasil dimulai di latar belakang.');
        } catch (\Exception $e) {
            Log::error('Backup Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menjalankan backup: '.$e->getMessage());
        }
    }
}
