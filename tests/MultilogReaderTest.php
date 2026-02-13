<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\FileReaderMemoryFactory;
use LogReader\LogReaderConfig;
use LogReader\LogReaderConfigFile;
use LogReader\MultilogPeriod;
use LogReader\MultilogReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MultilogReaderTest extends TestCase
{
    /**
     * @param string[] $expected
     */
    #[DataProvider('provideReadConfiguredCases')]
    public function testReadConfigured(string $now, MultilogPeriod $multilogPeriod, array $expected): void
    {
        $recordList = [];

        $logReaderConfig = new LogReaderConfig(
            $now,
            'UTC',
            '',
            '',
            2,
            [
                new LogReaderConfigFile(
                    "3a\n5b\n7c\n",
                    '~(?<second>\d)~',
                ),
                new LogReaderConfigFile(
                    "2d\n4e\n6f\n",
                    '~(?<second>\d)~',
                ),
            ],
        );

        $fileReaderMemoryFactory = new FileReaderMemoryFactory();
        $multilogReader = new MultilogReader($fileReaderMemoryFactory);

        foreach ($multilogReader->readConfigured($logReaderConfig, $multilogPeriod) as $record) {
            $recordList[] = $record->record;
        }

        $this->assertSame($expected, $recordList);
    }

    /**
     * @return iterable<string,array{string,MultilogPeriod,string[]}>
     */
    public static function provideReadConfiguredCases(): iterable
    {
        yield 'should find for minute' => ['0001-01-01 00:01:04', MultilogPeriod::MINUTE, ['e', 'b']];

        yield 'should find for hour' => ['0001-01-01 01:00:04', MultilogPeriod::HOUR, ['e', 'b']];

        yield 'should find for day' => ['0001-01-02 00:00:04', MultilogPeriod::DAY, ['e', 'b']];

        yield 'should find for week' => ['0001-01-08 00:00:04', MultilogPeriod::WEEK, ['e', 'b']];

        yield 'should find for month' => ['0001-02-01 00:00:04', MultilogPeriod::MONTH, ['e', 'b']];
    }
}
