<?php

namespace Tests\Unit;

use App\Support\ProfitPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ProfitPeriodTest extends TestCase
{
    public function test_first_period_end_for_day_1_to_15(): void
    {
        // 3 Agustus -> sampai 31 Agustus
        $mulai = Carbon::parse('2026-08-03');
        $end = ProfitPeriod::firstPeriodEnd($mulai);
        $this->assertEquals('2026-08-31', $end->toDateString());

        // 15 Januari -> sampai 31 Januari
        $mulai15 = Carbon::parse('2026-01-15');
        $end15 = ProfitPeriod::firstPeriodEnd($mulai15);
        $this->assertEquals('2026-01-31', $end15->toDateString());
    }

    public function test_first_period_end_for_day_16_to_31(): void
    {
        // 17 Juni -> sampai 31 Juli (akhir bulan berikutnya)
        $mulai = Carbon::parse('2026-06-17');
        $end = ProfitPeriod::firstPeriodEnd($mulai);
        $this->assertEquals('2026-07-31', $end->toDateString());

        // 31 Agustus -> sampai 30 September
        $mulai31 = Carbon::parse('2026-08-31');
        $end31 = ProfitPeriod::firstPeriodEnd($mulai31);
        $this->assertEquals('2026-09-30', $end31->toDateString());
    }

    public function test_first_period_end_in_february(): void
    {
        // Kabisat 2024: 10 Feb -> 29 Feb
        $mulaiKabisat = Carbon::parse('2024-02-10');
        $this->assertEquals('2024-02-29', ProfitPeriod::firstPeriodEnd($mulaiKabisat)->toDateString());

        // Kabisat 2024: 16 Feb -> 31 Mar
        $mulaiKabisat16 = Carbon::parse('2024-02-16');
        $this->assertEquals('2024-03-31', ProfitPeriod::firstPeriodEnd($mulaiKabisat16)->toDateString());

        // Non-kabisat 2025: 10 Feb -> 28 Feb
        $mulaiNonKabisat = Carbon::parse('2025-02-10');
        $this->assertEquals('2025-02-28', ProfitPeriod::firstPeriodEnd($mulaiNonKabisat)->toDateString());
    }

    public function test_open_report_date(): void
    {
        $end = Carbon::parse('2026-08-31');
        $open = ProfitPeriod::openReportDate($end);
        $this->assertEquals('2026-09-01', $open->toDateString());

        $endJul = Carbon::parse('2026-07-31');
        $this->assertEquals('2026-08-01', ProfitPeriod::openReportDate($endJul)->toDateString());
    }

    public function test_periods_generation(): void
    {
        // Mulai 3 Agustus 2026, today = 5 November 2026
        // P1: 2026-08-03 s/d 2026-08-31 (buka 2026-09-01)
        // P2: 2026-09-01 s/d 2026-09-30 (buka 2026-10-01)
        // P3: 2026-10-01 s/d 2026-10-31 (buka 2026-11-01)
        $mulai = Carbon::parse('2026-08-03');
        $today = Carbon::parse('2026-11-05');

        $periods = ProfitPeriod::periods($mulai, $today);
        $this->assertCount(3, $periods);

        $this->assertEquals(1, $periods[0]['ke']);
        $this->assertEquals('2026-08-03', $periods[0]['awal']->toDateString());
        $this->assertEquals('2026-08-31', $periods[0]['akhir']->toDateString());
        $this->assertEquals('2026-09-01', $periods[0]['jatuh_tempo']->toDateString());

        $this->assertEquals(2, $periods[1]['ke']);
        $this->assertEquals('2026-09-01', $periods[1]['awal']->toDateString());
        $this->assertEquals('2026-09-30', $periods[1]['akhir']->toDateString());
        $this->assertEquals('2026-10-01', $periods[1]['jatuh_tempo']->toDateString());

        $this->assertEquals(3, $periods[2]['ke']);
        $this->assertEquals('2026-10-01', $periods[2]['awal']->toDateString());
        $this->assertEquals('2026-10-31', $periods[2]['akhir']->toDateString());
        $this->assertEquals('2026-11-01', $periods[2]['jatuh_tempo']->toDateString());
    }
}
