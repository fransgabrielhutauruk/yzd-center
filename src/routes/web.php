<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DocumentStreamController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Seluruh rute web publik, autentikasi tamu, streaming dokumen, dan notifikasi.
| Rute modular Portal Administrator dan Portal Member dimuat dari file terpisah:
| - routes/admin.php
| - routes/member.php
*/

// Halaman Utama: Redirect dinamis berdasarkan status autentikasi dan peran pengguna
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('member.dashboard');
    }

    return redirect()->route('login');
})->name('home');

// Autentikasi Tamu (Guest Routes)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/register/check-referral', [RegisteredUserController::class, 'checkReferral'])
        ->name('register.check_referral');
});

// Logout (Authenticated)
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Ganti Password (Shared — semua role terotentikasi)
Route::post('/password/change', [ChangePasswordController::class, 'update'])
    ->middleware('auth')
    ->name('password.change');

// Akses Berkas Privat Terotentikasi (Akses terkontrol untuk Admin & Pemilik Dokumen)
Route::get('/documents/{id}', [DocumentStreamController::class, 'show'])
    ->middleware('auth')
    ->name('documents.show');
Route::get('/documents/claims/{id}', [DocumentStreamController::class, 'showClaimProof'])
    ->middleware('auth')
    ->name('documents.claim_proof');
Route::get('/documents/copytrade/{id}', [DocumentStreamController::class, 'showCopytradeAgreement'])
    ->middleware('auth')
    ->name('documents.copytrade');
Route::get('/documents/commissions/{id}/proof', [DocumentStreamController::class, 'showCommissionProof'])
    ->middleware('auth')
    ->name('documents.commission_proof');
Route::get('/documents/commissions/{id}/receipt', [DocumentStreamController::class, 'showCommissionReceipt'])
    ->middleware('auth')
    ->name('documents.commission_receipt');
Route::get('/documents/profit-master/{id}', [DocumentStreamController::class, 'showProfitImage'])
    ->middleware('auth')
    ->name('documents.profit_image');
Route::get('/documents/tickets/{id}', [DocumentStreamController::class, 'showTiketAttachment'])
    ->middleware('auth')
    ->name('documents.ticket_attachment');
Route::get('/documents/master-fee/{id}', [DocumentStreamController::class, 'showMasterFeeProof'])
    ->middleware('auth')
    ->name('documents.master_fee_proof');
Route::get('/documents/master-fee/{id}/myfxbook', [DocumentStreamController::class, 'showMyfxbookProof'])
    ->middleware('auth')
    ->name('documents.master_fee_myfxbook');
Route::get('/documents/master-fee/{id}/wd', [DocumentStreamController::class, 'showWdProof'])
    ->middleware('auth')
    ->name('documents.master_fee_wd');

// Notifikasi In-App (Terotentikasi)
Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark_all_read');
});

// Modul Rute Terpisah (Admin & Member)
require __DIR__ . '/admin.php';
require __DIR__ . '/member.php';
