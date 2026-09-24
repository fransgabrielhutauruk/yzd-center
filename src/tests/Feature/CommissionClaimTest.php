<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\KlaimKomisi;
use App\Models\KlaimKomisiItem;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Notifications\CommissionClaimProcessedNotification;
use App\Notifications\CommissionClaimSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommissionClaimTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $affiliator;

    private User $downline;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create([
            'role'   => 'administrator',
            'status' => 'active',
        ]);

        $this->affiliator = User::factory()->create([
            'role'   => 'affiliator',
            'status' => 'active',
        ]);

        $this->downline = User::factory()->create([
            'role'   => 'investor',
            'status' => 'active',
        ]);

        // Hubungkan relasi referral
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->downline->id,
            'sumber'        => 'link_otomatis',
            'status'        => 'active',
            'joined_at'     => now()->subDays(150),
        ]);
    }

    public function test_affiliator_can_view_commission_dashboard_and_claims_list(): void
    {
        $response = $this->actingAs($this->affiliator)->get('/investor/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Commissions/Index')
            ->has('stats')
            ->has('claims')
            ->has('filters')
            ->has('eligibleDownlines')
            ->has('defaultBank')
        );
    }

    public function test_downline_with_less_than_90_days_copytrade_is_not_eligible(): void
    {
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '99881122',
            'modal_riil'         => 50_000_000,
            'tanggal_mulai'      => now()->subDays(45),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(45),
        ]);

        $response = $this->actingAs($this->affiliator)->get('/investor/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Commissions/Index')
            ->where('eligibleDownlines.0.is_eligible', false)
            ->where('eligibleDownlines.0.active_days', 45)
        );
    }

    public function test_downline_with_at_least_90_days_copytrade_is_eligible(): void
    {
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '88776655',
            'modal_riil'         => 150_000_000,
            'tanggal_mulai'      => now()->subDays(95),
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '9876543210',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(95),
        ]);

        $response = $this->actingAs($this->affiliator)->get('/investor/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Commissions/Index')
            ->where('eligibleDownlines.0.is_eligible', true)
            ->where('eligibleDownlines.0.active_days', 95)
            ->where('eligibleDownlines.0.accounts.0.hari_kehitung', 90)
        );
    }

    public function test_multi_copytrade_calculates_earliest_start_combined_modal_and_account_days(): void
    {
        // Akun 1: mulai 100 hari lalu, modal 60 Juta
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '112233',
            'modal_riil'         => 60_000_000,
            'tanggal_mulai'      => now()->subDays(100),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(100),
        ]);

        // Akun 2: mulai 80 hari lalu (20 hari setelah Akun 1), modal 70 Juta
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '445566',
            'modal_riil'         => 70_000_000,
            'tanggal_mulai'      => now()->subDays(80),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(80),
        ]);

        $response = $this->actingAs($this->affiliator)->get('/investor/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Commissions/Index')
            ->where('eligibleDownlines.0.has_multi_copytrade', true)
            ->where('eligibleDownlines.0.jumlah_akun', 2)
            ->where('eligibleDownlines.0.total_modal_riil', 130_000_000) // 60M + 70M = 130M -> Tier 3
            ->where('eligibleDownlines.0.tier_info.tier_level', 3)
            ->where('eligibleDownlines.0.tier_info.persentase_tier', 30)
            ->where('eligibleDownlines.0.active_days', 100)
            ->where('eligibleDownlines.0.is_eligible', true)
            ->where('eligibleDownlines.0.accounts.0.is_first', true)
            ->where('eligibleDownlines.0.accounts.0.hari_kehitung', 90) // Akun pertama dihitung 90 hari
            ->where('eligibleDownlines.0.accounts.1.is_first', false)
            ->where('eligibleDownlines.0.accounts.1.selisih_hari', 20) // Mulai hari ke-20
            ->where('eligibleDownlines.0.accounts.1.hari_kehitung', 70) // 90 - 20 = 70 hari
        );
    }

    public function test_all_4_tier_percentages_calculation(): void
    {
        // Tier 1: > 10 Milyar -> 50%
        $tier1 = KlaimKomisiItem::determineTier(15_000_000_000);
        $this->assertEquals(1, $tier1['tier_level']);
        $this->assertEquals(50, $tier1['persentase_tier']);

        // Tier 2: 1 Milyar s/d 10 Milyar -> 40%
        $tier2 = KlaimKomisiItem::determineTier(5_000_000_000);
        $this->assertEquals(2, $tier2['tier_level']);
        $this->assertEquals(40, $tier2['persentase_tier']);

        // Tier 3: 100 Juta s/d 1 Milyar -> 30%
        $tier3 = KlaimKomisiItem::determineTier(500_000_000);
        $this->assertEquals(3, $tier3['tier_level']);
        $this->assertEquals(30, $tier3['persentase_tier']);

        // Tier 4: < 100 Juta -> 20%
        $tier4 = KlaimKomisiItem::determineTier(50_000_000);
        $this->assertEquals(4, $tier4['tier_level']);
        $this->assertEquals(20, $tier4['persentase_tier']);
    }

    public function test_affiliator_can_submit_batch_claims_as_single_row_in_klaim_komisi(): void
    {
        Notification::fake();

        // Downline 1 (aktif 100 hari, memiliki 2 akun)
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_2',
            'broker'             => 'HFM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '55443322',
            'modal_riil'         => 200_000_000,
            'tanggal_mulai'      => now()->subDays(100),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(100),
        ]);
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_1',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '99887711',
            'modal_riil'         => 300_000_000,
            'tanggal_mulai'      => now()->subDays(70), // selisih 30 hari -> 60 hari kehitung
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(70),
        ]);

        // Downline 2 (aktif 95 hari, memiliki 1 akun)
        $downline2 = User::factory()->create([
            'role'   => 'investor',
            'status' => 'active',
        ]);
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $downline2->id,
            'sumber'        => 'link_otomatis',
            'status'        => 'active',
            'joined_at'     => now()->subDays(120),
        ]);
        AkunCopytrade::create([
            'user_id'            => $downline2->id,
            'tier'               => 'tier_1',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '99887766',
            'modal_riil'         => 1_500_000_000,
            'tanggal_mulai'      => now()->subDays(95),
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => $downline2->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(95),
        ]);

        $response = $this->actingAs($this->affiliator)->post('/investor/commissions', [
            'downline_ids'       => [$this->downline->id, $downline2->id],
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '7788990011',
            'atas_nama_rekening' => 'Affiliator YZD',
        ]);

        $response->assertRedirect('/investor/commissions');
        $response->assertSessionHas('success');

        // VERIFIKASI TEPAT 1 ROW TIKET KLAIM DI DATABASE
        $this->assertEquals(1, KlaimKomisi::count());

        $claim = KlaimKomisi::first();
        $this->assertNotNull($claim);
        $this->assertEquals(2, $claim->total_downline);
        $this->assertEquals(3, $claim->total_akun); // 2 akun downline 1 + 1 akun downline 2
        $this->assertEquals(2_000_000_000, (float) $claim->total_modal_riil); // 200M + 300M + 1.5M = 2M
        $this->assertEquals('pending', $claim->status);

        // Dan 3 baris akun di tabel klaim_komisi_items
        $this->assertEquals(3, $claim->items()->count());

        $item1 = $claim->items()->where('downline_id', $this->downline->id)->where('is_akun_pertama', true)->first();
        $this->assertNotNull($item1);
        $this->assertEquals(90, $item1->hari_kehitung);
        $this->assertEquals(3, $item1->tier_level); // 200M + 300M = 500M -> Tier 3 (30%)
        $this->assertEquals(30, $item1->persentase_tier);

        $item2 = $claim->items()->where('downline_id', $this->downline->id)->where('is_akun_pertama', false)->first();
        $this->assertNotNull($item2);
        $this->assertEquals(60, $item2->hari_kehitung); // 90 - 30 = 60 hari
        $this->assertEquals(30, $item2->persentase_tier);

        $item3 = $claim->items()->where('downline_id', $downline2->id)->first();
        $this->assertNotNull($item3);
        $this->assertEquals(90, $item3->hari_kehitung);
        $this->assertEquals(2, $item3->tier_level); // 1.5 M -> Tier 2 (40%)
        $this->assertEquals(40, $item3->persentase_tier);

        Notification::assertSentTo($this->admin, CommissionClaimSubmittedNotification::class);
    }

    public function test_affiliator_cannot_submit_duplicate_claim_for_same_downline(): void
    {
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_2',
            'broker'             => 'HFM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '55443322',
            'modal_riil'         => 100_000_000,
            'tanggal_mulai'      => now()->subDays(100),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
            'copy_started_at'    => now()->subDays(100),
        ]);

        $claim = KlaimKomisi::create([
            'kode_klaim'         => KlaimKomisi::generateKodeKlaim(),
            'affiliator_id'      => $this->affiliator->id,
            'total_downline'     => 1,
            'total_akun'         => 1,
            'total_modal_riil'   => 100_000_000,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '7788990011',
            'atas_nama_rekening' => 'Affiliator YZD',
            'status'             => 'pending',
        ]);
        $claim->items()->create([
            'downline_id'        => $this->downline->id,
            'broker'             => 'HFM',
            'nomor_akun_trading' => '55443322',
            'is_akun_pertama'    => true,
            'tanggal_mulai'      => now()->subDays(100)->toDateString(),
            'tanggal_selesai'    => now()->subDays(10)->toDateString(),
            'hari_kehitung'      => 90,
            'modal_riil'         => 100_000_000,
            'tier_level'         => 3,
            'persentase_tier'    => 30,
        ]);

        $response = $this->actingAs($this->affiliator)->post('/investor/commissions', [
            'downline_ids'       => [$this->downline->id],
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '7788990011',
            'atas_nama_rekening' => 'Affiliator YZD',
        ]);

        $response->assertSessionHasErrors('downline_ids');
        $this->assertEquals(1, KlaimKomisi::count());
        $this->assertEquals(1, KlaimKomisiItem::count());
    }

    public function test_admin_can_view_commissions_list(): void
    {
        $claim = KlaimKomisi::create([
            'kode_klaim'         => 'CLM-202609-0001',
            'affiliator_id'      => $this->affiliator->id,
            'total_downline'     => 1,
            'total_akun'         => 1,
            'total_modal_riil'   => 200_000_000,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Affiliator YZD',
            'status'             => 'pending',
        ]);
        $claim->items()->create([
            'downline_id'        => $this->downline->id,
            'broker'             => 'HFM',
            'nomor_akun_trading' => '12345',
            'is_akun_pertama'    => true,
            'tanggal_mulai'      => now()->subDays(90)->toDateString(),
            'tanggal_selesai'    => now()->toDateString(),
            'hari_kehitung'      => 90,
            'modal_riil'         => 200_000_000,
            'tier_level'         => 3,
            'persentase_tier'    => 30,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Commissions/Index')
            ->has('claims')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_approve_and_transfer_commission(): void
    {
        Notification::fake();

        $claim = KlaimKomisi::create([
            'kode_klaim'           => 'CLM-202609-0002',
            'affiliator_id'        => $this->affiliator->id,
            'total_downline'       => 1,
            'total_akun'           => 1,
            'total_modal_riil'     => 10_000,
            'total_deposit_amount' => 10_000,
            'nama_bank'            => 'BCA',
            'nomor_rekening'       => '1234567890',
            'atas_nama_rekening'   => 'Affiliator YZD',
            'status'               => 'pending',
        ]);
        $item = $claim->items()->create([
            'downline_id'         => $this->downline->id,
            'broker'              => 'HFM',
            'nomor_akun_trading'  => '12345',
            'is_akun_pertama'     => true,
            'tanggal_mulai'       => now()->subDays(90)->toDateString(),
            'tanggal_selesai'     => now()->toDateString(),
            'hari_kehitung'       => 90,
            'modal_riil'          => 10_000,
            'deposit_amount'      => 10_000,
            'tier_level'          => 4,
            'persentase_tier'     => 20,
            'master_share_pct'    => 30.00,
            'affiliate_share_pct' => 20.00,
        ]);

        $receiptFile = UploadedFile::fake()->create('bukti_transfer.jpg', 300, 'image/jpeg');

        // Modal: $10,000 | ROI Master: 20% | Master Share: 30% | Affiliate Share: 20%
        // Gross Profit: $10,000 * 20% = $2,000
        // Hak Kotor Master: $2,000 * 30% = $600
        // Komisi Afiliator: $600 * 20% = $120
        // Hak Bersih Master: $600 - $120 = $480
        // Profit Bersih Investor: $2,000 - $600 = $1,400
        $response = $this->actingAs($this->admin)->post("/admin/commissions/{$claim->id}/process", [
            'status' => 'approved_transferred',
            'items'  => [
                [
                    'id'                  => $item->id,
                    'roi_percentage'      => 20.0,
                    'master_share_pct'    => 30.0,
                    'affiliate_share_pct' => 20.0,
                ],
            ],
            'bukti_transfer' => $receiptFile,
            'catatan_admin'  => 'Transfer komisi berhasil diproses dan dikirim ke rekening afiliator.',
        ]);

        $response->assertRedirect('/admin/commissions');
        $response->assertSessionHas('success');

        $claim->refresh();
        $this->assertEquals('approved_transferred', $claim->status);
        $this->assertEquals(2_000.0, (float) $claim->total_gross_profit);
        $this->assertEquals(600.0, (float) $claim->total_master_gross_share);
        $this->assertEquals(480.0, (float) $claim->total_master_net_share);
        $this->assertEquals(1_400.0, (float) $claim->total_investor_net_profit);
        $this->assertEquals(120.0, (float) $claim->total_affiliate_commission);
        $this->assertEquals(120.0, (float) $claim->total_nominal_transfer);
        $this->assertNotNull($claim->bukti_transfer_path);
        $this->assertEquals($this->admin->id, $claim->processed_by);
        $this->assertNotNull($claim->processed_at);

        $item->refresh();
        $this->assertEquals(20.0, (float) $item->roi_percentage);
        $this->assertEquals(2_000.0, (float) $item->gross_profit);
        $this->assertEquals(600.0, (float) $item->master_gross_share);
        $this->assertEquals(120.0, (float) $item->affiliate_commission);
        $this->assertEquals(480.0, (float) $item->master_net_share);
        $this->assertEquals(1_400.0, (float) $item->investor_net_profit);

        Storage::disk('local')->assertExists($claim->bukti_transfer_path);

        Notification::assertSentTo($this->affiliator, CommissionClaimProcessedNotification::class);
    }

    public function test_admin_approval_with_drawdown_guard_yields_zero_commission(): void
    {
        Notification::fake();

        $claim = KlaimKomisi::create([
            'kode_klaim'           => 'CLM-202609-0099',
            'affiliator_id'        => $this->affiliator->id,
            'total_downline'       => 1,
            'total_akun'           => 1,
            'total_modal_riil'     => 10_000,
            'total_deposit_amount' => 10_000,
            'nama_bank'            => 'BCA',
            'nomor_rekening'       => '1234567890',
            'atas_nama_rekening'   => 'Affiliator YZD',
            'status'               => 'pending',
        ]);
        $item = $claim->items()->create([
            'downline_id'         => $this->downline->id,
            'broker'              => 'HFM',
            'nomor_akun_trading'  => '99999',
            'is_akun_pertama'     => true,
            'tanggal_mulai'       => now()->subDays(90)->toDateString(),
            'tanggal_selesai'     => now()->toDateString(),
            'hari_kehitung'       => 90,
            'modal_riil'          => 10_000,
            'deposit_amount'      => 10_000,
            'tier_level'          => 4,
            'persentase_tier'     => 20,
            'master_share_pct'    => 30.00,
            'affiliate_share_pct' => 20.00,
        ]);

        $receiptFile = UploadedFile::fake()->create('bukti_transfer.jpg', 300, 'image/jpeg');

        // Drawdown: ROI = -15%
        // gross_profit = -$1,500 <= 0 -> Drawdown Guard:
        // affiliate_commission = 0, master_gross_share = 0, master_net_share = 0, investor_net_profit = -$1,500
        $response = $this->actingAs($this->admin)->post("/admin/commissions/{$claim->id}/process", [
            'status' => 'approved_transferred',
            'items'  => [
                [
                    'id'                  => $item->id,
                    'roi_percentage'      => -15.0,
                    'master_share_pct'    => 30.0,
                    'affiliate_share_pct' => 20.0,
                ],
            ],
            'bukti_transfer' => $receiptFile,
            'catatan_admin'  => 'Periode drawdown: tidak ada pemotongan bagi hasil dan komisi afiliator nihil.',
        ]);

        $response->assertRedirect('/admin/commissions');
        $response->assertSessionHas('success');

        $claim->refresh();
        $this->assertEquals(-1_500.0, (float) $claim->total_gross_profit);
        $this->assertEquals(0.0, (float) $claim->total_master_gross_share);
        $this->assertEquals(0.0, (float) $claim->total_master_net_share);
        $this->assertEquals(-1_500.0, (float) $claim->total_investor_net_profit);
        $this->assertEquals(0.0, (float) $claim->total_affiliate_commission);
        $this->assertEquals(0.0, (float) $claim->total_nominal_transfer);

        $item->refresh();
        $this->assertEquals(-15.0, (float) $item->roi_percentage);
        $this->assertEquals(-1_500.0, (float) $item->gross_profit);
        $this->assertEquals(0.0, (float) $item->master_gross_share);
        $this->assertEquals(0.0, (float) $item->affiliate_commission);
        $this->assertEquals(0.0, (float) $item->master_net_share);
        $this->assertEquals(-1_500.0, (float) $item->investor_net_profit);
    }

    public function test_admin_can_reject_commission_claim_with_reason(): void
    {
        Notification::fake();

        $claim = KlaimKomisi::create([
            'kode_klaim'         => 'CLM-202609-0003',
            'affiliator_id'      => $this->affiliator->id,
            'total_downline'     => 1,
            'total_akun'         => 1,
            'total_modal_riil'   => 500_000_000,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Affiliator YZD',
            'status'             => 'pending',
        ]);
        $claim->items()->create([
            'downline_id'        => $this->downline->id,
            'broker'             => 'HFM',
            'nomor_akun_trading' => '12345',
            'is_akun_pertama'    => true,
            'tanggal_mulai'      => now()->subDays(90)->toDateString(),
            'tanggal_selesai'    => now()->toDateString(),
            'hari_kehitung'      => 90,
            'modal_riil'         => 500_000_000,
            'tier_level'         => 3,
            'persentase_tier'    => 30,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/commissions/{$claim->id}/process", [
            'status'        => 'rejected',
            'catatan_admin' => 'Data akun investor tidak sinkron dengan catatan broker partner.',
        ]);

        $response->assertRedirect('/admin/commissions');
        $response->assertSessionHas('success');

        $claim->refresh();
        $this->assertEquals('rejected', $claim->status);
        $this->assertEquals('Data akun investor tidak sinkron dengan catatan broker partner.', $claim->catatan_admin);
        $this->assertNull($claim->total_profit_master);
        $this->assertNull($claim->total_nominal_transfer);

        Notification::assertSentTo($this->affiliator, CommissionClaimProcessedNotification::class);
    }

    public function test_only_authorized_user_can_stream_commission_documents(): void
    {
        $claim = KlaimKomisi::create([
            'kode_klaim'             => 'CLM-202609-0004',
            'affiliator_id'          => $this->affiliator->id,
            'total_downline'         => 1,
            'total_akun'             => 1,
            'total_modal_riil'       => 100_000_000,
            'total_profit_master'    => 50_000_000,
            'total_nominal_transfer' => 15_000_000,
            'bukti_transfer_path'    => 'commission_claims/transfers/sample_receipt.jpg',
            'nama_bank'              => 'BCA',
            'nomor_rekening'         => '1234567890',
            'atas_nama_rekening'     => 'Affiliator YZD',
            'status'                 => 'approved_transferred',
        ]);

        Storage::disk('local')->put('commission_claims/transfers/sample_receipt.jpg', 'fake-image-content');

        $otherUser = User::factory()->create([
            'role'   => 'investor',
            'status' => 'active',
        ]);

        // Unauthorized user cannot view receipt
        $response = $this->actingAs($otherUser)->get("/documents/commissions/{$claim->id}/receipt");
        $response->assertStatus(403);

        // Affiliator owner can view receipt
        $response = $this->actingAs($this->affiliator)->get("/documents/commissions/{$claim->id}/receipt");
        $response->assertStatus(200);

        // Admin can view receipt
        $response = $this->actingAs($this->admin)->get("/documents/commissions/{$claim->id}/receipt");
        $response->assertStatus(200);
    }

    public function test_commission_calculation_formula_and_mathematical_balance(): void
    {
        // 1. Kasus Profit Normal: Modal $10,000 | ROI 25% | Hak Master 30% | Komisi Afiliator 40% (Tier 2)
        $res = KlaimKomisiItem::calculateCommission(
            depositAmount: 10_000,
            roiPercentage: 25.0,
            masterSharePct: 30.0,
            affiliateSharePct: 40.0
        );

        $this->assertEquals(2_500.0, $res['gross_profit']);
        $this->assertEquals(750.0, $res['master_gross_share']);
        $this->assertEquals(300.0, $res['affiliate_commission']);
        $this->assertEquals(450.0, $res['master_net_share']);
        $this->assertEquals(1_750.0, $res['investor_net_profit']);
        $this->assertFalse($res['is_drawdown']);

        // Keseimbangan Matematika: Investor Net + Master Net + Komisi Afiliator == Gross Profit
        $totalAllocated = $res['investor_net_profit'] + $res['master_net_share'] + $res['affiliate_commission'];
        $this->assertEquals($res['gross_profit'], $totalAllocated);

        // 2. Kasus Drawdown / Loss: Modal $5,000 | ROI -10%
        $loss = KlaimKomisiItem::calculateCommission(
            depositAmount: 5_000,
            roiPercentage: -10.0,
            masterSharePct: 30.0,
            affiliateSharePct: 20.0
        );

        $this->assertEquals(-500.0, $loss['gross_profit']);
        $this->assertEquals(0.0, $loss['master_gross_share']);
        $this->assertEquals(0.0, $loss['affiliate_commission']);
        $this->assertEquals(0.0, $loss['master_net_share']);
        $this->assertEquals(-500.0, $loss['investor_net_profit']);
        $this->assertTrue($loss['is_drawdown']);

        // 3. Kasus Break-Even (0% ROI)
        $even = KlaimKomisiItem::calculateCommission(
            depositAmount: 5_000,
            roiPercentage: 0.0,
            masterSharePct: 30.0,
            affiliateSharePct: 20.0
        );

        $this->assertEquals(0.0, $even['gross_profit']);
        $this->assertEquals(0.0, $even['master_gross_share']);
        $this->assertEquals(0.0, $even['affiliate_commission']);
        $this->assertEquals(0.0, $even['master_net_share']);
        $this->assertEquals(0.0, $even['investor_net_profit']);
        $this->assertTrue($even['is_drawdown']);
    }
}
