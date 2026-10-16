<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Pastikan akun pengguna tidak dinonaktifkan (is_active) oleh administrator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isAdmin() && !$user->is_active) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi admin.',
            ]);
        }

        return $next($request);
    }
}
