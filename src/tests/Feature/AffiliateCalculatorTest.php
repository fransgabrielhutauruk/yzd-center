<?php

namespace Tests\Feature;

use App\Models\KlaimKomisiItem;
use App\Models\PengaturanMasterFee;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AffiliateCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put(ExchangeRateService::CACHE_KEY, [
            'rate'       => 16500.0,
            'updated_at' => now()->toIso8601String(),
            'date'       => now()->toDateString(),
            'is_live'    => true,
            'source'     => 'test_frankfurter',
        ], 3600);

        PengaturanMasterFee::current()->update([
            'fee_persen'     => 30,
            'wd_maks_persen' => 60,
        ]);

        $this->member = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('member.affiliates.calculator'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_is_forbidden_to_access_member_calculator(): void
    {
        $response = $this->actingAs($this->admin)->get(route('member.affiliates.calculator'));

        $response->assertForbidden();
    }

    public function test_inactive_member_is_redirected_by_ensure_active_middleware(): void
    {
        $inactiveMember = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveMember)->get(route('member.affiliates.calculator'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['login']);
    }

    public function test_active_member_can_access_calculator_page_with_correct_props(): void
    {
        $response = $this->actingAs($this->member)->get(route('member.affiliates.calculator'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Affiliates/Calculator')
            ->has('tiers', 4)
            ->where('tiers.0.tier_level', 1)
            ->where('tiers.0.persentase_tier', 50)
            ->where('tiers.1.tier_level', 2)
            ->where('tiers.1.persentase_tier', 40)
            ->where('tiers.2.tier_level', 3)
            ->where('tiers.2.persentase_tier', 30)
            ->where('tiers.3.tier_level', 4)
            ->where('tiers.3.persentase_tier', 20)
            ->has('presets', fn ($presets) => $presets
                ->where('default_profit_rate', fn ($val) => (float) $val === 5.0)
                ->where('default_modal_idr', fn ($val) => (float) $val === 100_000_000.0)
                ->where('wd_pct', fn ($val) => (float) $val === 60.0)
                ->where('master_fee_pct', fn ($val) => (float) $val === 30.0)
                ->where('months', 3)
                ->where('max_investors', null)
                ->where('default_exchange_rate', fn ($val) => (float) $val === 16500.0)
                ->has('exchange_rate_meta')
            )
        );
    }

    public function test_tier_definitions_match_determine_tier_on_boundary_values(): void
    {
        // Nilai batas: 99.999.999 -> Tier 4 (20%)
        $t99m = KlaimKomisiItem::determineTier(99_999_999);
        $this->assertSame(4, $t99m['tier_level']);
        $this->assertSame(20, $t99m['persentase_tier']);

        // Nilai batas: 100.000.000 -> Tier 3 (30%)
        $t100m = KlaimKomisiItem::determineTier(100_000_000);
        $this->assertSame(3, $t100m['tier_level']);
        $this->assertSame(30, $t100m['persentase_tier']);

        // Nilai batas: 999.999.999 -> Tier 3 (30%)
        $t999m = KlaimKomisiItem::determineTier(999_999_999);
        $this->assertSame(3, $t999m['tier_level']);
        $this->assertSame(30, $t999m['persentase_tier']);

        // Nilai batas: 1.000.000.000 -> Tier 2 (40%)
        $t1b = KlaimKomisiItem::determineTier(1_000_000_000);
        $this->assertSame(2, $t1b['tier_level']);
        $this->assertSame(40, $t1b['persentase_tier']);

        // Nilai batas: 10.000.000.000 -> Tier 2 (40%) (batas tegas > Rp10 M untuk Tier 1)
        $t10b = KlaimKomisiItem::determineTier(10_000_000_000);
        $this->assertSame(2, $t10b['tier_level']);
        $this->assertSame(40, $t10b['persentase_tier']);

        // Nilai batas: 10.000.000.001 -> Tier 1 (50%)
        $t10b1 = KlaimKomisiItem::determineTier(10_000_000_001);
        $this->assertSame(1, $t10b1['tier_level']);
        $this->assertSame(50, $t10b1['persentase_tier']);
    }

    public function test_tier_definitions_contains_expected_keys(): void
    {
        $tiers = KlaimKomisiItem::tierDefinitions();

        $this->assertCount(4, $tiers);

        foreach ($tiers as $tier) {
            $this->assertArrayHasKey('tier_level', $tier);
            $this->assertArrayHasKey('persentase_tier', $tier);
            $this->assertArrayHasKey('label', $tier);
            $this->assertArrayHasKey('name', $tier);
            $this->assertArrayHasKey('min_idr', $tier);
            $this->assertArrayHasKey('max_idr', $tier);
            $this->assertArrayHasKey('range_label', $tier);
            $this->assertArrayHasKey('is_strict_greater', $tier);
        }
    }
}
