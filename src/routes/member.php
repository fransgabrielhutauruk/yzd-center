<?php

use App\Http\Controllers\Member\AccountController;
use App\Http\Controllers\Member\AffiliateCalculatorController;
use App\Http\Controllers\Member\AffiliateNetworkController;
use App\Http\Controllers\Member\CommissionClaimController as MemberCommissionClaimController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\EducationController as MemberEducationController;
use App\Http\Controllers\Member\InvestmentPortfolioController;
use App\Http\Controllers\Member\MasterFeeController as MemberMasterFeeController;
use App\Http\Controllers\Member\PasswordController as MemberPasswordController;
use App\Http\Controllers\Member\ProfitMasterController as MemberProfitMasterController;
use App\Http\Controllers\Member\TiketBantuanController as MemberTiketBantuanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member Portal Routes
|--------------------------------------------------------------------------
| Seluruh rute portal investor & affiliator (member) dengan middleware autentikasi dan peran.
| Dimuat secara modular via routes/web.php.
*/

Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    // Fitur yang mewajibkan akun sudah berstatus active
    Route::middleware('ensure.active')->group(function () {
        Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

        // Jaringan Kemitraan & Klaim Susulan
        Route::get('/affiliates', [AffiliateNetworkController::class, 'index'])->name('affiliates.index');
        Route::get('/affiliates-calculator', [AffiliateCalculatorController::class, 'index'])->name('affiliates.calculator');
        Route::post('/affiliates/claims', [AffiliateNetworkController::class, 'storeClaim'])->name('affiliates.claims.store');
        Route::get('/affiliates/eligible-downlines', [AffiliateNetworkController::class, 'eligibleDownlines'])->name('affiliates.eligible_downlines');
        Route::get('/affiliates/check-downline', [AffiliateNetworkController::class, 'checkDownline'])->name('affiliates.check_downline');

        // Klaim Komisi Afiliasi
        Route::get('/commissions', [MemberCommissionClaimController::class, 'index'])->name('commissions.index');
        Route::post('/commissions', [MemberCommissionClaimController::class, 'store'])->name('commissions.store');
        Route::get('/commissions/eligible-downlines', [MemberCommissionClaimController::class, 'eligibleDownlines'])->name('commissions.eligible_downlines');

        // Laporan Profit & Master Fee
        Route::get('/master-fee', [MemberMasterFeeController::class, 'index'])->name('master_fee.index');
        Route::post('/master-fee', [MemberMasterFeeController::class, 'store'])->name('master_fee.store');
        Route::put('/master-fee/{id}', [MemberMasterFeeController::class, 'update'])->name('master_fee.update');
        Route::delete('/master-fee/{id}', [MemberMasterFeeController::class, 'destroy'])->name('master_fee.destroy');
        Route::get('/master-fee/{id}', [MemberMasterFeeController::class, 'show'])->name('master_fee.show');
        Route::post('/master-fee/{id}/proof', [MemberMasterFeeController::class, 'uploadProof'])->name('master_fee.proof');
        Route::post('/first-period-banner/dismiss', function (Request $request) {
            session(['first_period_notice_dismissed' => true]);

            if ($request->hasHeader('X-Inertia')) {
                return back();
            }

            return response()->json(['status' => 'dismissed']);
        })->name('first_period_banner.dismiss');
    });

    // Informasi Akun & Unggah Ulang Berkas Verifikasi (Selalu dapat diakses oleh member)
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::post('/account/reupload/{type}', [AccountController::class, 'reupload'])->name('account.reupload');

    // Ganti Password Member
    Route::get('/account/password', [MemberPasswordController::class, 'index'])->name('account.password');

    // Portofolio Investasi (Terpadu: Akun Trading Tier 1/2/3, Alur Onboarding & Kinerja)
    Route::get('/investments', [InvestmentPortfolioController::class, 'index'])->name('investments.index');
    Route::post('/investments', [InvestmentPortfolioController::class, 'storeAccount'])->name('investments.store');
    Route::post('/investments/onboarding', [InvestmentPortfolioController::class, 'updateOnboarding'])->name('investments.onboarding.update');
    Route::post('/investments/onboarding/reset', [InvestmentPortfolioController::class, 'resetOnboarding'])->name('investments.onboarding.reset');
    Route::put('/investments/{id}/myfxbook', [InvestmentPortfolioController::class, 'updateMyfxbook'])->name('investments.myfxbook.update');
    Route::match(['get', 'post'], '/investments/template-perjanjian/{tier}', [InvestmentPortfolioController::class, 'downloadTemplate'])->name('investments.template');

    // Backward compatibility redirects & route aliases
    Route::get('/copytrade', fn () => redirect()->route('member.investments.index'))->name('copytrade.index');
    Route::post('/copytrade', [InvestmentPortfolioController::class, 'storeAccount'])->name('copytrade.store');
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
