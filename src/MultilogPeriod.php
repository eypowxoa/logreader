<?php

declare(strict_types=1);

namespace LogReader;

enum MultilogPeriod
{
    case DAY;
    case HOUR;
    case MINUTE;
    case MONTH;
    case WEEK;

    public function getIntervalString(): string
    {
        return match ($this) {
            MultilogPeriod::MINUTE => 'PT1M',
            MultilogPeriod::HOUR => 'PT1H',
            MultilogPeriod::DAY => 'P1D',
            MultilogPeriod::WEEK => 'P1W',
            MultilogPeriod::MONTH => 'P1M',
        };
    }
}
