<?php

declare(strict_types=1);

namespace LogParserTests;

use LogParser\Record;
use PHPUnit\Framework\TestCase;
use Carbon\CarbonImmutable;

/**
 * @internal
 */
final class RecordTest extends TestCase
{
    public function testConstruct(): void
    {
        $record = new Record(CarbonImmutable::parse('2001-02-03 04:05:06'), 7, 8, '9');
        $this->assertSame(15, $record->border);
    }

    public function testToString(): void
    {
        $record = new Record(CarbonImmutable::parse('2001-02-03 04:05:06'), 7, 8, '9');
        $this->assertSame('2001-02-03 04:05:06 9', (string) $record);
    }
}
