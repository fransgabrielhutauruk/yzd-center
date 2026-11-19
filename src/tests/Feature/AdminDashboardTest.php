<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\Invitation;
use App\Models\KlaimKomisi;
use App\Models\KlaimReferral;
use App\Models\LaporanProfitBulanan;
use App\Models\RelasiReferral;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email'    => 'admin@test.com',
            'username' => 'admin_test',
        ]);
    }

    public function test_admin_can_view_dashboard_with_clean_empty_queues(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('pending_actions.kyc', 0)
            ->where('pending_actions.copytrade', 0)
            ->where('pending_actions.referral_claims', 0)
            ->where('pending_actions.commission_claims', 0)
            ->where('pending_actions.total', 0)
            ->where('performance_fee_monitoring.unpaid_reports_count', 0)
            ->where('performance_fee_monitoring.unpaid_investors_count', 0)
            ->where('performance_fee_monitoring.missing_accounts_count', 0)
            ->where('performance_fee_monitoring.missing_investors_count', 0)
            ->where('stats.investor_count', 0)
            ->where('stats.affiliator_count', 0)
            ->where('stats.active_copytrade_count', 0)
            ->where('stats.active_invites', 0)
        );
    }

    public function test_admin_dashboard_accurately_computes_pending_queues_and_ecosystem_stats(): void
    {
        // 1. Pending KYC User (1 member status pending_review)
        $pendingInvestor = User::factory()->create([
            'role'      => 'member',
            'status'    => 'pending_review',
            'is_active' => false,
        ]);

        // 2. Active Investor (1 member status active)
        $activeInvestor = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        // 3. Active Affiliator (1 member status active dengan referral code)
        $activeAffiliator = User::factory()->create([
            'role'          => 'member',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'AFF123',
        ]);

        // Hubungkan investor sebagai downline aktif dari affiliator
        RelasiReferral::create([
            'affiliator_id' => $activeAffiliator->id,
            'downline_id'   => $activeInvestor->id,
            'status'        => 'active',
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
        ]);

        // 4. Pending Copytrade (1 akun pending)
        AkunCopytrade::create([
            'user_id'            => $activeInvestor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-PENDING-01',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $activeInvestor->name,
            'status'             => 'pending',
        ]);

        // 5. Active Copytrade (1 akun active)
        AkunCopytrade::create([
            'user_id'            => $activeInvestor->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => 'MT4-ACTIVE-02',
            'modal_riil'         => 10000,
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $activeInvestor->name,
            'status'             => 'active',
        ]);

        // 6. Pending Referral Claim (1 klaim pending)
        KlaimReferral::create([
            'affiliator_id' => $activeAffiliator->id,
            'downline_id'   => $activeInvestor->id,
            'alasan'        => 'Investor kenalan langsung offline.',
            'status'        => 'pending',
        ]);

        // 7. Pending Commission Claim (1 tiket klaim komisi pending)
        KlaimKomisi::create([
            'kode_klaim'         => KlaimKomisi::generateKodeKlaim(),
            'affiliator_id'      => $activeAffiliator->id,
            'total_downline'     => 1,
            'total_akun'         => 1,
            'total_modal_riil'   => 10000,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $activeAffiliator->name,
            'status'             => 'pending',
        ]);

        // 8. Invitations (1 aktif, 1 expired/inaktif)
        Invitation::create([
            'token'          => 'inv_active_1234567890',
            'label'          => 'Undangan Member Aktif',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDays(7),
        ]);

        Invitation::create([
            'token'          => 'inv_expired_1234567890',
            'label'          => 'Undangan Member Inaktif',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => false,
            'expired_at'     => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('pending_actions.kyc', 1)
            ->where('pending_actions.copytrade', 1)
            ->where('pending_actions.referral_claims', 1)
            ->where('pending_actions.commission_claims', 1)
            ->where('pending_actions.total', 4)
            ->where('stats.investor_count', 1) // $activeInvestor yang punya akunCopytrade
            ->where('stats.active_investor_count', 1)
            ->where('stats.affiliator_count', 1)
            ->where('stats.active_affiliator_count', 1)
            ->where('stats.active_copytrade_count', 1)
            ->where('stats.active_invites', 1)
        );
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $member = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($member)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_dashboard_computes_performance_fee_monitoring_unpaid_and_missing_reports(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-05 10:00:00'));

        try {
            $investorA = User::factory()->create(['role' => 'member', 'status' => 'active', 'is_active' => true]);
            $investorB = User::factory()->create(['role' => 'member', 'status' => 'active', 'is_active' => true]);

            // Akun A1: mulai 2026-04-10 (P1: 10 Apr - 30 Apr terbuka sejak 1 Mei), sudah ada laporan P1 belum_dibayar & P2 menunggu_verifikasi
            $akunA1 = AkunCopytrade::create([
                'user_id'            => $investorA->id,
                'tier'               => 'tier_4',
                'broker'             => 'HFM',
                'tipe_akun'          => 'standard',
                'nomor_akun_trading' => 'MF-MON-001',
                'modal_riil'         => 1000,
                'tanggal_mulai'      => '2026-04-10',
                'nama_bank'          => 'BCA',
                'nomor_rekening'     => '1111111111',
                'atas_nama_rekening' => $investorA->name,
                'status'             => 'active',
            ]);

            LaporanProfitBulanan::create([
                'nomor'               => 'PFE-202605-0001',
                'akun_copytrade_id'   => $akunA1->id,
                'user_id'             => $investorA->id,
                'periode_ke'          => 1,
                'periode_awal'        => '2026-04-10',
                'periode_akhir'       => '2026-04-30',
                'jatuh_tempo'         => '2026-05-01',
                'profit_usd'          => 200.00,
                'bukti_myfxbook_path' => 'performance_fee/myfxbook/a1.png',
                'wd_usd'              => 100.00,
                'wd_idr'              => 1600000,
                'bukti_wd_path'       => 'performance_fee/wd/a1.png',
                'kurs_riil'           => 16000.00,
                'fee_persen'          => 30.00,
                'fee_usd'             => 60.00,
                'fee_idr'             => 960000,
                'status'              => LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
            ]);

            // P2 sudah lapor & upload bukti (menunggu verifikasi admin, tidak masuk unpaid)
            LaporanProfitBulanan::create([
                'nomor'               => 'PFE-202606-0002',
                'akun_copytrade_id'   => $akunA1->id,
                'user_id'             => $investorA->id,
                'periode_ke'          => 2,
                'periode_awal'        => '2026-05-01',
                'periode_akhir'       => '2026-05-31',
                'jatuh_tempo'         => '2026-06-01',
                'profit_usd'          => 300.00,
                'bukti_myfxbook_path' => 'performance_fee/myfxbook/a2.png',
                'wd_usd'              => 150.00,
                'wd_idr'              => 2400000,
                'bukti_wd_path'       => 'performance_fee/wd/a2.png',
                'kurs_riil'           => 16000.00,
                'fee_persen'          => 30.00,
                'fee_usd'             => 90.00,
                'fee_idr'             => 1440000,
                'status'              => LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI,
            ]);

            // Akun A2 milik investorA: mulai 2026-04-10, punya 1 laporan ditolak + belum lapor P2
            $akunA2 = AkunCopytrade::create([
                'user_id'            => $investorA->id,
                'tier'               => 'tier_4',
                'broker'             => 'XM',
                'tipe_akun'          => 'standard',
                'nomor_akun_trading' => 'MF-MON-002',
                'modal_riil'         => 1500,
                'tanggal_mulai'      => '2026-04-10',
                'nama_bank'          => 'BCA',
                'nomor_rekening'     => '1111111111',
                'atas_nama_rekening' => $investorA->name,
                'status'             => 'active',
            ]);

            LaporanProfitBulanan::create([
                'nomor'               => 'PFE-202605-0003',
                'akun_copytrade_id'   => $akunA2->id,
                'user_id'             => $investorA->id,
                'periode_ke'          => 1,
                'periode_awal'        => '2026-04-10',
                'periode_akhir'       => '2026-04-30',
                'jatuh_tempo'         => '2026-05-01',
                'profit_usd'          => 100.00,
                'bukti_myfxbook_path' => 'performance_fee/myfxbook/a2_p1.png',
                'wd_usd'              => 50.00,
                'wd_idr'              => 800000,
                'bukti_wd_path'       => 'performance_fee/wd/a2_p1.png',
                'kurs_riil'           => 16000.00,
                'fee_persen'          => 30.00,
                'fee_usd'             => 30.00,
                'fee_idr'             => 480000,
                'status'              => LaporanProfitBulanan::STATUS_DITOLAK,
            ]);

            // Akun B1 milik investorB: mulai 2026-05-05, belum lapor P1 sama sekali (P1: 5 Mei - 31 Mei terbuka 1 Juni)
            AkunCopytrade::create([
                'user_id'            => $investorB->id,
                'tier'               => 'tier_4',
                'broker'             => 'IC Markets',
                'tipe_akun'          => 'standard',
                'nomor_akun_trading' => 'MF-MON-003',
                'modal_riil'         => 2000,
                'tanggal_mulai'      => '2026-05-05',
                'nama_bank'          => 'Mandiri',
                'nomor_rekening'     => '2222222222',
                'atas_nama_rekening' => $investorB->name,
                'status'             => 'active',
            ]);

            $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('pending_actions.performance_fee', 1)
                ->where('pending_actions.total', 1)
                ->where('performance_fee_monitoring.unpaid_reports_count', 2)
                ->where('performance_fee_monitoring.unpaid_only_count', 1)
                ->where('performance_fee_monitoring.rejected_reports_count', 1)
                ->where('performance_fee_monitoring.unpaid_investors_count', 1) // hanya investorA
                ->where('performance_fee_monitoring.unpaid_total_usd', 90)
                ->where('performance_fee_monitoring.unpaid_total_idr', 1440000)
                ->where('performance_fee_monitoring.missing_accounts_count', 2) // akunA2 (P2) & akunB1 (P1)
                ->where('performance_fee_monitoring.missing_investors_count', 2) // investorA & investorB
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
