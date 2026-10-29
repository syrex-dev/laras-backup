<?php

namespace App\Domain\Lisensi\Middlewares;

use App\Domain\Lisensi\Models\LicenseState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnforceLicenseReadOnly
{
    /**
     * Rute-rute mutasi yang dikecualikan dari pemblokiran mode baca-saja,
     * seperti login/logout, rute pembaruan lisensi oleh admin, dan unduhan/ekspor.
     */
    protected array $except = [
        'login*',
        'logout',
        'admin/license*',
        '*/export*',
        'export/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('license_states')) {
            return $next($request);
        }

        $license = LicenseState::current();

        if ($license->isReadOnly()) {
            // Metode aman (baca, cari, lihat) selalu diizinkan
            if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
                return $next($request);
            }

            // Periksa rute yang dikecualikan (misal admin update lisensi)
            foreach ($this->except as $pattern) {
                if ($request->is($pattern)) {
                    return $next($request);
                }
            }

            $message = 'Masa berlaku lisensi E-Office telah kedaluwarsa. Sistem berjalan dalam Mode Baca-Saja (Read-Only). Anda tetap dapat membaca, mencari, dan mengekspor seluruh data. Hubungi administrator instansi untuk memperbarui lisensi.';

            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'message' => $message,
                    'is_read_only' => true,
                    'status' => 'expired',
                ], 403);
            }

            return back()->with('error', $message);
        }

        return $next($request);
    }
}
