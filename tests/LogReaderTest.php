<?php

declare(strict_types=1);

namespace LogParserTests;

use Carbon\CarbonImmutable;
use LogParser\FileReaderMemory;
use LogParser\LogReader;
use LogParser\LogWrongException;
use LogParser\Record;
use LogParser\RecordReader;
use LogParser\RecordSearch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LogReaderTest extends TestCase
{
    /**
     * @param string[]|\Throwable $expected
     */
    #[DataProvider('provideReadLogCases')]
    public function testReadLog(?string $file, int $readBufferSize, int $searchBufferSize, int $since, int $until, array|\Throwable $expected): void
    {
        $unreadable = false;

        if (null === $file) {
            $file = '';
            $unreadable = true;
        }

        $fileReaderMemory = new FileReaderMemory($file, $unreadable);
        $recordReader = new RecordReader('~(?<day>[\dx])~');
        $recordSearch = new RecordSearch($fileReaderMemory, $recordReader, $searchBufferSize);
        $logReader = new LogReader($fileReaderMemory, $recordReader, $recordSearch, $readBufferSize);

        $sinceDate = CarbonImmutable::now()->setDate(1, 1, $since)->setTime(0, 0);
        $untilDate = CarbonImmutable::now()->setDate(1, 1, $until)->setTime(0, 0);

        if ($expected instanceof \Throwable) {
            $this->expectExceptionObject($expected);
        }

        /** @var Record[] $result */
        $result = iterator_to_array($logReader->readLog($sinceDate, $untilDate));

        if (\is_array($expected)) {
            $this->assertCount(\count($expected), $result);
            foreach ($expected as $index => $expectedRecord) {
                $this->assertArrayHasKey($index, $result);
                $record = $result[$index];
                $this->assertSame($expectedRecord, \sprintf('%s:%s', $record->date->format('j'), $record->record));
            }
        }
    }

    /**
     * @return iterable<string,array{?string,int,int,int,int,string[]|\Throwable}>
     */
    public static function provideReadLogCases(): iterable
    {
        yield 'should find nothing if empty' => ['', 100, 100, 0, 0, []];

        yield 'should find nothing if record before since' => ['2ё⚽🎲1', 100, 100, 3, 3, []];

        yield 'should find nothing if record after until' => ['4ё⚽🎲4', 100, 100, 3, 3, []];

        yield 'should find single record at exact time' => ['3ё⚽🎲3', 100, 100, 3, 3, ['3:ё⚽🎲3']];

        yield 'should find single record inside time range' => ['3ё⚽🎲3', 100, 100, 2, 4, ['3:ё⚽🎲3']];

        yield 'should find multiple records at exact time' => ["3ё⚽🎲3\n3ё⚽🎲4", 100, 100, 3, 3, ['3:ё⚽🎲3', '3:ё⚽🎲4']];

        yield 'should find multiple records inside time range' => ["3ё⚽🎲3\n3ё⚽🎲4", 100, 100, 2, 4, ['3:ё⚽🎲3', '3:ё⚽🎲4']];

        yield 'should skip records outside time range' => ["2ё⚽🎲2\n3ё⚽🎲3\n4ё⚽🎲4", 100, 100, 3, 3, ['3:ё⚽🎲3']];

        yield 'should find nothing if read buffer too short' => ['3ё⚽🎲3', 10, 100, 3, 3, []];

        yield 'should find record with UTF-8' => ["2ё⚽🎲2\n3ё⚽🎲3\n4ё⚽🎲4", 14, 100, 2, 4, ['2:ё⚽🎲2', '3:ё⚽🎲3', '4:ё⚽🎲4']];

        $error = static fn(string $file, int $position, string $error): LogWrongException => new LogWrongException(\sprintf(
            'Failed to read log %1$s after 0. File %1$s is wrong somewhere after %2$d. %3$s',
            md5($file),
            $position,
            $error,
        ));

        yield 'should fail if date wrong' => ['xё⚽🎲2', 100, 100, 2, 4, $error('xё⚽🎲2', 0, 'Wrong day x at 0, expected integer from 1 to 31')];

        yield 'should fail if UTF-8 wrong' => ["xё⚽\x80🎲2", 100, 100, 2, 4, $error("xё⚽\x80🎲2", 3, 'Not an UTF-8')];

        yield 'should fail if not readable' => [null, 100, 100, 2, 4, $error('', 0, 'Not readable')];
    }
}
