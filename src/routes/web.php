<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminAffiliateController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReferralClaimController;
use App\Http\Controllers\Admin\CommissionClaimController as AdminCommissionClaimController;
use App\Http\Controllers\Admin\CopytradeController as AdminCopytradeController;
use App\Http\Controllers\Admin\EducationGlossaryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\HelpCenterController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\LegalContentController;
use App\Http\Controllers\Admin\MasterFeeController as AdminMasterFeeController;
use App\Http\Controllers\Admin\ProfitMasterController as AdminProfitMasterController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\Settings\PasswordController as AdminPasswordController;
use App\Http\Controllers\Admin\TiketBantuanController as AdminTiketBantuanController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DocumentStreamController;
use App\Http\Controllers\Member\AccountController;
use App\Http\Controllers\Member\AffiliateNetworkController;
use App\Http\Controllers\Member\CommissionClaimController as MemberCommissionClaimController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\EducationController as MemberEducationController;
use App\Http\Controllers\Member\InvestmentPortfolioController;
use App\Http\Controllers\Member\MasterFeeController as MemberMasterFeeController;
use App\Http\Controllers\Member\PasswordController as MemberPasswordController;
use App\Http\Controllers\Member\ProfitMasterController as MemberProfitMasterController;
use App\Http\Controllers\Member\TiketBantuanController as MemberTiketBantuanController;
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
Route::get('/documents/balance-proof/{id}', [DocumentStreamController::class, 'showBalanceProof'])
    ->middleware('auth')
    ->name('documents.balance_proof');

// Notifikasi In-App (Terotentikasi)
Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark_all_read');
});

