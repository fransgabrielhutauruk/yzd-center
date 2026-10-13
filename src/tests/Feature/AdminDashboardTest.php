<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\Invitation;
use App\Models\KlaimKomisi;
use App\Models\KlaimReferral;
use App\Models\User;
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
            ->where('stats.investor_count', 0)
            ->where('stats.affiliator_count', 0)
            ->where('stats.active_copytrade_count', 0)
            ->where('stats.active_invites', 0)
        );
    }

    public function test_admin_dashboard_accurately_computes_pending_queues_and_ecosystem_stats(): void
    {
        // 1. Pending KYC User (1 investor status pending_review)
        $pendingInvestor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'pending_review',
            'is_active' => false,
        ]);

        // 2. Active Investor (1 investor status active)
        $activeInvestor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        // 3. Active Affiliator (1 affiliator status active)
        $activeAffiliator = User::factory()->affiliator()->create([
            'status'    => 'active',
            'is_active' => true,
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
        $claim = KlaimKomisi::create([
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
            'label'          => 'Undangan Investor Aktif',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDays(7),
        ]);

        Invitation::create([
            'token'          => 'inv_expired_1234567890',
            'label'          => 'Undangan Investor Inaktif',
            'target_role'    => 'investor',
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
            ->where('stats.investor_count', 2)
            ->where('stats.active_investor_count', 1)
            ->where('stats.affiliator_count', 1)
            ->where('stats.active_affiliator_count', 1)
            ->where('stats.active_copytrade_count', 1)
            ->where('stats.active_invites', 1)
        );
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $investor = User::factory()->create(['role' => 'investor', 'status' => 'active']);

        $response = $this->actingAs($investor)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }
}
