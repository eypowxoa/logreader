<?php

declare(strict_types=1);

namespace LogParserTests;

use Carbon\CarbonImmutable;
use LogParser\FileReaderMemory;
use LogParser\FileWrongException;
use LogParser\Record;
use LogParser\RecordReader;
use LogParser\RecordSearch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RecordSearchTest extends TestCase
{
    public function testConstructShouldFailForNegativeBufferSize(): void
    {
        $fileReaderMemory = new FileReaderMemory('');
        $recordReader = new RecordReader('~a~');
        $this->expectException(\InvalidArgumentException::class);
        new RecordSearch($fileReaderMemory, $recordReader, -1);
    }

    public function testConstructShouldFailForZeroBufferSize(): void
    {
        $fileReaderMemory = new FileReaderMemory('');
        $recordReader = new RecordReader('~a~');
        $this->expectException(\InvalidArgumentException::class);
        new RecordSearch($fileReaderMemory, $recordReader, 0);
    }

    #[DataProvider('provideFindRecordCases')]
    public function testFindRecord(?string $file, int $bufferSize, int $date, bool $since, string|\Throwable|null $expectedRecord): void
    {
        $unreadable = false;

        if (null === $file) {
            $file = '';
            $unreadable = true;
        }

        $fileReaderMemory = new FileReaderMemory($file, $unreadable);
        $recordReader = new RecordReader('~(?<day>[\dx])~');
        $recordSearch = new RecordSearch($fileReaderMemory, $recordReader, $bufferSize);

        if ($expectedRecord instanceof \Throwable) {
            $this->expectExceptionObject($expectedRecord);
        }

        $record = $recordSearch->findRecord(CarbonImmutable::parse(\sprintf('0001-01-%02d 00:00:00', $date)), $since);

        if (\is_string($expectedRecord)) {
            $this->assertInstanceOf(Record::class, $record);
            $this->assertSame($expectedRecord, \sprintf('%s:%s', $record->date->format('j'), $record->record));
        }

        if (null === $expectedRecord) {
            $this->assertNotInstanceOf(Record::class, $record);
        }
    }

    /**
     * @return iterable<string,array{null|string,int,int,bool,null|int|string|\Throwable}>
     */
    public static function provideFindRecordCases(): iterable
    {
        yield 'should return null if empty' => ['', 100, 0, true, null];

        yield 'should return null if no record' => ['a', 100, 0, true, null];

        yield 'should find single record same as search since' => ['2', 100, 2, true, '2:'];

        yield 'should find single record same as search until' => ['2', 100, 2, false, '2:'];

        yield 'should find single record above search since' => ['3', 100, 2, true, '3:'];

        yield 'should not find single record above search until' => ['3', 100, 2, false, null];

        yield 'should not find single record below search since' => ['2', 100, 3, true, null];

        yield 'should find single record below search until' => ['2', 100, 3, false, '2:'];

        yield 'should find single record with UTF-8' => ['2ё⚽🎲', 100, 2, true, '2:ё⚽🎲'];

        /** @var array{int,bool,null|string}[] $dataTestList */
        $dataTestList = [
            [1, true, '2:ё⚽🎲1'],
            [1, false, null],
            [2, true, '2:ё⚽🎲1'],
            [2, false, '2:ё⚽🎲2'],
            [3, true, '3:ё⚽🎲3'],
            [3, false, '3:ё⚽🎲4'],
            [4, true, '4:ё⚽🎲5'],
            [4, false, '4:ё⚽🎲6'],
            [5, true, null],
            [5, false, '4:ё⚽🎲6'],
        ];

        foreach ([100, 24] as $bufferSize) {
            foreach ($dataTestList as $test) {
                yield \sprintf('data test %d:%d:%s:%s', $bufferSize, $test[0], $test[1] ? 's' : 'u', $test[2]) => [
                    "2ё⚽🎲1\n2ё⚽🎲2\n3ё⚽🎲3\n3ё⚽🎲4\n4ё⚽🎲5\n4ё⚽🎲6",
                    $bufferSize,
                    ...$test,
                ];
            }
        }

        yield 'should not find buffer less than double record size' => ["2🎲🎲🎲🎲🎲🎲🎲🎲\n2ё⚽🎲2\n3ё⚽🎲3\n4ё⚽🎲4\n", 23, 3, true, null];

        $error = static fn(string $file, int $position, string $error): string => \sprintf('File %s is wrong somewhere after %d. %s', md5($file), $position, $error);

        yield 'should fail if found wrong record' => ['xё⚽🎲', 100, 2, true, new FileWrongException($error('xё⚽🎲', 0, 'Wrong day x at 0, expected integer from 1 to 31'))];

        yield 'should fail if wrong UTF-8' => ["2ё⚽\x80🎲", 100, 2, true, new FileWrongException($error("2ё⚽\x80🎲", 2, 'Not an UTF-8'))];

        yield 'should fail if not readable' => [null, 100, 2, true, new FileWrongException($error('', 0, \sprintf('Not readable %s', md5(''))))];
    }
}
