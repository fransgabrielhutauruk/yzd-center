<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Pastikan akun pengguna sudah aktif sebelum mengakses fitur tertentu.
     * Jika akun pendaftar belum diverifikasi (status != 'active'),
     * pengguna hanya diizinkan mengakses halaman verifikasi KYC.
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

        if ($user && !$user->isAdmin() && $user->status !== 'active') {
            if ($request->routeIs('investor.account*') || $request->routeIs('investor.copytrade*')) {
                return $next($request);
            }

            return redirect()->route('investor.account')->with('error', 'Akun Anda belum aktif. Harap tunggu atau lengkapi proses verifikasi dokumen dan pendaftaran Copytrade.');
        }

        return $next($request);
    }
}
