<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Carbon;

enum AccessDuration: string
{
    case FifteenMinutes = '15m';
    case ThirtyMinutes = '30m';
    case OneHour = '1h';
    case TwentyFourHours = '24h';

    public function toSeconds(): int
    {
        return match ($this) {
            self::FifteenMinutes => 900,
            self::ThirtyMinutes => 1800,
            self::OneHour => 3600,
            self::TwentyFourHours => 86400,
        };
    }

    public function toCarbon(): Carbon
    {
        return now()->addSeconds($this->toSeconds());
    }
}
