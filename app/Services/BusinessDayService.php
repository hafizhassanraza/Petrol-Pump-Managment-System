<?php

namespace App\Services;

use App\Models\Shift;
use Carbon\Carbon;

class BusinessDayService
{
    /** Station operating day starts at 9:00 AM */
    public const SHIFT_START_HOUR = 9;

    public const SHIFT_END_HOUR = 9;

    /**
     * Calendar date of the current business day (9 AM boundary).
     * Before 9 AM → still previous business day.
     */
    public static function currentBusinessDate(): Carbon
    {
        $now = now();

        if ($now->hour < self::SHIFT_START_HOUR) {
            return $now->copy()->subDay()->startOfDay();
        }

        return $now->copy()->startOfDay();
    }

    /**
     * Calendar date of the business day that contains a datetime (9 AM boundary).
     */
    public static function toBusinessDate(Carbon|string $datetime): Carbon
    {
        $dt = Carbon::parse($datetime);

        if ($dt->hour < self::SHIFT_START_HOUR) {
            return $dt->copy()->subDay()->startOfDay();
        }

        return $dt->copy()->startOfDay();
    }

    /**
     * Datetime range for one business day: 09:00:00 → next day 08:59:59.
     */
    public static function businessDayBounds(Carbon|string $businessDate): array
    {
        $date = Carbon::parse($businessDate)->startOfDay();
        $from = $date->copy()->setTime(self::SHIFT_START_HOUR, 0, 0);
        $to = $from->copy()->addDay()->subSecond();

        return [$from, $to];
    }

    public static function defaultShift(): Shift
    {
        return Shift::query()->firstOrCreate(
            ['name' => 'Business Day (9 AM – 9 AM)'],
            [
                'start_time' => '09:00:00',
                'end_time' => '09:00:00',
            ]
        );
    }

    public static function defaultShiftId(): int
    {
        return self::defaultShift()->id;
    }
}
