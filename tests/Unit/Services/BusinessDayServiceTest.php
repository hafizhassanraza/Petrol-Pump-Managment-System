<?php

namespace Tests\Unit\Services;

use App\Models\Shift;
use App\Services\BusinessDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_business_date_before_nine_am_is_previous_day(): void
    {
        $this->travelTo(now()->setTime(8, 30, 0));

        $date = BusinessDayService::currentBusinessDate();

        $this->assertEquals(now()->subDay()->toDateString(), $date->toDateString());
    }

    public function test_current_business_date_at_nine_am_is_today(): void
    {
        $this->travelTo(now()->setTime(9, 0, 0));

        $date = BusinessDayService::currentBusinessDate();

        $this->assertEquals(now()->toDateString(), $date->toDateString());
    }

    public function test_business_day_bounds_span_nine_am_to_next_morning(): void
    {
        [$from, $to] = BusinessDayService::businessDayBounds('2026-06-03');

        $this->assertEquals('2026-06-03 09:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-06-04 08:59:59', $to->format('Y-m-d H:i:s'));
    }

    public function test_default_shift_is_created_when_missing(): void
    {
        $this->assertDatabaseCount('shifts', 0);

        $shift = BusinessDayService::defaultShift();

        $this->assertInstanceOf(Shift::class, $shift);
        $this->assertDatabaseCount('shifts', 1);
        $this->assertSame(BusinessDayService::defaultShiftId(), $shift->id);
    }
}
