<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\KlaimKomisi;
use App\Models\LaporanProfitBulanan;
use App\Models\PengaturanMaster;
use App\Models\RelasiReferral;
use App\Models\User;
use Carbon\Carbon;
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
            'myfxbook_url'       => 'https://www.myfxbook.com/members/demo/dash',
        ]);
    }

    private function makeReport(AkunCopytrade $akun, int $periodeKe, string $awal, string $akhir, float $profit): LaporanProfitBulanan
    {
        return LaporanProfitBulanan::create([
            'nomor'               => 'MFE-' . uniqid(),
            'akun_copytrade_id'   => $akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => $periodeKe,
            'periode_awal'        => $awal,
            'periode_akhir'       => $akhir,
            'jatuh_tempo'         => Carbon::parse($akhir)->addDay()->toDateString(),
            'profit_usd'          => $profit,
            'fee_persen'          => 30.00,
            'fee_usd'             => round($profit * 0.30, 2),
            'wd_usd'              => round($profit * 0.40, 2),
            'wd_idr'              => (int) round($profit * 0.40 * 16000),
            'kurs_riil'           => 16000.00,
            'fee_idr'             => (int) round($profit * 0.30 * 16000),
            'bukti_myfxbook_path' => 'reports/myfx.jpg',
            'bukti_wd_path'       => 'reports/wd.jpg',
            'status'              => 'lunas',
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
                ->has('dashboard.trend', 6)
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
                ->has('dashboard.accounts', 0)
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

    public function test_running_stage_uses_real_profit_reports(): void
    {
        $akun = $this->makeAccount('active', 1000.00);
        $this->makeReport($akun, 1, '2026-01-01', '2026-01-31', 100.00);
        $this->makeReport($akun, 2, '2026-02-01', '2026-02-28', 110.00);

        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'running')
                ->where('dashboard.portfolio.total_current_balance', fn ($v) => (float) $v === 1210.0)
                ->where('dashboard.portfolio.total_all_time_profit', fn ($v) => (float) $v === 210.0)
                ->has('dashboard.trend', 6)
            );
    }

    public function test_running_stage_flags_unrecorded_account_this_month(): void
    {
        $this->makeAccount('active', 1000.00);

        $this->actingAs($this->member)
            ->get(route('member.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Member/Dashboard')
                ->where('dashboard.stage', 'running')
                ->where('dashboard.balance_recorded_this_month', false)
                ->where('dashboard.unrecorded_accounts_count', 1)
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

    public function test_first_period_banner_dismiss_returns_back_for_inertia_requests(): void
    {
        $response = $this->actingAs($this->member)
            ->from(route('member.dashboard'))
            ->post(route('member.first_period_banner.dismiss'), [], [
                'X-Inertia' => 'true',
            ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertTrue(session('first_period_notice_dismissed'));
    }

    public function test_first_period_banner_dismiss_returns_json_for_api_requests(): void
    {
        $response = $this->actingAs($this->member)
            ->postJson(route('member.first_period_banner.dismiss'));

        $response->assertOk();
        $response->assertExactJson(['status' => 'dismissed']);
        $this->assertTrue(session('first_period_notice_dismissed'));
    }
}
