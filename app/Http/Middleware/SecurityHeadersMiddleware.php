<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Menambahkan header keamanan standar pada respons HTTP
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // 1. Mencegah MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 2. Mengontrol informasi referrer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 3. Mencegah clickjacking (iframe embedding dari luar domain)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 4. Mencegah XSS pada browser lama
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 5. Content-Security-Policy (CSP) yang kompatibel dengan Vite dan Inertia
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:* http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:*",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com http://localhost:* http://127.0.0.1:*",
            "font-src 'self' data: https://fonts.gstatic.com http://localhost:* http://127.0.0.1:*",
            "img-src 'self' data: blob: http://localhost:* http://127.0.0.1:*",
            "connect-src 'self' http://localhost:* http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:*",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        $cspPolicy = implode('; ', $cspDirectives);

        $headerName = config('security.csp_report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($headerName, $cspPolicy);

        return $response;
    }
}
