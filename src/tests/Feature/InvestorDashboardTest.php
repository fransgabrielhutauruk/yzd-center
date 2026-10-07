<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        PengaturanMaster::create([
            'link_referral_broker'    => 'https://broker.example.com',
            'link_master_akun'        => 'https://mql5.example.com',
            'tier_1_min'              => 100,
            'tier_2_min'              => 5000,
            'tier_3_min'              => 300000,
            'kurs_usd_idr'            => 16796.00,
            'kurs_usd_idr_updated_at' => now(),
        ]);

        $this->investor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    private function makeAccount(string $status = 'active', float $modal = 1000.00): AkunCopytrade
    {
        return AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-' . random_int(1000, 9999),
            'modal_riil'         => $modal,
            'tanggal_mulai'      => now()->startOfMonth()->subMonths(2)->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => $status,
            'copy_started_at'    => now()->startOfMonth()->subMonths(2),
        ]);
    }

    private function makeRecord(AkunCopytrade $akun, string $date, float $balance): void
    {
        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => $date,
            'balance'                      => $balance,
            'keuntungan_nominal'           => 0,
            'keuntungan_persen'            => 0,
            'keuntungan_kumulatif'         => 0,
            'pertumbuhan_kumulatif_persen' => 0,
        ]);
    }

    public function test_stage_no_account_when_investor_has_no_accounts(): void
    {
        $this->actingAs($this->investor)
            ->get(route('investor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Investor/InvestorDashboard')
                ->where('dashboard.stage', 'no_account')
                ->where('dashboard.trend', [])
                ->has('dashboard.accounts', 0)
            );
    }

    public function test_stage_pending_account_when_only_pending_accounts_exist(): void
    {
        $this->makeAccount('pending');

        $this->actingAs($this->investor)
            ->get(route('investor.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('dashboard.stage', 'pending_account')
                ->where('dashboard.portfolio.pending_accounts_count', 1)
                ->has('dashboard.accounts', 1)
            );
    }

    public function test_rejected_accounts_are_ignored(): void
    {
        $this->makeAccount('rejected');

        $this->actingAs($this->investor)
            ->get(route('investor.dashboard'))
            ->assertInertia(fn ($page) => $page->where('dashboard.stage', 'no_account'));
    }

    public function test_running_stage_uses_real_balance_records_and_computes_mom(): void
    {
        $akun = $this->makeAccount('active', 1000.00);
        $this->makeRecord($akun, now()->startOfMonth()->subMonth()->toDateString(), 1100.00);
        $this->makeRecord($akun, now()->startOfMonth()->toDateString(), 1210.00);

        $this->actingAs($this->investor)
            ->get(route('investor.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('dashboard.stage', 'running')
                ->where('dashboard.portfolio.total_current_balance', 1210)
                ->where('dashboard.portfolio.total_all_time_profit', 210)
                ->where('dashboard.mom_change', 110)
                ->where('dashboard.balance_recorded_this_month', true)
                ->where('dashboard.unrecorded_accounts_count', 0)
                ->has('dashboard.trend', 2)
            );
    }

    public function test_running_stage_flags_unrecorded_account_this_month(): void
    {
        $akun = $this->makeAccount('active', 1000.00);
        $this->makeRecord($akun, now()->startOfMonth()->subMonth()->toDateString(), 1100.00);

        $this->actingAs($this->investor)
            ->get(route('investor.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('dashboard.stage', 'running')
                ->where('dashboard.balance_recorded_this_month', false)
                ->where('dashboard.unrecorded_accounts_count', 1)
                ->where('dashboard.mom_change', null)
            );
    }

    public function test_running_stage_computes_mom_when_account_has_historical_records_prior_to_copy_started_at(): void
    {
        // Simulasi akun yang diverifikasi hari ini, namun memiliki catatan saldo bulan lalu dan bulan ini
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => 'MT4-HISTORICAL',
            'modal_riil'         => 10000.00,
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Historical',
            'status'             => 'active',
            'copy_started_at'    => now(), // Baru diverifikasi hari ini
        ]);

        $this->makeRecord($akun, now()->startOfMonth()->subMonth()->toDateString(), 9000.00);
        $this->makeRecord($akun, now()->startOfMonth()->toDateString(), 20000.00);

        $this->actingAs($this->investor)
            ->get(route('investor.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('dashboard.stage', 'running')
                ->where('dashboard.portfolio.total_current_balance', 20000)
                ->where('dashboard.mom_change', 11000)
                ->where('dashboard.mom_change_pct', 122.2)
                ->where('dashboard.balance_recorded_this_month', true)
                ->has('dashboard.trend', 2)
            );
    }

    public function test_affiliator_renders_decoupled_affiliator_dashboard_with_affiliate_stats(): void
    {
        $affiliator = User::factory()->create([
            'role'          => 'affiliator',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'TESTAF',
        ]);

        $this->actingAs($affiliator)
            ->get(route('investor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Investor/AffiliatorDashboard')
                ->where('user.role', 'affiliator')
                ->where('user.referral_code', 'TESTAF')
                ->has('dashboard')
                ->has('affiliate.network')
                ->has('affiliate.commission')
            );
    }
}
