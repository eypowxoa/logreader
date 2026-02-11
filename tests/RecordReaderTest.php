<?php

declare(strict_types=1);

namespace LogParserTests;

use Carbon\CarbonImmutable;
use LogParser\Record;
use LogParser\RecordReader;
use LogParser\RecordWrongException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RecordReaderTest extends TestCase
{
    /**
     * @param array<array{int,null|array{int,int,int,string}|string}> $expected
     */
    #[DataProvider('provideReadRecordCases')]
    public function testReadRecord(string $buffer, bool $complete, int $offset, int $position, array $expected): void
    {
        $recordReader = new RecordReader('~(?<day>[\dx]+)~');
        $recordReader->setBuffer($buffer, $complete, $position);
        $recordReader->offset = $offset;

        if (\is_string($expected[0][1])) {
            $this->expectExceptionObject(new RecordWrongException($expected[0][1]));
        }

        $record = $recordReader->readRecord();

        while ($record instanceof Record) {
            $exp = array_shift($expected);
            $this->assertIsArray($exp);
            $this->assertSame($exp[0], $recordReader->offset);
            $this->assertIsArray($exp[1]);
            $this->assertSame($exp[1][0], $record->position);
            $this->assertSame($exp[1][1], $record->length);
            $this->assertSame($exp[1][2], (int) $record->date->format('j'));
            $this->assertSame($exp[1][3], $record->record);
            $record = $recordReader->readRecord();
        }

        $last = array_shift($expected);
        $this->assertIsArray($last);
        $this->assertSame($last[0], $recordReader->offset);
        $this->assertIsNotArray($last[1]);
        $this->assertEmpty($expected);
    }

    /**
     * @return iterable<string,array{string,bool,int,int,array{int,null|array{int,int,int,string}|string}[]}>
     */
    public static function provideReadRecordCases(): iterable
    {
        yield 'should find nothing in empty string' => ['', false, 0, 0, [[0, null]]];

        yield 'should find nothing in space string' => ["\n", false, 0, 0, [[1, null]]];

        yield 'should fail for wrong date' => ["x\n", false, 0, 0, [[1, 'Wrong day x at 0, expected integer from 1 to 31']]];

        yield 'should find nothing if no next record' => ["2\n", false, 0, 0, [[2, null]]];

        yield 'should find record with no body' => ["2\n3", false, 0, 0, [[2, [0, 2, 2, '']], [2, null]]];

        yield 'should trim record body' => ["2 a \n3", false, 0, 0, [[5, [0, 5, 2, 'a']], [5, null]]];

        yield 'should find all complete records' => ["2 a \n3 b \n4", false, 0, 0, [[5, [0, 5, 2, 'a']], [10, [5, 5, 3, 'b']], [10, null]]];

        yield 'should skip trash at start' => ["a 2 \n3 b \n4", false, 0, 0, [[10, [5, 5, 3, 'b']], [10, null]]];

        yield 'should work with utf8' => ["2 ё \n3 ё \n4", false, 0, 0, [[6, [0, 6, 2, 'ё']], [12, [6, 6, 3, 'ё']], [12, null]]];

        yield 'should work with multiline records' => ["2 ё \n\na\n \n3 ё \n4", false, 0, 0, [[11, [0, 11, 2, "ё \n\na"]], [17, [11, 6, 3, 'ё']], [17, null]]];

        yield 'should use offset' => ["2 a \n3 b \n4", false, 5, 0, [[10, [5, 5, 3, 'b']], [10, null]]];

        yield 'should add buffer position' => ["2 a \n3 b \n4", false, 0, 1, [[5, [1, 5, 2, 'a']], [10, [6, 5, 3, 'b']], [10, null]]];

        yield 'should return last if buffer complete' => ["2 a \n3 b \na", true, 0, 1, [[5, [1, 5, 2, 'a']], [11, [6, 6, 3, "b \na"]], [11, null]]];
    }

    public function testReadRecordShouldUseTimezone(): void
    {
        $recordReader = new RecordReader('~(?<year>\d{4})\-(?<month>\d{2})\-(?<day>\d{2}) (?<hour>\d{2}):(?<minute>\d{2}):(?<second>\d{2})~', new \DateTimeZone('Africa/Tunis'));
        $recordReader->setBuffer('2001-01-01 12:00:00', true);

        $record = $recordReader->readRecord();

        $this->assertInstanceOf(Record::class, $record);
        $this->assertSame(CarbonImmutable::parse('2001-01-01 12:00:00', 'Africa/Tunis')->getTimestamp(), $record->date->getTimestamp());
    }
}
