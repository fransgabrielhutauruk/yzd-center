<?php

use App\Http\Controllers\Admin\AdminAffiliateController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\AdminReferralClaimController;
use App\Http\Controllers\Admin\CommissionClaimController as AdminCommissionClaimController;
use App\Http\Controllers\Admin\CopytradeController as AdminCopytradeController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\LegalContentController;
use App\Http\Controllers\Admin\PengaturanMasterController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DocumentStreamController;
use App\Http\Controllers\Investor\AffiliateNetworkController;
use App\Http\Controllers\Investor\CommissionClaimController as InvestorCommissionClaimController;
use App\Http\Controllers\Investor\CopytradeController as InvestorCopytradeController;
use App\Http\Controllers\Investor\InvestorDashboardController;
use App\Http\Controllers\Investor\KycController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman Utama: Redirect dinamis berdasarkan status autentikasi dan peran pengguna
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user && $user->status !== 'active') {
            return redirect()->route('investor.kyc');
        }

        return redirect()->route('investor.dashboard');
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

// Notifikasi In-App (Terotentikasi)
Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark_all_read');
});

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

    // Verifikasi Copytrade
    Route::get('/copytrade', [AdminCopytradeController::class, 'index'])->name('copytrade.index');
    Route::post('/copytrade/{id}/verify', [AdminCopytradeController::class, 'verify'])->name('copytrade.verify');

    // Pengaturan Master
    Route::get('/settings/master', [PengaturanMasterController::class, 'index'])->name('settings.master');
    Route::post('/settings/master', [PengaturanMasterController::class, 'update'])->name('settings.master.update');
    Route::get('/settings/master/download-pdf/{tier}', [PengaturanMasterController::class, 'downloadPdf'])->name('settings.master.download_pdf');

    // Verifikasi Permohonan Klaim Referral Susulan
    Route::get('/referral-claims', [AdminReferralClaimController::class, 'index'])->name('referral_claims.index');
    Route::post('/referral-claims/{id}/verify', [AdminReferralClaimController::class, 'verify'])->name('referral_claims.verify');

    // Rekapitulasi Jaringan Mitra Affiliator
    Route::get('/affiliates', [AdminAffiliateController::class, 'index'])->name('affiliates.index');

    // Pencairan Komisi Afiliasi (Modul 05)
    Route::get('/commissions', [AdminCommissionClaimController::class, 'index'])->name('commissions.index');
    Route::post('/commissions/{id}/process', [AdminCommissionClaimController::class, 'process'])->name('commissions.process');

    // Pengaturan Dokumen Legalitas (Terms & Privacy)
    Route::get('/settings/legal', [LegalContentController::class, 'index'])->name('settings.legal');
    Route::post('/settings/legal', [LegalContentController::class, 'update'])->name('settings.legal.update');
});

// Portal Investor & Afiliator
Route::middleware(['auth', 'role:investor,affiliator'])->prefix('investor')->name('investor.')->group(function () {
    // Fitur yang mewajibkan akun sudah berstatus active
    Route::middleware('ensure.active')->group(function () {
        Route::get('/dashboard', [InvestorDashboardController::class, 'index'])->name('dashboard');

        // Jaringan Kemitraan & Klaim Susulan (Khusus Affiliator)
        Route::get('/affiliates', [AffiliateNetworkController::class, 'index'])->name('affiliates.index');
        Route::post('/affiliates/claims', [AffiliateNetworkController::class, 'storeClaim'])->name('affiliates.claims.store');
        Route::get('/affiliates/eligible-downlines', [AffiliateNetworkController::class, 'eligibleDownlines'])->name('affiliates.eligible_downlines');
        Route::get('/affiliates/check-downline', [AffiliateNetworkController::class, 'checkDownline'])->name('affiliates.check_downline');

        // Klaim Komisi Afiliasi (Khusus Affiliator - Modul 05)
        Route::get('/commissions', [InvestorCommissionClaimController::class, 'index'])->name('commissions.index');
        Route::post('/commissions', [InvestorCommissionClaimController::class, 'store'])->name('commissions.store');
        Route::get('/commissions/eligible-downlines', [InvestorCommissionClaimController::class, 'eligibleDownlines'])->name('commissions.eligible_downlines');
    });

    // Status KYC & Unggah Ulang Berkas (Selalu dapat diakses oleh investor/afiliator)
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc');
    Route::post('/kyc/reupload/{type}', [KycController::class, 'reupload'])->name('kyc.reupload');

    // Manajemen Akun Copytrade
    Route::get('/copytrade', [InvestorCopytradeController::class, 'index'])->name('copytrade.index');
    Route::post('/copytrade', [InvestorCopytradeController::class, 'store'])->name('copytrade.store');
    Route::match(['get', 'post'], '/copytrade/template-perjanjian/{tier}', [InvestorCopytradeController::class, 'downloadTemplate'])->name('copytrade.template');
    Route::get('/copytrade/panduan-pdf/{tier}', [InvestorCopytradeController::class, 'downloadGuidePdf'])->name('copytrade.guide_pdf');
});
