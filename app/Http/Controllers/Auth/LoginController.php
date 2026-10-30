<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $props = [];
        if (app()->environment(['local', 'testing'])) {
            $props['showDemoAccounts'] = true;
        }

        return Inertia::render('Auth/Login', $props);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            AuditLogger::log(
                action: 'auth.login',
                subject: $user,
                description: "Pengguna {$user->name} ({$user->email}) berhasil login ke sistem",
                causer: $user
            );

            if ($request->header('X-Inertia')) {
                return Inertia::location(session()->pull('url.intended', route('dashboard')));
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditLogger::log(
                action: 'auth.logout',
                subject: $user,
                description: "Pengguna {$user->name} ({$user->email}) keluar dari sistem",
                causer: $user
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
