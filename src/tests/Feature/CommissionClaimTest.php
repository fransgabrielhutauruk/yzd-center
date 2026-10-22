<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\KlaimKomisi;
use App\Models\KlaimKomisiItem;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Notifications\CommissionClaimProcessedNotification;
use App\Notifications\CommissionClaimSubmittedNotification;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

        // Set kurs tetap $1 = Rp 16.000 di Cache untuk pengujian deterministik tanpa HTTP request keluar
        Cache::put(ExchangeRateService::CACHE_KEY, [
            'rate'       => 16000.0,
            'updated_at' => now()->toIso8601String(),
            'date'       => now()->toDateString(),
            'is_live'    => false,
            'source'     => 'test',
        ], 3600);

        $this->admin = User::factory()->create([
            'role'   => 'administrator',
            'status' => 'active',
        ]);

        $this->affiliator = User::factory()->create([
            'role'   => 'member',
            'status' => 'active',
        ]);

        $this->downline = User::factory()->create([
            'role'   => 'member',
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
        $response = $this->actingAs($this->affiliator)->get('/member/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Commissions/Index')
            ->has('stats')
            ->has('claims')
            ->has('filters')
            ->has('eligibleDownlines')
            ->has('defaultBank')
            ->has('exchangeRate')
        );
    }

    public function test_downline_with_less_than_3_months_copytrade_is_not_eligible(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-16 10:00:00'));

        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '99881122',
            'modal_riil'         => 3000, // $3,000 * 16,000 = Rp 48.000.000 (Tier 4)
            'tanggal_mulai'      => '2026-04-01', // baru 45 hari dari periode 1 Apr - 1 Jul (91 hari)
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);

        $response = $this->actingAs($this->affiliator)->get('/member/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Commissions/Index')
            ->where('eligibleDownlines.0.is_eligible', false)
            ->where('eligibleDownlines.0.active_days', 45)
            ->where('eligibleDownlines.0.target_days', 91)
        );
    }

    public function test_downline_with_3_months_same_date_copytrade_is_eligible(): void
    {
        // Mulai 1 April 2026 -> Jatuh tempo 3 bulan di tanggal yang sama: 1 Juli 2026 (91 hari)
        Carbon::setTestNow(Carbon::parse('2026-07-01 10:00:00'));

        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '88776655',
            'modal_riil'         => 10000, // $10,000 * Rp 16.000 = Rp 160.000.000 (Tier 3 - 30%)
            'tanggal_mulai'      => '2026-04-01',
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '9876543210',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);

        $response = $this->actingAs($this->affiliator)->get('/member/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Commissions/Index')
            ->where('eligibleDownlines.0.is_eligible', true)
            ->where('eligibleDownlines.0.first_started_at', '01 April 2026')
            ->where('eligibleDownlines.0.period_end_date', '01 Juli 2026')
            ->where('eligibleDownlines.0.active_days', 91)
            ->where('eligibleDownlines.0.target_days', 91)
            ->where('eligibleDownlines.0.total_modal_riil', 10000)
            ->where('eligibleDownlines.0.total_modal_idr', 160000000)
            ->where('eligibleDownlines.0.tier_info.tier_level', 3)
            ->where('eligibleDownlines.0.tier_info.persentase_tier', 30)
            ->where('eligibleDownlines.0.accounts.0.hari_kehitung', 91)
            ->where('eligibleDownlines.0.accounts.0.tanggal_mulai_raw', '2026-04-01')
            ->where('eligibleDownlines.0.accounts.0.tanggal_selesai_raw', '2026-07-01')
        );
    }

    public function test_multi_copytrade_calculates_earliest_start_combined_modal_and_account_days(): void
    {
        // Waktu sekarang: 10 Juli 2026 (100 hari sejak 1 April 2026)
        // Periode 3 bulan dari 1 April 2026 adalah 1 April 2026 - 1 Juli 2026 (91 hari)
        Carbon::setTestNow(Carbon::parse('2026-07-10 10:00:00'));

        // Akun 1: mulai 1 April 2026, modal $100,000 (Rp 1,6 Milyar pada kurs Rp 16.000 -> Tier 2, 40%)
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_2',
            'broker'             => 'HFM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '112233',
            'modal_riil'         => 100000,
            'tanggal_mulai'      => '2026-04-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);

        // Akun 2: mulai 21 April 2026 (20 hari setelah Akun 1), modal $3,000 (Rp 48 Juta pada kurs Rp 16.000 -> Tier 4, 20%)
        // Tier tidak digabungkan: Akun 1 tetap Tier 2 (40%), Akun 2 tetap Tier 4 (20%)
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_4',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '445566',
            'modal_riil'         => 3000,
            'tanggal_mulai'      => '2026-04-21',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);

        // Akun 3: mulai 5 Juli 2026 (di luar periode 1 April - 1 Juli 2026), modal $31,250 (Rp 500 Juta)
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_3',
            'broker'             => 'HFM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '778899',
            'modal_riil'         => 31250,
            'tanggal_mulai'      => '2026-07-05',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);

        $response = $this->actingAs($this->affiliator)->get('/member/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Commissions/Index')
            ->where('eligibleDownlines.0.has_multi_copytrade', true)
            ->where('eligibleDownlines.0.jumlah_akun', 2)
            ->where('eligibleDownlines.0.total_modal_riil', 103000) // $100,000 + $3,000 = $103,000 (Akun 3 di luar periode 3 bulan tidak dihitung)
            ->where('eligibleDownlines.0.total_modal_idr', 1648000000) // $103,000 * 16,000 = Rp 1,648 Milyar
            ->where('eligibleDownlines.0.active_days', 100)
            ->where('eligibleDownlines.0.target_days', 91)
            ->where('eligibleDownlines.0.is_eligible', true)
            ->where('eligibleDownlines.0.accounts.0.is_first', true)
            ->where('eligibleDownlines.0.accounts.0.hari_kehitung', 91) // 1 Apr - 1 Jul = 91 hari
            ->where('eligibleDownlines.0.accounts.0.tier_level', 2) // Akun 1 = Tier 2 (40%)
            ->where('eligibleDownlines.0.accounts.0.persentase_tier', 40)
            ->where('eligibleDownlines.0.accounts.1.is_first', false)
            ->where('eligibleDownlines.0.accounts.1.selisih_hari', 20) // Mulai hari ke-20 (21 Apr)
            ->where('eligibleDownlines.0.accounts.1.hari_kehitung', 71) // 21 Apr - 1 Jul = 71 hari
            ->where('eligibleDownlines.0.accounts.1.tier_level', 4) // Akun 2 = Tier 4 (20%)
            ->where('eligibleDownlines.0.accounts.1.persentase_tier', 20)
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
        Carbon::setTestNow(Carbon::parse('2026-07-10 10:00:00'));

        // Downline 1 (mulai 1 April 2026 -> periode 1 Apr - 1 Jul = 91 hari, memiliki 2 akun dengan Tier berbeda)
        // Akun 1: $100,000 (Rp 1,6 Milyar -> Tier 2, 40%)
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_2',
            'broker'             => 'HFM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '55443322',
            'modal_riil'         => 100000,
            'tanggal_mulai'      => '2026-04-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);
        // Akun 2: mulai 1 Mei 2026 (selisih 30 hari -> 1 Mei - 1 Jul = 61 hari kehitung), modal $3,000 (Rp 48 Juta -> Tier 4, 20%)
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_4',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '99887711',
            'modal_riil'         => 3000,
            'tanggal_mulai'      => '2026-05-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);

        // Downline 2 (mulai 1 April 2026 -> 91 hari, memiliki 1 akun modal $22,000 = Rp 352 Juta -> Tier 3 30%)
        $downline2 = User::factory()->create([
            'role'   => 'member',
            'status' => 'active',
        ]);
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $downline2->id,
            'sumber'        => 'link_otomatis',
            'status'        => 'active',
            'joined_at'     => '2026-03-15',
        ]);
        AkunCopytrade::create([
            'user_id'            => $downline2->id,
            'tier'               => 'tier_3',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '99887766',
            'modal_riil'         => 22000,
            'tanggal_mulai'      => '2026-04-01',
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => $downline2->name,
            'status'             => 'active',
        ]);

        $response = $this->actingAs($this->affiliator)->post('/member/commissions', [
            'downline_ids'       => [$this->downline->id, $downline2->id],
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '7788990011',
            'atas_nama_rekening' => 'Affiliator YZD',
        ]);

        $response->assertRedirect('/member/commissions');
        $response->assertSessionHas('success');

        // VERIFIKASI TEPAT 1 ROW TIKET KLAIM DI DATABASE
        $this->assertEquals(1, KlaimKomisi::count());

        $claim = KlaimKomisi::first();
        $this->assertNotNull($claim);
        $this->assertEquals(2, $claim->total_downline);
        $this->assertEquals(3, $claim->total_akun); // 2 akun downline 1 + 1 akun downline 2
        $this->assertEquals(125000.0, (float) $claim->total_modal_riil); // $100,000 + $3,000 + $22,000 = $125,000
        $this->assertEquals('pending', $claim->status);

        // Dan 3 baris akun di tabel klaim_komisi_items
        $this->assertEquals(3, $claim->items()->count());

        $item1 = $claim->items()->where('downline_id', $this->downline->id)->where('is_akun_pertama', true)->first();
        $this->assertNotNull($item1);
        $this->assertEquals(91, $item1->hari_kehitung);
        $this->assertEquals('2026-04-01', $item1->tanggal_mulai->toDateString());
        $this->assertEquals('2026-07-01', $item1->tanggal_selesai->toDateString());
        $this->assertEquals(2, $item1->tier_level); // Akun 1: $100,000 * 16,000 = Rp 1,6 Milyar -> Tier 2 (40%)
        $this->assertEquals(40, $item1->persentase_tier);

        $item2 = $claim->items()->where('downline_id', $this->downline->id)->where('is_akun_pertama', false)->first();
        $this->assertNotNull($item2);
        $this->assertEquals(61, $item2->hari_kehitung); // 1 Mei - 1 Jul = 61 hari
        $this->assertEquals(4, $item2->tier_level); // Akun 2: $3,000 * 16,000 = Rp 48 Juta -> Tier 4 (20%)
        $this->assertEquals(20, $item2->persentase_tier);

        $item3 = $claim->items()->where('downline_id', $downline2->id)->first();
        $this->assertNotNull($item3);
        $this->assertEquals(91, $item3->hari_kehitung);
        $this->assertEquals(3, $item3->tier_level); // $22,000 * 16,000 = Rp 352 Juta -> Tier 3 (30%)
        $this->assertEquals(30, $item3->persentase_tier);

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

        $response = $this->actingAs($this->affiliator)->post('/member/commissions', [
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
        // Kurs Konversi Riil MT4 (Input Manual Admin): Rp 16,250 / $1 -> Total Transfer IDR = $120 * 16,250 = Rp 1,950,000
        $response = $this->actingAs($this->admin)->post("/admin/commissions/{$claim->id}/process", [
            'status'            => 'approved_transferred',
            'kurs_konversi_idr' => 16250,
            'items'             => [
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
        $this->assertEquals(16_250.0, (float) $claim->kurs_konversi_idr);
        $this->assertEquals(1_950_000.0, (float) $claim->total_nominal_transfer_idr);
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
            'role'   => 'member',
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

    public function test_downlines_are_sorted_by_no_copytrade_first_then_earliest_copytrade_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 10:00:00'));

        // Downline 1 ($this->downline): mulai copytrade lebih baru (15 April 2026)
        AkunCopytrade::create([
            'user_id'            => $this->downline->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '100001',
            'modal_riil'         => 5000,
            'tanggal_mulai'      => '2026-04-15',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->downline->name,
            'status'             => 'active',
        ]);

        // Downline 2: mulai copytrade lebih awal (1 Maret 2026)
        $downlineEarlier = User::factory()->create(['role' => 'member', 'status' => 'active']);
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $downlineEarlier->id,
            'sumber'        => 'link_otomatis',
            'status'        => 'active',
            'joined_at'     => '2026-02-20',
        ]);
        AkunCopytrade::create([
            'user_id'            => $downlineEarlier->id,
            'tier'               => 'tier_1',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '100002',
            'modal_riil'         => 5000,
            'tanggal_mulai'      => '2026-03-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $downlineEarlier->name,
            'status'             => 'active',
        ]);

        // Downline 3: belum ada copytrade sama sekali
        $downlineNoCopytrade = User::factory()->create(['role' => 'member', 'status' => 'active']);
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $downlineNoCopytrade->id,
            'sumber'        => 'link_otomatis',
            'status'        => 'active',
            'joined_at'     => '2026-05-01',
        ]);

        $response = $this->actingAs($this->affiliator)->get('/member/commissions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Commissions/Index')
            ->where('eligibleDownlines.0.id', $downlineNoCopytrade->id)
            ->where('eligibleDownlines.0.first_started_at_raw', null)
            ->where('eligibleDownlines.1.id', $downlineEarlier->id)
            ->where('eligibleDownlines.1.first_started_at_raw', '2026-03-01')
            ->where('eligibleDownlines.2.id', $this->downline->id)
            ->where('eligibleDownlines.2.first_started_at_raw', '2026-04-15')
        );
    }

    public function test_commission_claim_processed_notification_formats_idr_correctly(): void
    {
        $claim = KlaimKomisi::create([
            'kode_klaim'                 => 'CLM-202610-0003',
            'affiliator_id'              => $this->affiliator->id,
            'total_downline'             => 1,
            'total_akun'                 => 1,
            'total_modal_riil'           => 100,
            'total_deposit_amount'       => 100,
            'total_affiliate_commission' => 0.30,
            'total_nominal_transfer'     => 0.30,
            'kurs_konversi_idr'          => 15000,
            'total_nominal_transfer_idr' => 4500,
            'nama_bank'                  => 'SeaBank',
            'nomor_rekening'             => '12345678',
            'atas_nama_rekening'         => 'Affiliator Test',
            'status'                     => 'approved_transferred',
        ]);

        $notification = new CommissionClaimProcessedNotification($claim, 'approved_transferred');
        $data = $notification->toArray($this->affiliator);

        $this->assertEquals('Pencairan Komisi Berhasil Ditransfer', $data['title']);
        $this->assertStringContainsString('telah dicairkan sebesar Rp 4.500.', $data['message']);
        $this->assertEquals(4500.0, (float) $data['total_nominal_transfer_idr']);
    }

    public function test_commission_claim_submitted_notification_formats_modal_in_usd(): void
    {
        $claim = KlaimKomisi::create([
            'kode_klaim'           => 'CLM-202610-0004',
            'affiliator_id'        => $this->affiliator->id,
            'total_downline'       => 1,
            'total_akun'           => 1,
            'total_modal_riil'     => 100,
            'total_deposit_amount' => 100,
            'nama_bank'            => 'BCA',
            'nomor_rekening'       => '12345678',
            'atas_nama_rekening'   => 'Affiliator Test',
            'status'               => 'pending',
        ]);

        $notification = new CommissionClaimSubmittedNotification($claim);
        $data = $notification->toArray($this->admin);

        $this->assertEquals('Permohonan Klaim Komisi Baru (90 Hari)', $data['title']);
        $this->assertStringContainsString('dengan total modal $100.', $data['message']);
    }
}
