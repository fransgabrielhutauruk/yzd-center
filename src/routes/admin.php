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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
| Seluruh rute portal administrator dengan middleware autentikasi dan peran.
| Dimuat secara modular via routes/web.php.
*/

Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Manajemen Pengguna (Member)
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle_status');

    // Manajemen Tautan Undangan
    Route::get('/invitations/export', [InvitationController::class, 'export'])->name('invitations.export');
    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/batch-delete', [InvitationController::class, 'batchDestroy'])->name('invitations.batch_destroy');
    Route::delete('/invitations/{id}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

    // Verifikasi Pendaftaran (KYC & Akun Investor/Affiliator)
    Route::get('/registrations/export', [RegistrationController::class, 'export'])->name('registrations.export');
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/registrations/documents/{id}/verify', [RegistrationController::class, 'verify'])->name('registrations.verify');

    // Verifikasi Copytrade
    Route::get('/copytrade/export', [AdminCopytradeController::class, 'export'])->name('copytrade.export');
    Route::get('/copytrade', [AdminCopytradeController::class, 'index'])->name('copytrade.index');
    Route::post('/copytrade/{id}/verify', [AdminCopytradeController::class, 'verify'])->name('copytrade.verify');
    Route::get('/copytrade/{id}/detail', [AdminCopytradeController::class, 'detail'])->whereNumber('id')->name('copytrade.detail');

    // Ganti Password Admin
    Route::get('/settings/password', [AdminPasswordController::class, 'index'])->name('settings.password');

    // Verifikasi Permohonan Klaim Referral Susulan
    Route::get('/referral-claims/export', [AdminReferralClaimController::class, 'export'])->name('referral_claims.export');
    Route::get('/referral-claims', [AdminReferralClaimController::class, 'index'])->name('referral_claims.index');
    Route::post('/referral-claims/{id}/verify', [AdminReferralClaimController::class, 'verify'])->name('referral_claims.verify');

    // Rekapitulasi Jaringan Mitra Affiliator
    Route::get('/affiliates/export', [AdminAffiliateController::class, 'export'])->name('affiliates.export');
    Route::get('/affiliates', [AdminAffiliateController::class, 'index'])->name('affiliates.index');

    // Pencairan Komisi Afiliasi (Modul 05)
    Route::get('/commissions/export', [AdminCommissionClaimController::class, 'export'])->name('commissions.export');
    Route::get('/commissions', [AdminCommissionClaimController::class, 'index'])->name('commissions.index');
    Route::post('/commissions/{id}/process', [AdminCommissionClaimController::class, 'process'])->name('commissions.process');

    // Materi Promosi (Profit Master)
    Route::get('/profit-master', [AdminProfitMasterController::class, 'index'])->name('profit_master.index');
    Route::get('/profit-master/feed', [AdminProfitMasterController::class, 'feed'])->name('profit_master.feed');
    Route::post('/profit-master', [AdminProfitMasterController::class, 'store'])->name('profit_master.store');
    Route::match(['put', 'patch', 'post'], '/profit-master/{post}', [AdminProfitMasterController::class, 'update'])->name('profit_master.update');
    Route::delete('/profit-master/{post}', [AdminProfitMasterController::class, 'destroy'])->name('profit_master.destroy');

    // Tiket Bantuan & Pelaporan Kendala
    Route::get('/tickets/export', [AdminTiketBantuanController::class, 'export'])->name('tickets.export');
    Route::get('/tickets', [AdminTiketBantuanController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTiketBantuanController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/replies', [AdminTiketBantuanController::class, 'reply'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/status', [AdminTiketBantuanController::class, 'updateStatus'])->name('tickets.status');

    // Pengaturan Dokumen Legalitas (Terms & Privacy)
    Route::get('/settings/legal', [LegalContentController::class, 'index'])->name('settings.legal');
    Route::post('/settings/legal', [LegalContentController::class, 'update'])->name('settings.legal.update');

    // Pengaturan Master Fee & Kelola Laporan
    Route::get('/master-fee/export', [AdminMasterFeeController::class, 'export'])->name('master_fee.export');
    Route::get('/master-fee', [AdminMasterFeeController::class, 'index'])->name('master_fee.index');
    Route::post('/master-fee/{id}/verify', [AdminMasterFeeController::class, 'verify'])->name('master_fee.verify');
    Route::post('/master-fee/{id}/reject', [AdminMasterFeeController::class, 'reject'])->name('master_fee.reject');
    Route::post('/master-fee/{id}/flag-correction', [AdminMasterFeeController::class, 'flagCorrection'])->name('master_fee.flag_correction');
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
