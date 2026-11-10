<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use App\Models\User;
use App\Services\EducationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvestorFinancialTest extends TestCase
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
            'myfxbook_url'       => 'https://www.myfxbook.com/members/demo/bep',
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
            ->get(route('member.education.calculator'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Education/Calculator')
            ->has('presets')
            ->where('presets.default_initial_capital', 500)
            ->where('presets.default_monthly_profit_rate', 5)
        );
    }
}