// Portal Administrator
Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Manajemen Pengguna (Member)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle_status');

    // Manajemen Tautan Undangan
    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/batch-delete', [InvitationController::class, 'batchDestroy'])->name('invitations.batch_destroy');
    Route::delete('/invitations/{id}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

    // Verifikasi Pendaftaran (KYC & Akun Investor/Affiliator)
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/registrations/documents/{id}/verify', [RegistrationController::class, 'verify'])->name('registrations.verify');

    // Verifikasi Copytrade
    Route::get('/copytrade', [AdminCopytradeController::class, 'index'])->name('copytrade.index');
    Route::post('/copytrade/{id}/verify', [AdminCopytradeController::class, 'verify'])->name('copytrade.verify');
    Route::get('/copytrade/{id}/detail', [AdminCopytradeController::class, 'detail'])->whereNumber('id')->name('copytrade.detail');

    // Ganti Password Admin
    Route::get('/settings/password', [AdminPasswordController::class, 'index'])->name('settings.password');

    // Verifikasi Permohonan Klaim Referral Susulan
    Route::get('/referral-claims', [AdminReferralClaimController::class, 'index'])->name('referral_claims.index');
    Route::post('/referral-claims/{id}/verify', [AdminReferralClaimController::class, 'verify'])->name('referral_claims.verify');

    // Rekapitulasi Jaringan Mitra Affiliator
    Route::get('/affiliates', [AdminAffiliateController::class, 'index'])->name('affiliates.index');

    // Pencairan Komisi Afiliasi (Modul 05)
    Route::get('/commissions', [AdminCommissionClaimController::class, 'index'])->name('commissions.index');
    Route::post('/commissions/{id}/process', [AdminCommissionClaimController::class, 'process'])->name('commissions.process');

    // Materi Promosi (Profit Master)
    Route::get('/profit-master', [AdminProfitMasterController::class, 'index'])->name('profit_master.index');
    Route::get('/profit-master/feed', [AdminProfitMasterController::class, 'feed'])->name('profit_master.feed');
    Route::post('/profit-master', [AdminProfitMasterController::class, 'store'])->name('profit_master.store');
    Route::match(['put', 'patch', 'post'], '/profit-master/{post}', [AdminProfitMasterController::class, 'update'])->name('profit_master.update');
    Route::delete('/profit-master/{post}', [AdminProfitMasterController::class, 'destroy'])->name('profit_master.destroy');

    // Tiket Bantuan & Pelaporan Kendala
    Route::get('/tickets', [AdminTiketBantuanController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTiketBantuanController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/replies', [AdminTiketBantuanController::class, 'reply'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/status', [AdminTiketBantuanController::class, 'updateStatus'])->name('tickets.status');

    // Pengaturan Dokumen Legalitas (Terms & Privacy)
    Route::get('/settings/legal', [LegalContentController::class, 'index'])->name('settings.legal');
    Route::post('/settings/legal', [LegalContentController::class, 'update'])->name('settings.legal.update');

    // Pengaturan Master Fee & Kelola Tagihan
    Route::get('/master-fee', [AdminMasterFeeController::class, 'index'])->name('master_fee.index');
    Route::post('/master-fee/{id}/verify', [AdminMasterFeeController::class, 'verify'])->name('master_fee.verify');
    Route::post('/master-fee/{id}/reject', [AdminMasterFeeController::class, 'reject'])->name('master_fee.reject');
    Route::get('/settings/master-fee', [AdminMasterFeeController::class, 'settings'])->name('settings.master_fee');
    Route::put('/settings/master-fee', [AdminMasterFeeController::class, 'updateSettings'])->name('settings.master_fee.update');

    // Audit Log Aktivitas Sistem
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity_logs.export');

    // Edukasi & Bantuan (Modul 06)
    Route::get('/education/glossary', [EducationGlossaryController::class, 'index'])->name('education.glossary.index');
    Route::post('/education/glossary', [EducationGlossaryController::class, 'store'])->name('education.glossary.store');
    Route::put('/education/glossary/{id}', [EducationGlossaryController::class, 'update'])->name('education.glossary.update');
    Route::delete('/education/glossary/{id}', [EducationGlossaryController::class, 'destroy'])->name('education.glossary.destroy');
    Route::delete('/education/glossary', [EducationGlossaryController::class, 'batchDestroy'])->name('education.glossary.batch-destroy');

    Route::get('/education/help', [HelpCenterController::class, 'index'])->name('education.help.index');
    Route::get('/education/help/articles/create', [HelpCenterController::class, 'createArticle'])->name('education.help.articles.create');
    Route::get('/education/help/articles/{id}/edit', [HelpCenterController::class, 'editArticle'])->name('education.help.articles.edit');
    Route::post('/education/help/upload-media', [HelpCenterController::class, 'uploadMedia'])->name('education.help.upload-media');
    Route::post('/education/help/categories', [HelpCenterController::class, 'storeCategory'])->name('education.help.categories.store');
    Route::put('/education/help/categories/{id}', [HelpCenterController::class, 'updateCategory'])->name('education.help.categories.update');
    Route::delete('/education/help/categories/{id}', [HelpCenterController::class, 'destroyCategory'])->name('education.help.categories.destroy');

    Route::post('/education/help/articles', [HelpCenterController::class, 'storeArticle'])->name('education.help.articles.store');
    Route::put('/education/help/articles/{id}', [HelpCenterController::class, 'updateArticle'])->name('education.help.articles.update');
    Route::delete('/education/help/articles/{id}', [HelpCenterController::class, 'destroyArticle'])->name('education.help.articles.destroy');

    // FAQ (Tanya Jawab)
    Route::get('/education/faq', [FaqController::class, 'index'])->name('education.faq.index');
    Route::post('/education/faq', [FaqController::class, 'store'])->name('education.faq.store');
    Route::put('/education/faq/{id}', [FaqController::class, 'update'])->name('education.faq.update');
    Route::delete('/education/faq/{id}', [FaqController::class, 'destroy'])->name('education.faq.destroy');
    Route::delete('/education/faq', [FaqController::class, 'batchDestroy'])->name('education.faq.batch-destroy');

    Route::post('/education/faq/topics', [FaqController::class, 'storeTopic'])->name('education.faq.topics.store');
    Route::put('/education/faq/topics/{id}', [FaqController::class, 'updateTopic'])->name('education.faq.topics.update');
    Route::delete('/education/faq/topics/{id}', [FaqController::class, 'destroyTopic'])->name('education.faq.topics.destroy');
});

