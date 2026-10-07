<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\User;
use App\Services\EducationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestorFinancialTest extends TestCase
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

    public function test_education_service_bep_calculation_accurately_tracks_profit_and_wd(): void
    {
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-100235',
            'modal_riil'         => 500.00,
            'tanggal_mulai'      => now()->subMonths(3)->toDateString(),
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '987654321',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'copy_started_at'    => now()->subMonths(3),
        ]);

        $service = app(EducationService::class);
        $summary = $service->getInvestorBepSummary($this->investor);

        $this->assertTrue($summary['has_active_copytrade']);
        $this->assertEquals(500.00, $summary['initial_capital']);
        $this->assertGreaterThan(0, $summary['total_withdrawn']);
        $this->assertGreaterThan(0, $summary['total_compounded']);
        $this->assertEquals($summary['total_withdrawn'] + $summary['total_compounded'], $summary['total_profit_accumulated']);
        $this->assertGreaterThan(0, $summary['bep_progress_pct']);
        $this->assertLessThanOrEqual(100, $summary['bep_progress_pct']);
        $this->assertGreaterThan(0, $summary['months_to_bep']);
    }

    public function test_investor_can_access_calculator_page(): void
    {
        $response = $this->actingAs($this->investor)
            ->get(route('investor.education.calculator'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Education/Calculator')
            ->has('presets')
            ->where('presets.default_initial_capital', 500)
            ->where('presets.default_monthly_profit_rate', 5)
        );
    }

    public function test_investor_can_record_monthly_balance_with_automatic_profit_calculation(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-778899',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '11223344',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'copy_started_at'    => '2026-01-01',
        ]);

        // Input 1: Tanggal 1 Februari 2026 -> Saldo $105 (keuntungan dari modal awal $100 adalah $5 / 5%)
        $response1 = $this->actingAs($this->investor)
            ->post(route('investor.copytrade.balance.store'), [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => '2026-02-01',
                'balance'            => 105.00,
                'catatan'            => 'Bulan 1 profit normal',
            ]);

        $response1->assertRedirect();
        $this->assertDatabaseHas('riwayat_balance_copytrade', [
            'akun_copytrade_id'    => $akun->id,
            'balance'              => 105.00,
            'keuntungan_nominal'   => 5.00,
            'keuntungan_persen'    => 5.00,
            'keuntungan_kumulatif' => 5.00,
        ]);
        $record1 = RiwayatBalanceCopytrade::where('akun_copytrade_id', $akun->id)->first();
        $this->assertNotNull($record1);
        $this->assertEquals('2026-02-01', $record1->tanggal_pencatatan->format('Y-m-d'));

        // Input 2: Tanggal 1 Maret 2026 -> Saldo $110.25 (keuntungan dari bulan lalu $105 adalah $5.25 / 5%)
        $response2 = $this->actingAs($this->investor)
            ->post(route('investor.copytrade.balance.store'), [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => '2026-03-01',
                'balance'            => 110.25,
                'catatan'            => 'Bulan 2 profit 5%',
            ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('riwayat_balance_copytrade', [
            'akun_copytrade_id'            => $akun->id,
            'balance'                      => 110.25,
            'keuntungan_nominal'           => 5.25,
            'keuntungan_persen'            => 5.00,
            'keuntungan_kumulatif'         => 10.25,
            'pertumbuhan_kumulatif_persen' => 10.25,
        ]);
        $record2 = RiwayatBalanceCopytrade::where('akun_copytrade_id', $akun->id)->orderByDesc('tanggal_pencatatan')->first();
        $this->assertNotNull($record2);
        $this->assertEquals('2026-03-01', $record2->tanggal_pencatatan->format('Y-m-d'));
    }

    public function test_investor_can_view_balance_history_and_filter_by_periods(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-554433',
            'modal_riil'         => 200.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '55443322',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'copy_started_at'    => '2026-01-01',
        ]);

        // Verifikasi rute legasi copytrade.balance.index mengarah ke investments
        $legacyResponse = $this->actingAs($this->investor)
            ->get(route('investor.copytrade.balance.index'));
        $legacyResponse->assertRedirect(route('investor.investments.index'));

        // Akses halaman terpadu dengan filter periode bebas: 6 bulan terakhir
        $response = $this->actingAs($this->investor)
            ->get(route('investor.investments.index', [
                'account_id' => $akun->id,
                'period'     => '6m',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Investments/Index')
            ->has('accounts', 1)
            ->where('selected_account_id', $akun->id)
            ->has('summary')
            ->where('summary.period', '6m')
            ->where('summary.period_label', '6 Bulan Terakhir')
            ->has('summary.period_profit_nominal')
            ->has('summary.period_growth_pct')
        );

        // Akses dengan filter periode 1, 2, 3, 4 tahun
        foreach (['1y' => '1 Tahun Terakhir', '2y' => '2 Tahun Terakhir', '3y' => '3 Tahun Terakhir', '4y' => '4 Tahun Terakhir'] as $period => $label) {
            $resp = $this->actingAs($this->investor)
                ->get(route('investor.investments.index', [
                    'account_id' => $akun->id,
                    'period'     => $period,
                ]));

            $resp->assertStatus(200);
            $resp->assertInertia(fn ($page) => $page
                ->where('summary.period', $period)
                ->where('summary.period_label', $label)
            );
        }
    }

    public function test_investor_can_delete_balance_record(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-998877',
            'modal_riil'         => 100.00,
            'tanggal_mulai'      => '2026-01-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '99887766',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'active',
            'copy_started_at'    => '2026-01-01',
        ]);

        $this->actingAs($this->investor)
            ->post(route('investor.copytrade.balance.store'), [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => '2026-02-01',
                'balance'            => 105.00,
            ]);

        $record = \App\Models\RiwayatBalanceCopytrade::first();
        $this->assertNotNull($record);

        $deleteResponse = $this->actingAs($this->investor)
            ->delete(route('investor.copytrade.balance.destroy', $record->id));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('riwayat_balance_copytrade', [
            'id' => $record->id,
        ]);
    }
}
