<?php

namespace App\Domain\Licensing\Services;

use App\Models\User;

class LicenseManager
{
    /**
     * Default license state saat belum terintegrasi API server lisensi vendor.
     */
    public function getLicenseState(): array
    {
        return [
            'status'            => 'active', // active, grace_period, expired
            'tier'              => 'standar', // dasar, standar, lanjutan
            'tier_name'         => 'Standar (Politeknik & Kampus Menengah)',
            'max_users'         => 100,
            'max_storage_gb'    => 50,
            'expires_at'        => now()->addYear()->format('Y-m-d'),
            'days_remaining'    => 365,
            'entitlements'      => [
                'surat_masuk'         => true,
                'surat_keluar'        => true,
                'disposisi'           => true,
                'tte_pki'             => true,
                'qr_verification'     => true,
                'ocr'                 => true,
                'whatsapp_gateway'    => false,
                'sso'                 => false,
                'audit_logs'          => true,
                'backup_restore'      => true,
            ],
        ];
    }

    /**
     * Periksa apakah aplikasi berada dalam status Read-Only (FR-LIC-04).
     */
    public function isReadOnly(): bool
    {
        $state = $this->getLicenseState();
        return $state['status'] === 'expired';
    }

    /**
     * Periksa apakah suatu fitur diizinkan berdasarkan paket langganan (FR-LIC-06).
     */
    public function hasFeature(string $featureKey): bool
    {
        $state = $this->getLicenseState();
        return !empty($state['entitlements'][$featureKey]);
    }

    /**
     * Periksa kuota pengguna aktif (FR-LIC-07).
     */
    public function checkUserQuota(): array
    {
        $state = $this->getLicenseState();
        $currentUserCount = User::count();
        $maxUsers = $state['max_users'];
        $usagePercent = round(($currentUserCount / max(1, $maxUsers)) * 100, 1);

        return [
            'current'       => $currentUserCount,
            'max'           => $maxUsers,
            'usage_percent' => $usagePercent,
            'is_exceeded'   => $currentUserCount > $maxUsers,
            'is_warning'    => $usagePercent >= 80,
        ];
    }
}
