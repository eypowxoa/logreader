<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\Record;
use PHPUnit\Framework\TestCase;
use Carbon\CarbonImmutable;

/**
 * @internal
 */
final class RecordTest extends TestCase
{
    public function testConstruct(): void
    {
        $date = CarbonImmutable::parse('2001-02-03 04:05:06');
        $record = new Record($date, 7, 8, '9');
        $this->assertSame($date, $record->date);
        $this->assertSame(8, $record->length);
        $this->assertSame(7, $record->position);
        $this->assertSame(15, $record->border);
        $this->assertSame('9', $record->record);
    }

    public function testToString(): void
    {
        $record = new Record(CarbonImmutable::parse('2001-02-03 04:05:06'), 7, 8, '9');
        $this->assertSame('2001-02-03 04:05:06 9', (string) $record);
    }
}
