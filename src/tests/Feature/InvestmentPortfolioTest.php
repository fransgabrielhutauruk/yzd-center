<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\LaporanProfitBulanan;
use App\Models\PengaturanMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvestmentPortfolioTest extends TestCase
{
    use RefreshDatabase;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        PengaturanMaster::create([
            'kurs_usd_idr'            => 16796.00,
            'kurs_usd_idr_updated_at' => now(),
        ]);

        $this->investor = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    public function test_investor_can_view_unified_investments_portfolio_page(): void
    {
        $response = $this->actingAs($this->investor)
            ->get(route('member.investments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Investments/Index')
            ->has('consolidated')
            ->has('accounts')
            ->where('consolidated.total_accounts', 0)
        );
    }

    public function test_investor_can_register_new_investment_account(): void
    {
        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_1',
                'nomor_akun_trading'    => 'MT4-889900',
                'modal_riil'            => 150.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'dokumen_perjanjian'    => $file,
                'myfxbook_url'          => 'https://www.myfxbook.com/members/test/portfolio/12345',
            ]);

        $response->assertRedirect(route('member.investments.index'));
        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'nomor_akun_trading' => 'MT4-889900',
            'modal_riil'         => 150.00,
            'status'             => 'pending',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/test/portfolio/12345',
        ]);
    }

    public function test_consolidated_summary_accurately_aggregates_multiple_accounts(): void
    {
        // Akun 1: Modal $100, Laporan profit $20
        $akun1 = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-ACC1',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '11111',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/test/acc1/111',
        ]);
        LaporanProfitBulanan::create([
            'nomor'               => 'LPB-2026-001',
            'akun_copytrade_id'   => $akun1->id,
            'user_id'             => $this->investor->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-01-01',
            'periode_akhir'       => '2026-01-31',
            'jatuh_tempo'         => '2026-02-01',
            'profit_usd'          => 20.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 6.00,
            'wd_usd'              => 10.00,
            'wd_idr'              => 160000,
            'kurs_riil'           => 16000.00,
            'fee_idr'             => 96000,
            'bukti_myfxbook_path' => 'laporan-profit/myfxbook/1.jpg',
            'bukti_wd_path'       => 'laporan-profit/wd/1.jpg',
            'status'              => 'lunas',
        ]);

        // Akun 2 (Tier 3 Managed VPS): Modal $300,000, Laporan profit $15,000
        $akun2 = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_3',
            'broker'             => 'XM',
            'tipe_akun'          => 'private_vps',
            'nomor_akun_trading' => 'MT4-ACC2',
            'modal_riil'         => 300000.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '22222',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/test/acc2/222',
        ]);
        LaporanProfitBulanan::create([
            'nomor'               => 'LPB-2026-002',
            'akun_copytrade_id'   => $akun2->id,
            'user_id'             => $this->investor->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-01-01',
            'periode_akhir'       => '2026-01-31',
            'jatuh_tempo'         => '2026-02-01',
            'profit_usd'          => 15000.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 4500.00,
            'wd_usd'              => 7500.00,
            'wd_idr'              => 120000000,
            'kurs_riil'           => 16000.00,
            'fee_idr'             => 72000000,
            'bukti_myfxbook_path' => 'laporan-profit/myfxbook/2.jpg',
            'bukti_wd_path'       => 'laporan-profit/wd/2.jpg',
            'status'              => 'lunas',
        ]);

        $response = $this->actingAs($this->investor)
            ->get(route('member.investments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Investments/Index')
            ->where('consolidated.total_accounts', 2)
            ->where('consolidated.active_accounts_count', 2)
            ->where('consolidated.total_initial_capital', fn ($val) => (float) $val === 300100.0)
            ->where('consolidated.total_current_balance', fn ($val) => (float) $val === 315120.0)
            ->where('consolidated.total_all_time_profit', fn ($val) => (float) $val === 15020.0)
            ->has('accounts', 2)
        );
    }

    public function test_legacy_copytrade_routes_redirect_to_investments_page(): void
    {
        $resp = $this->actingAs($this->investor)
            ->get(route('member.copytrade.index'));
        $resp->assertRedirect(route('member.investments.index'));
    }

    public function test_unauthenticated_user_cannot_access_investments(): void
    {
        $response = $this->get(route('member.investments.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_rejected_accounts_are_hidden_from_investor_portfolio_page(): void
    {
        // Akun 1: aktif
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-ACTIVE',
            'modal_riil'         => 100.00,
            'status'             => 'active',
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '123',
            'atas_nama_rekening' => 'Investor',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/demo/active',
        ]);

        // Akun 2: ditolak
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => 'MT4-REJECTED',
            'modal_riil'         => 5000.00,
            'status'             => 'rejected',
            'catatan_admin'      => 'Password investor tidak valid',
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '123',
            'atas_nama_rekening' => 'Investor',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/demo/rej',
        ]);

        $response = $this->actingAs($this->investor)
            ->get(route('member.investments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('accounts', 1)
            ->where('accounts.0.nomor_akun_trading', 'MT4-ACTIVE')
        );
    }

    public function test_pending_accounts_do_not_count_towards_financial_statistics(): void
    {
        // Hanya punya akun pending
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-PENDING',
            'modal_riil'         => 200.00,
            'status'             => 'pending',
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '123',
            'atas_nama_rekening' => 'Investor',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/demo/pending',
        ]);

        $response = $this->actingAs($this->investor)
            ->get(route('member.investments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('consolidated.has_active_accounts', false)
            ->where('consolidated.active_accounts_count', 0)
            ->where('consolidated.pending_accounts_count', 1)
            ->where('consolidated.total_initial_capital', fn ($val) => (float) $val === 0.0)
            ->where('consolidated.total_current_balance', fn ($val) => (float) $val === 0.0)
            ->where('consolidated.total_all_time_profit', fn ($val) => (float) $val === 0.0)
            ->has('accounts', 1)
            ->where('accounts.0.status', 'pending')
        );
    }

    public function test_tier_1_accepts_deposit_below_100_and_rejects_5000_or_above(): void
    {
        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');

        // Modal $50 (di bawah $100) harus BERHASIL didaftarkan untuk Tier 1
        $responseSuccess = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_1',
                'nomor_akun_trading'    => 'MT4-TIER1-50',
                'modal_riil'            => 50.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'dokumen_perjanjian'    => $file,
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/tier1',
            ]);

        $responseSuccess->assertRedirect(route('member.investments.index'));
        $this->assertDatabaseHas('akun_copytrade', [
            'nomor_akun_trading' => 'MT4-TIER1-50',
            'modal_riil'         => 50.00,
            'tier'               => 'tier_1',
        ]);

        // Modal $5,000 (tidak boleh untuk Tier 1, karena harus di bawah $5,000)
        $responseFail = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_1',
                'nomor_akun_trading'    => 'MT4-TIER1-5000',
                'modal_riil'            => 5000.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/tier1fail',
            ]);

        $responseFail->assertSessionHasErrors('modal_riil');
    }

    public function test_tier_2_validates_range_between_5000_and_300000(): void
    {
        // Modal $4,999 (di bawah $5,000) harus gagal di Tier 2
        $responseUnder = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_2',
                'nomor_akun_trading'    => 'MT4-TIER2-UNDER',
                'modal_riil'            => 4999.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/tier2under',
            ]);
        $responseUnder->assertSessionHasErrors('modal_riil');

        // Modal $300,001 (di atas $300,000) harus gagal di Tier 2
        $responseOver = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_2',
                'nomor_akun_trading'    => 'MT4-TIER2-OVER',
                'modal_riil'            => 300001.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/tier2over',
            ]);
        $responseOver->assertSessionHasErrors('modal_riil');

        // Modal $10,000 (valid range $5,000 s/d $300,000) harus berhasil
        $file = UploadedFile::fake()->create('surat.pdf', 200, 'application/pdf');
        $responseValid = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_2',
                'nomor_akun_trading'    => 'MT4-TIER2-VALID',
                'modal_riil'            => 10000.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'dokumen_perjanjian'    => $file,
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/tier2valid',
            ]);
        $responseValid->assertRedirect(route('member.investments.index'));
        $this->assertDatabaseHas('akun_copytrade', [
            'nomor_akun_trading' => 'MT4-TIER2-VALID',
            'tier'               => 'tier_2',
        ]);
    }

    public function test_tier_3_validates_minimum_300000(): void
    {
        // Modal $299,999 (di bawah $300,000) harus gagal di Tier 3
        $responseUnder = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_3',
                'nomor_akun_trading'    => 'MT4-TIER3-UNDER',
                'modal_riil'            => 299999.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'server_broker'         => 'XMGlobal-Real 50',
                'master_password'       => 'MasterSecret123',
                'investor_password'     => 'InvestorSecret456',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/tier3under',
            ]);
        $responseUnder->assertSessionHasErrors('modal_riil');

        // Modal $300,000 (tepat minimal) harus berhasil di Tier 3
        $file = UploadedFile::fake()->create('surat.pdf', 200, 'application/pdf');
        $responseValid = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_3',
                'nomor_akun_trading'    => 'MT4-TIER3-VALID',
                'modal_riil'            => 300000.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345678',
                'atas_nama_rekening'    => 'Investor Test',
                'server_broker'         => 'XMGlobal-Real 50',
                'master_password'       => 'MasterSecret123',
                'investor_password'     => 'InvestorSecret456',
                'dokumen_perjanjian'    => $file,
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/tier3valid',
            ]);
        $responseValid->assertRedirect(route('member.investments.index'));
        $this->assertDatabaseHas('akun_copytrade', [
            'nomor_akun_trading' => 'MT4-TIER3-VALID',
            'tier'               => 'tier_3',
        ]);
    }

    public function test_subsequent_investment_account_does_not_require_bank_and_agreement_and_inherits_them(): void
    {
        // 1. Akun pertama: mendaftar dengan bank & berkas perjanjian
        $file = UploadedFile::fake()->create('perjanjian_awal.pdf', 300, 'application/pdf');
        $resp1 = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_1',
                'nomor_akun_trading'    => 'MT4-ACC-FIRST',
                'modal_riil'            => 1000.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'Mandiri',
                'nomor_rekening'        => '9876543210',
                'atas_nama_rekening'    => 'Investor Pertama',
                'dokumen_perjanjian'    => $file,
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/first',
            ]);
        $resp1->assertRedirect(route('member.investments.index'));
        $firstAccount = AkunCopytrade::where('nomor_akun_trading', 'MT4-ACC-FIRST')->firstOrFail();

        // 2. Akun kedua: mendaftar TANPA mengirim bank dan TANPA mengirim surat perjanjian (karena field disembunyikan)
        $resp2 = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_2',
                'nomor_akun_trading'    => 'MT4-ACC-SECOND',
                'modal_riil'            => 15000.00,
                'tanggal_mulai_trading' => '2026-03-01',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/second',
            ]);
        $resp2->assertRedirect(route('member.investments.index'));

        // 3. Pastikan akun kedua berhasil tersimpan dan mewarisi data bank & perjanjian dari akun sebelumnya
        $secondAccount = AkunCopytrade::where('nomor_akun_trading', 'MT4-ACC-SECOND')->firstOrFail();
        $this->assertEquals('Mandiri', $secondAccount->nama_bank);
        $this->assertEquals('9876543210', $secondAccount->nomor_rekening);
        $this->assertEquals('Investor Pertama', $secondAccount->atas_nama_rekening);
        $this->assertEquals($firstAccount->surat_perjanjian_path, $secondAccount->surat_perjanjian_path);
    }

    public function test_download_tier_3_agreement_template_includes_server_broker_and_passwords(): void
    {
        $params = [
            'nomor_akun_trading'    => '99887733',
            'modal_riil'            => '350000',
            'tanggal_mulai_trading' => '2026-05-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '8899001122',
            'atas_nama_rekening'    => $this->investor->name,
            'server_broker'         => 'XMGlobal-Real 55',
            'master_password'       => 'MasterSecret123!',
            'investor_password'     => 'InvSecret456!',
        ];

        $response = $this->actingAs($this->investor)
            ->get(route('member.investments.template', ['tier' => 'tier_3']) . '?' . http_build_query($params));

        $response->assertStatus(200);
        $this->assertStringContainsString('Surat_Perjanjian_Kemitraan_TIER3_', $response->headers->get('content-disposition'));

        $binaryContent = $response->streamedContent();
        $tempPath = tempnam(sys_get_temp_dir(), 'test_tier3_') . '.docx';
        file_put_contents($tempPath, $binaryContent);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tempPath));
        $xml = $zip->getFromName('word/document.xml');

        $this->assertStringContainsString('XMGlobal-Real 55', $xml);
        $this->assertStringContainsString('MasterSecret123!', $xml);
        $this->assertStringContainsString('InvSecret456!', $xml);
        $this->assertStringContainsString('99887733', $xml);

        $zip->close();
        unlink($tempPath);
    }

    public function test_download_agreement_template_properly_escapes_special_characters_producing_valid_xml(): void
    {
        $this->investor->update([
            'name'      => 'Investor & Partner <VIP>',
            'pekerjaan' => 'Trader & Business Consultant',
        ]);

        foreach (['tier_1', 'tier_2', 'tier_3'] as $tier) {
            $params = [
                'nomor_akun_trading' => '12345678',
                'modal_riil'         => '10000',
                'nama_bank'          => 'BCA & Mandiri',
                'nomor_rekening'     => '1234567890',
                'atas_nama_rekening' => 'Investor & Co',
            ];

            $response = $this->actingAs($this->investor)
                ->get(route('member.investments.template', ['tier' => $tier]) . '?' . http_build_query($params));

            $response->assertStatus(200);

            $binaryContent = $response->streamedContent();
            $tempPath = tempnam(sys_get_temp_dir(), 'test_xml_escape_') . '.docx';
            file_put_contents($tempPath, $binaryContent);

            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($tempPath));

            $xml = $zip->getFromName('word/document.xml');
            libxml_use_internal_errors(true);
            $parsed = simplexml_load_string($xml);
            $this->assertNotFalse($parsed, "XML in {$tier} template must be valid, but had errors: " . json_encode(libxml_get_errors()));

            $this->assertStringContainsString('Trader &amp; Business Consultant', $xml);
            $this->assertStringContainsString('Investor &amp; Partner &lt;VIP&gt;', $xml);

            $zip->close();
            unlink($tempPath);
        }
    }

    public function test_investor_can_view_account_performance_history_from_profit_reports(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/demo/perf',
        ]);

        LaporanProfitBulanan::create([
            'nomor'               => 'LPB-2026-003',
            'akun_copytrade_id'   => $akun->id,
            'user_id'             => $this->investor->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-01-01',
            'periode_akhir'       => '2026-01-31',
            'jatuh_tempo'         => '2026-02-01',
            'profit_usd'          => 100,
            'fee_persen'          => 30,
            'fee_usd'             => 30,
            'wd_usd'              => 50,
            'wd_idr'              => 800000,
            'kurs_riil'           => 16000,
            'fee_idr'             => 480000,
            'bukti_myfxbook_path' => 'myfx.jpg',
            'bukti_wd_path'       => 'wd.jpg',
            'status'              => 'lunas',
        ]);

        LaporanProfitBulanan::create([
            'nomor'               => 'LPB-2026-004',
            'akun_copytrade_id'   => $akun->id,
            'user_id'             => $this->investor->id,
            'periode_ke'          => 2,
            'periode_awal'        => '2026-02-01',
            'periode_akhir'       => '2026-02-28',
            'jatuh_tempo'         => '2026-03-01',
            'profit_usd'          => 150,
            'fee_persen'          => 30,
            'fee_usd'             => 45,
            'wd_usd'              => 60,
            'wd_idr'              => 960000,
            'kurs_riil'           => 16000,
            'fee_idr'             => 720000,
            'bukti_myfxbook_path' => 'myfx2.jpg',
            'bukti_wd_path'       => 'wd2.jpg',
            'status'              => 'lunas',
        ]);

        $response = $this->actingAs($this->investor)->get(route('member.investments.index', [
            'account_id' => $akun->id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('summary.history', 2)
            ->where('summary.all_time_profit_nominal', fn ($v) => (float) $v === 250.0)
            ->where('summary.current_balance', fn ($v) => (float) $v === 1250.0)
        );
    }

    public function test_rejected_account_disappears_from_list_and_resubmission_requires_agreement(): void
    {
        // 1. Akun pertama ditolak
        $rej = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'REJ-111',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'rejected',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/demo/rej',
        ]);

        // Halaman portofolio: akun ditolak hilang dari list (accounts kosong) & has_existing = false
        $response = $this->actingAs($this->investor)->get(route('member.investments.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('accounts', 0)
            ->where('has_existing', false)
        );

        // Pengajuan ulang tanpa dokumen perjanjian HARUS gagal validasi
        $payloadWithoutDoc = [
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => 'NEW-222',
            'modal_riil'            => 150.00,
            'tanggal_mulai_trading' => '2026-02-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '12345',
            'atas_nama_rekening'    => 'Investor Test',
            'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/new222',
        ];

        $this->actingAs($this->investor)
            ->post(route('member.investments.store'), $payloadWithoutDoc)
            ->assertSessionHasErrors('dokumen_perjanjian');

        // Pengajuan ulang dengan dokumen perjanjian berhasil
        $file = UploadedFile::fake()->create('surat_perjanjian_baru.pdf', 500, 'application/pdf');
        $payloadWithDoc = array_merge($payloadWithoutDoc, ['dokumen_perjanjian' => $file]);

        $this->actingAs($this->investor)
            ->post(route('member.investments.store'), $payloadWithDoc)
            ->assertRedirect(route('member.investments.index'));

        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'nomor_akun_trading' => 'NEW-222',
            'status'             => 'pending',
        ]);
    }

    public function test_second_submission_while_first_pending_does_not_require_agreement(): void
    {
        // 1. Akun pertama pending
        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');
        $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_1',
                'nomor_akun_trading'    => 'PENDING-1',
                'modal_riil'            => 100.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345',
                'atas_nama_rekening'    => 'Investor Test',
                'dokumen_perjanjian'    => $file,
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/pend1',
            ])
            ->assertRedirect(route('member.investments.index'));

        // Portofolio sekarang memiliki 1 akun pending, has_existing = true
        $response = $this->actingAs($this->investor)->get(route('member.investments.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('accounts', 1)
            ->where('has_existing', true)
        );

        // Pengajuan akun kedua sekaligus tanpa dokumen perjanjian HARUS BERHASIL
        $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_2',
                'nomor_akun_trading'    => 'PENDING-2',
                'modal_riil'            => 5000.00,
                'tanggal_mulai_trading' => '2026-02-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '12345',
                'atas_nama_rekening'    => 'Investor Test',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/pend2',
            ])
            ->assertRedirect(route('member.investments.index'));

        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'nomor_akun_trading' => 'PENDING-2',
            'status'             => 'pending',
        ]);
    }

    public function test_investor_can_update_myfxbook_url(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-UPDATE-MFB',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/old/portfolio/111',
        ]);

        $response = $this->actingAs($this->investor)
            ->put(route('member.investments.myfxbook.update', $akun->id), [
                'myfxbook_url' => 'https://www.myfxbook.com/members/new/portfolio/999',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('akun_copytrade', [
            'id'           => $akun->id,
            'myfxbook_url' => 'https://www.myfxbook.com/members/new/portfolio/999',
        ]);
    }

    public function test_investor_cannot_update_myfxbook_url_with_invalid_domain(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-UPDATE-MFB2',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/old/portfolio/111',
        ]);

        $response = $this->actingAs($this->investor)
            ->put(route('member.investments.myfxbook.update', $akun->id), [
                'myfxbook_url' => 'https://google.com/malicious',
            ]);

        $response->assertSessionHasErrors('myfxbook_url');
    }

    public function test_investor_can_auto_save_onboarding_step_and_detect_tier(): void
    {
        $response = $this->actingAs($this->investor)
            ->post(route('member.investments.onboarding.update'), [
                'modal_usd'       => 7500.0,
                'completed_steps' => ['step_1', 'step_2'],
                'draft_data'      => [
                    'nomor_akun_trading' => 'HFM-991188',
                    'server_broker'      => 'HFMarkets-Live Server 2',
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('investor_onboarding_steps', [
            'user_id'   => $this->investor->id,
            'tier'      => 'tier_2',
            'modal_usd' => 7500.0,
            'status'    => 'in_progress',
        ]);
    }

    public function test_tier_2_copytrade_registration_uses_hfm_broker(): void
    {
        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_2',
                'nomor_akun_trading'    => 'HFM-TIER2-001',
                'modal_riil'            => 10000.00,
                'tanggal_mulai_trading' => '2026-03-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '987654321',
                'atas_nama_rekening'    => 'Investor Tier 2',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/account/2',
                'dokumen_perjanjian'    => $file,
            ]);

        $response->assertRedirect(route('member.investments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_2',
            'broker'             => 'HFM', // Tier 2 kini menggunakan broker HFM
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => 'HFM-TIER2-001',
        ]);
    }

    public function test_investor_can_reset_onboarding_step(): void
    {
        $this->actingAs($this->investor)
            ->post(route('member.investments.onboarding.update'), [
                'modal_usd'       => 2000.0,
                'completed_steps' => ['step_1'],
            ]);

        $this->assertDatabaseHas('investor_onboarding_steps', [
            'user_id' => $this->investor->id,
        ]);

        $resetResponse = $this->actingAs($this->investor)
            ->post(route('member.investments.onboarding.reset'));

        $resetResponse->assertRedirect();
        $resetResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('investor_onboarding_steps', [
            'user_id' => $this->investor->id,
        ]);
    }

    public function test_registering_account_marks_onboarding_step_as_submitted(): void
    {
        $this->actingAs($this->investor)
            ->post(route('member.investments.onboarding.update'), [
                'modal_usd'       => 3000.0,
                'completed_steps' => ['step_1', 'step_2', 'step_3', 'step_4', 'step_5', 'step_6'],
            ]);

        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');

        $this->actingAs($this->investor)
            ->post(route('member.investments.store'), [
                'tier_investasi'        => 'tier_1',
                'nomor_akun_trading'    => 'HFM-COMPLETE-01',
                'modal_riil'            => 3000.00,
                'tanggal_mulai_trading' => '2026-03-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '987654321',
                'atas_nama_rekening'    => 'Investor Flow Test',
                'myfxbook_url'          => 'https://www.myfxbook.com/members/demo/account/3',
                'dokumen_perjanjian'    => $file,
            ]);

        $this->assertDatabaseHas('investor_onboarding_steps', [
            'user_id' => $this->investor->id,
            'status'  => 'submitted',
        ]);
    }
}
