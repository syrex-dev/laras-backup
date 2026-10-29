<?php

namespace App\Http\Middleware;

use App\Domain\Instansi\Models\InstitutionProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        // Dalam unit testing, lewati kecuali dites secara eksplisit
        if (app()->runningUnitTests() && ! $request->header('X-Test-Enforce-Setup')) {
            return $next($request);
        }

        if (! Schema::hasTable('institution_profiles')) {
            return $next($request);
        }

        $profile = InstitutionProfile::current();

        // Rute aset dan health check dilewati
        if ($request->is('build*') || $request->is('assets*') || $request->is('up')) {
            return $next($request);
        }

        // Jika mengakses rute setup
        if ($request->is('setup*')) {
            if ($profile->is_configured) {
                return redirect()->route('dashboard');
            }

            return $next($request);
        }

        // Jika sistem belum dikonfigurasi, arahkan semua rute ke wizard setup
        if (! $profile->is_configured) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
