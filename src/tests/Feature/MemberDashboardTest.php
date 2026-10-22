<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\KlaimKomisi;
use App\Models\PengaturanMaster;
use App\Models\RelasiReferral;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        PengaturanMaster::create([
            'kurs_usd_idr'            => 16796.00,
            'kurs_usd_idr_updated_at' => now(),
        ]);

        $this->member = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    private function makeAccount(string $status = 'active', float $modal = 1000.00): AkunCopytrade
    {
        return AkunCopytrade::create([
            'user_id'            => $this->member->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-' . random_int(1000, 9999),
            'modal_riil'         => $modal,
            'tanggal_mulai'      => now()->startOfMonth()->subMonths(2)->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Member Test',
            'status'             => $status,
        ]);
    }

    private function makeRecord(AkunCopytrade $akun, string $date, float $balance): void
    {
        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->member->id,
            'tanggal_pencatatan'           => $date,
            'balance'                      => $balance,
            'keuntungan_nominal'           => 0,
            'keuntungan_persen'            => 0,
            'keuntungan_kumulatif'         => 0,
            'pertumbuhan_kumulatif_persen' => 0,
        ]);
    }

    public function test_stage_no_account_when_member_has_no_accounts(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'no_account')
                ->where('dashboard.trend', [])
                ->has('dashboard.accounts', 0)
            );
    }

    public function test_newly_registered_unverified_member_can_access_dashboard_with_no_account_stage(): void
    {
        $pendingMember = User::factory()->pending()->create();

        $this->actingAs($pendingMember)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'no_account')
            );
    }

    public function test_stage_pending_account_when_only_pending_accounts_exist(): void
    {
        $this->makeAccount('pending');

        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'pending_account')
                ->where('dashboard.portfolio.pending_accounts_count', 1)
                ->has('dashboard.accounts', 1)
            );
    }

    public function test_rejected_accounts_are_ignored(): void
    {
        $this->makeAccount('rejected');

        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'no_account')
            );
    }

    public function test_running_stage_uses_real_balance_records_and_computes_mom(): void
    {
        $akun = $this->makeAccount('active', 1000.00);
        $this->makeRecord($akun, now()->startOfMonth()->subMonth()->toDateString(), 1100.00);
        $this->makeRecord($akun, now()->startOfMonth()->toDateString(), 1210.00);

        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
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

        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'running')
                ->where('dashboard.balance_recorded_this_month', false)
                ->where('dashboard.unrecorded_accounts_count', 1)
                ->where('dashboard.mom_change', null)
            );
    }

    public function test_running_stage_computes_mom_when_account_has_historical_records(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->member->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => 'MT4-HISTORICAL',
            'modal_riil'         => 10000.00,
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Member Historical',
            'status'             => 'active',
        ]);

        $this->makeRecord($akun, now()->startOfMonth()->subMonth()->toDateString(), 9000.00);
        $this->makeRecord($akun, now()->startOfMonth()->toDateString(), 20000.00);

        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'running')
                ->where('dashboard.portfolio.total_current_balance', 20000)
                ->where('dashboard.mom_change', 11000)
                ->where('dashboard.mom_change_pct', 122.2)
                ->where('dashboard.balance_recorded_this_month', true)
                ->has('dashboard.trend', 2)
            );
    }

    public function test_member_dashboard_provides_unified_investment_and_affiliate_stats(): void
    {
        $affiliator = User::factory()->create([
            'role'          => 'member',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'TESTAF',
        ]);

        $activeDownline = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $inactiveDownline = User::factory()->create([
            'role'      => 'member',
            'status'    => 'pending_review',
            'is_active' => false,
        ]);

        RelasiReferral::create([
            'affiliator_id' => $affiliator->id,
            'downline_id'   => $activeDownline->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
            'status'        => 'active',
        ]);

        RelasiReferral::create([
            'affiliator_id' => $affiliator->id,
            'downline_id'   => $inactiveDownline->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
            'status'        => 'active',
        ]);

        KlaimKomisi::create([
            'kode_klaim'                 => 'CLM-202610-0001',
            'affiliator_id'              => $affiliator->id,
            'total_downline'             => 1,
            'total_akun'                 => 1,
            'total_modal_riil'           => 10000,
            'total_nominal_transfer'     => 120,
            'kurs_konversi_idr'          => 16250,
            'total_nominal_transfer_idr' => 1950000,
            'nama_bank'                  => 'BCA',
            'nomor_rekening'             => '1234567890',
            'atas_nama_rekening'         => $affiliator->name,
            'status'                     => 'approved_transferred',
        ]);

        $this->actingAs($affiliator)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('user.role', 'member')
                ->where('user.referral_code', 'TESTAF')
                ->has('dashboard')
                ->where('affiliate.network.total_downlines', 1)
                ->where('affiliate.commission.total_dicairkan', 120)
                ->where('affiliate.commission.total_dicairkan_idr', 1950000)
                ->where('affiliate.commission.klaim_disetujui', 1)
                ->where('affiliate.commission.downline_siap_diklaim', 0)
            );
    }
}
