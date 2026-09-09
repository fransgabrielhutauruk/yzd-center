<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Investor\InvestorDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman Utama: Redirect dinamis berdasarkan status autentikasi dan peran pengguna
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        return ($user && $user->isAdmin())
            ? redirect()->route('admin.dashboard')
            : redirect()->route('investor.dashboard');
    }

    return redirect()->route('login');
})->name('home');

// Autentikasi Tamu (Guest Routes)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// Logout (Authenticated)
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Portal Administrator
Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Manajemen Tautan Undangan
    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{id}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
});

// Portal Investor & Afiliator
Route::middleware(['auth', 'role:investor,affiliator'])->prefix('investor')->name('investor.')->group(function () {
    Route::get('/dashboard', [InvestorDashboardController::class, 'index'])->name('dashboard');
});
