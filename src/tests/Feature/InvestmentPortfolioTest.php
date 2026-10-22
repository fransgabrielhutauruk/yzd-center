<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use App\Models\RiwayatBalanceCopytrade;
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
            ]);

        $response->assertRedirect(route('member.investments.index'));
        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'nomor_akun_trading' => 'MT4-889900',
            'modal_riil'         => 150.00,
            'status'             => 'pending',
        ]);
    }

    public function test_investor_can_record_monthly_balance_and_calculates_profit(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-112233',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
        ]);

        $response = $this->actingAs($this->investor)
            ->post(route('member.investments.balance.store'), [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => '2026-02-01',
                'balance'            => 108.00,
                'catatan'            => 'Bulan Februari profit $8',
            ]);

        $response->assertRedirect(route('member.investments.index', ['account_id' => $akun->id]));
        $this->assertDatabaseHas('riwayat_balance_copytrade', [
            'akun_copytrade_id'    => $akun->id,
            'balance'              => 108.00,
            'keuntungan_nominal'   => 8.00,
            'keuntungan_persen'    => 8.00,
            'keuntungan_kumulatif' => 8.00,
        ]);
    }

    public function test_consolidated_summary_accurately_aggregates_multiple_accounts(): void
    {
        // Akun 1: Modal $100, Saldo akhir $120 (laba $20)
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
        ]);
        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun1->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => '2026-02-01',
            'balance'                      => 120.00,
            'keuntungan_nominal'           => 20.00,
            'keuntungan_persen'            => 20.00,
            'keuntungan_kumulatif'         => 20.00,
            'pertumbuhan_kumulatif_persen' => 20.00,
        ]);

        // Akun 2 (Tier 3 Managed VPS): Modal $300,000, Saldo akhir $315,000 (laba $15,000)
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
        ]);
        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun2->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => '2026-02-01',
            'balance'                      => 315000.00,
            'keuntungan_nominal'           => 15000.00,
            'keuntungan_persen'            => 5.00,
            'keuntungan_kumulatif'         => 15000.00,
            'pertumbuhan_kumulatif_persen' => 5.00,
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

    public function test_investor_can_delete_balance_record(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-DEL1',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
        ]);

        $rec = RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'  => $akun->id,
            'user_id'            => $this->investor->id,
            'tanggal_pencatatan' => '2026-02-01',
            'balance'            => 110.00,
            'keuntungan_nominal' => 10.00,
            'keuntungan_persen'  => 10.00,
        ]);

        $response = $this->actingAs($this->investor)
            ->delete(route('member.investments.balance.destroy', $rec->id));

        $response->assertRedirect(route('member.investments.index', ['account_id' => $akun->id]));
        $this->assertDatabaseMissing('riwayat_balance_copytrade', [
            'id' => $rec->id,
        ]);
    }

    public function test_legacy_copytrade_routes_redirect_to_investments_page(): void
    {
        $resp1 = $this->actingAs($this->investor)
            ->get(route('member.copytrade.index'));
        $resp1->assertRedirect(route('member.investments.index'));

        $resp2 = $this->actingAs($this->investor)
            ->get(route('member.copytrade.balance.index'));
        $resp2->assertRedirect(route('member.investments.index'));
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

    public function test_investor_cannot_record_balance_for_pending_account(): void
    {
        $pendingAccount = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-PENDING-2',
            'modal_riil'         => 100.00,
            'status'             => 'pending',
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '123',
            'atas_nama_rekening' => 'Investor',
        ]);

        $response = $this->actingAs($this->investor)
            ->post(route('member.investments.balance.store'), [
                'akun_copytrade_id'  => $pendingAccount->id,
                'tanggal_pencatatan' => '2026-02-01',
                'balance'            => 105.00,
            ]);

        // Harus gagal validasi/otorisasi
        $response->assertSessionHasErrors('akun_copytrade_id');
        $this->assertDatabaseMissing('riwayat_balance_copytrade', [
            'akun_copytrade_id' => $pendingAccount->id,
        ]);
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
                // nama_bank, nomor_rekening, atas_nama_rekening, dokumen_perjanjian tidak dikirim
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

    public function test_investor_can_filter_performance_using_custom_month_and_year(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => '2026-05-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
        ]);

        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => '2026-05-01',
            'balance'                      => 1100,
            'keuntungan_nominal'           => 100,
            'keuntungan_persen'            => 10,
            'keuntungan_kumulatif'         => 100,
            'pertumbuhan_kumulatif_persen' => 10,
        ]);

        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => '2026-06-01',
            'balance'                      => 1250,
            'keuntungan_nominal'           => 150,
            'keuntungan_persen'            => 13.64,
            'keuntungan_kumulatif'         => 250,
            'pertumbuhan_kumulatif_persen' => 25,
        ]);

        $response = $this->actingAs($this->investor)->get(route('member.investments.index', [
            'account_id' => $akun->id,
            'period'     => 'custom',
            'start_date' => '2026-05',
            'end_date'   => '2026-06',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('summary.period_label', 'Mei 2026 s/d Juni 2026')
            ->where('summary.start_date', '2026-05-01')
            ->where('summary.end_date', '2026-06-30')
            ->has('summary.history', 2)
            ->where('summary.months_active', fn ($m) => $m >= 1)
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
            ])
            ->assertRedirect(route('member.investments.index'));

        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'nomor_akun_trading' => 'PENDING-2',
            'status'             => 'pending',
        ]);
    }

    public function test_investor_can_update_balance_record_including_changing_date_without_creating_new_row(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-UPDATE-1',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
        ]);

        $record = RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => '2026-02-01',
            'balance'                      => 110.00,
            'keuntungan_nominal'           => 10.00,
            'keuntungan_persen'            => 10.00,
            'keuntungan_kumulatif'         => 10.00,
            'pertumbuhan_kumulatif_persen' => 10.00,
            'catatan'                      => 'Catatan lama',
        ]);

        $this->assertEquals(1, RiwayatBalanceCopytrade::where('akun_copytrade_id', $akun->id)->count());

        // Update tanggal menjadi 2026-02-15 dan saldo 115.00
        $response = $this->actingAs($this->investor)
            ->put(route('member.investments.balance.update', $record->id), [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => '2026-02-15',
                'balance'            => 115.00,
                'catatan'            => 'Catatan diperbarui',
            ]);

        $response->assertRedirect(route('member.investments.index', ['account_id' => $akun->id]));
        $response->assertSessionHas('success');

        // Pastikan TIDAK membuat baris baru (tetap tepat 1 record)
        $this->assertEquals(1, RiwayatBalanceCopytrade::where('akun_copytrade_id', $akun->id)->count());

        $record->refresh();
        $this->assertEquals('2026-02-15', $record->tanggal_pencatatan->format('Y-m-d'));
        $this->assertEquals(115.00, (float) $record->balance);
        $this->assertEquals(15.00, (float) $record->keuntungan_nominal);
        $this->assertEquals(15.00, (float) $record->keuntungan_persen);
        $this->assertEquals('Catatan diperbarui', $record->catatan);
    }

    public function test_updating_balance_date_to_an_existing_date_fails_validation(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-COLLISION',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
        ]);

        $record1 = RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => '2026-02-01',
            'balance'                      => 110.00,
            'keuntungan_nominal'           => 10.00,
            'keuntungan_persen'            => 10.00,
            'keuntungan_kumulatif'         => 10.00,
            'pertumbuhan_kumulatif_persen' => 10.00,
        ]);

        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => '2026-03-01',
            'balance'                      => 120.00,
            'keuntungan_nominal'           => 10.00,
            'keuntungan_persen'            => 9.09,
            'keuntungan_kumulatif'         => 20.00,
            'pertumbuhan_kumulatif_persen' => 20.00,
        ]);

        // Coba ubah record1 ke tanggal record2 (2026-03-01)
        $response = $this->actingAs($this->investor)
            ->put(route('member.investments.balance.update', $record1->id), [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => '2026-03-01',
                'balance'            => 112.00,
            ]);

        $response->assertSessionHasErrors('tanggal_pencatatan');
    }

    public function test_investor_cannot_update_other_investor_balance_record(): void
    {
        $otherUser = \App\Models\User::factory()->create(['role' => 'investor', 'status' => 'active']);
        $otherAkun = AkunCopytrade::create([
            'user_id'            => $otherUser->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-OTHER',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345',
            'atas_nama_rekening' => 'Other Investor',
            'status'             => 'active',
        ]);

        $otherRecord = RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $otherAkun->id,
            'user_id'                      => $otherUser->id,
            'tanggal_pencatatan'           => '2026-02-01',
            'balance'                      => 110.00,
            'keuntungan_nominal'           => 10.00,
            'keuntungan_persen'            => 10.00,
            'keuntungan_kumulatif'         => 10.00,
            'pertumbuhan_kumulatif_persen' => 10.00,
        ]);

        $response = $this->actingAs($this->investor)
            ->put(route('member.investments.balance.update', $otherRecord->id), [
                'akun_copytrade_id'  => $otherAkun->id,
                'tanggal_pencatatan' => '2026-02-15',
                'balance'            => 115.00,
            ]);

        $response->assertForbidden();
    }
}
