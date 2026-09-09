<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\LegalContentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DocumentStreamController;
use App\Http\Controllers\Investor\InvestorDashboardController;
use App\Http\Controllers\Investor\KycController;
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

// Akses Berkas Privat Terotentikasi (Akses terkontrol untuk Admin & Pemilik Dokumen)
Route::get('/documents/{id}', [DocumentStreamController::class, 'show'])
    ->middleware('auth')
    ->name('documents.show');

// Portal Administrator
Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Manajemen Tautan Undangan
    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{id}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

    // Verifikasi Dokumen KYC & Legalitas
    Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
    Route::post('/kyc/documents/{id}/verify', [AdminKycController::class, 'verify'])->name('kyc.verify');

    // Pengaturan Dokumen Legalitas (Terms & Privacy)
    Route::get('/settings/legal', [LegalContentController::class, 'index'])->name('settings.legal');
    Route::post('/settings/legal', [LegalContentController::class, 'update'])->name('settings.legal.update');
});

// Portal Investor & Afiliator
Route::middleware(['auth', 'role:investor,affiliator'])->prefix('investor')->name('investor.')->group(function () {
    Route::get('/dashboard', [InvestorDashboardController::class, 'index'])->name('dashboard');

    // Status KYC & Unggah Ulang Berkas
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc.index');
    Route::post('/kyc/reupload/{type}', [KycController::class, 'reupload'])->name('kyc.reupload');
});