// Portal Member
Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    // Fitur yang mewajibkan akun sudah berstatus active
    Route::middleware('ensure.active')->group(function () {
        Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

        // Jaringan Kemitraan & Klaim Susulan
        Route::get('/affiliates', [AffiliateNetworkController::class, 'index'])->name('affiliates.index');
        Route::post('/affiliates/claims', [AffiliateNetworkController::class, 'storeClaim'])->name('affiliates.claims.store');
        Route::get('/affiliates/eligible-downlines', [AffiliateNetworkController::class, 'eligibleDownlines'])->name('affiliates.eligible_downlines');
        Route::get('/affiliates/check-downline', [AffiliateNetworkController::class, 'checkDownline'])->name('affiliates.check_downline');

        // Klaim Komisi Afiliasi
        Route::get('/commissions', [MemberCommissionClaimController::class, 'index'])->name('commissions.index');
        Route::post('/commissions', [MemberCommissionClaimController::class, 'store'])->name('commissions.store');
        Route::get('/commissions/eligible-downlines', [MemberCommissionClaimController::class, 'eligibleDownlines'])->name('commissions.eligible_downlines');

        // Tagihan Master Fee
        Route::get('/master-fee', [MemberMasterFeeController::class, 'index'])->name('master_fee.index');
        Route::get('/master-fee/{id}', [MemberMasterFeeController::class, 'show'])->name('master_fee.show');
        Route::post('/master-fee/{id}/proof', [MemberMasterFeeController::class, 'uploadProof'])->name('master_fee.proof');
    });

    // Informasi Akun & Unggah Ulang Berkas Verifikasi (Selalu dapat diakses oleh member)
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::post('/account/reupload/{type}', [AccountController::class, 'reupload'])->name('account.reupload');

    // Ganti Password Member
    Route::get('/account/password', [MemberPasswordController::class, 'index'])->name('account.password');

    // Portofolio Investasi (Terpadu: Akun Trading Tier 1/2/3, Kinerja Saldo & BEP)
    Route::get('/investments', [InvestmentPortfolioController::class, 'index'])->name('investments.index');
    Route::post('/investments', [InvestmentPortfolioController::class, 'storeAccount'])->name('investments.store');
    Route::post('/investments/balance', [InvestmentPortfolioController::class, 'storeBalance'])->name('investments.balance.store');
    Route::put('/investments/balance/{id}', [InvestmentPortfolioController::class, 'updateBalance'])->name('investments.balance.update');
    Route::delete('/investments/balance/{id}', [InvestmentPortfolioController::class, 'destroyBalance'])->name('investments.balance.destroy');
    Route::match(['get', 'post'], '/investments/template-perjanjian/{tier}', [InvestmentPortfolioController::class, 'downloadTemplate'])->name('investments.template');

    // Backward compatibility redirects & route aliases
    Route::get('/copytrade', fn () => redirect()->route('member.investments.index'))->name('copytrade.index');
    Route::get('/copytrade/balance', fn () => redirect()->route('member.investments.index'))->name('copytrade.balance.index');
    Route::post('/copytrade', [InvestmentPortfolioController::class, 'storeAccount'])->name('copytrade.store');
    Route::post('/copytrade/balance', [InvestmentPortfolioController::class, 'storeBalance'])->name('copytrade.balance.store');
    Route::put('/copytrade/balance/{id}', [InvestmentPortfolioController::class, 'updateBalance'])->name('copytrade.balance.update');
    Route::delete('/copytrade/balance/{id}', [InvestmentPortfolioController::class, 'destroyBalance'])->name('copytrade.balance.destroy');
    Route::match(['get', 'post'], '/copytrade/template-perjanjian/{tier}', [InvestmentPortfolioController::class, 'downloadTemplate'])->name('copytrade.template');

    // Materi Promosi (Profit Master) - Selalu dapat diakses oleh semua member
    Route::get('/profit-master', [MemberProfitMasterController::class, 'index'])->name('profit_master.index');
    Route::get('/profit-master/feed', [MemberProfitMasterController::class, 'feed'])->name('profit_master.feed');

    // Tiket Bantuan & Pelaporan Kendala (Dapat diakses seluruh member, termasuk pending KYC)
    Route::get('/tickets', [MemberTiketBantuanController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [MemberTiketBantuanController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [MemberTiketBantuanController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [MemberTiketBantuanController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/replies', [MemberTiketBantuanController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/resolve', [MemberTiketBantuanController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket}/reopen', [MemberTiketBantuanController::class, 'reopen'])->name('tickets.reopen');

    // Edukasi, Pusat Bantuan & Kalkulator Proyeksi (Selalu dapat diakses)
    Route::get('/education/glossary', [MemberEducationController::class, 'glossary'])->name('education.glossary');
    Route::get('/education/help', [MemberEducationController::class, 'help'])->name('education.help');
    Route::get('/education/help/{slug}', [MemberEducationController::class, 'article'])->name('education.article');
    Route::get('/education/faq', [MemberEducationController::class, 'faq'])->name('education.faq');
    Route::get('/education/calculator', [MemberEducationController::class, 'calculator'])->name('education.calculator');
});
