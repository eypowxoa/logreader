<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\FileReaderMemoryFactory;
use LogReader\FilterVariant;
use LogReader\FilterVariantWrongException;
use LogReader\LogReaderConfig;
use LogReader\LogReaderConfigFile;
use LogReader\MultilogPeriod;
use LogReader\MultilogPeriodWrongException;
use LogReader\MultilogReader;
use LogReader\Record;
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

        foreach ($multilogReader->readConfigured(
            $logReaderConfig,
            $multilogPeriod,
        ) as $record) {
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

    public function testReadConfiguredShouldFailIfNotConfiguredPeriod(): void
    {
        $logReaderConfig = new LogReaderConfig(
            '0001-01-01 00:01:04',
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
            periodList: [
                MultilogPeriod::DAY,
                MultilogPeriod::MONTH,
            ]
        );

        $fileReaderMemoryFactory = new FileReaderMemoryFactory();
        $multilogReader = new MultilogReader($fileReaderMemoryFactory);

        $this->expectException(MultilogPeriodWrongException::class);
        $this->expectExceptionMessage('got WEEK');
        $this->expectExceptionMessage('expected DAY, MONTH');

        $multilogReader->readConfigured($logReaderConfig, MultilogPeriod::WEEK);
    }

    public function testReadConfiguredShouldFailIfNotConfiguredVariant(): void
    {
        $logReaderConfig = new LogReaderConfig(
            '0001-01-01 00:01:04',
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
            variantList: [
                FilterVariant::VARIANT_1,
                FilterVariant::VARIANT_3,
            ]
        );

        $fileReaderMemoryFactory = new FileReaderMemoryFactory();
        $multilogReader = new MultilogReader($fileReaderMemoryFactory);

        $this->expectException(FilterVariantWrongException::class);
        $this->expectExceptionMessage('got VARIANT_2');
        $this->expectExceptionMessage('expected VARIANT_1, VARIANT_3');

        $multilogReader->readConfigured($logReaderConfig, MultilogPeriod::DAY, FilterVariant::VARIANT_2);
    }

    public function testReadConfiguredShouldPassVariantNumberToFilterFunction(): void
    {
        /** @var FilterVariant[] $passedVariants */
        $passedVariants = [];

        $logReaderConfig = new LogReaderConfig(
            '0001-01-01 00:01:00',
            'UTC',
            '',
            '',
            2,
            [
                new LogReaderConfigFile(
                    "3a\n5b\n",
                    '~(?<second>\d)~',
                    static function (Record $record, FilterVariant $filterVariant) use (&$passedVariants): true {
                        $passedVariants[] = $filterVariant;

                        return true;
                    },
                ),
            ],
            variantList: [FilterVariant::VARIANT_1, FilterVariant::VARIANT_2],
        );

        $fileReaderMemoryFactory = new FileReaderMemoryFactory();
        $multilogReader = new MultilogReader($fileReaderMemoryFactory);

        $multilogReader->readConfigured($logReaderConfig, MultilogPeriod::MINUTE, FilterVariant::VARIANT_1);

        $this->assertSame([FilterVariant::VARIANT_1, FilterVariant::VARIANT_1], $passedVariants);

        /** @var FilterVariant[] $passedVariants */
        $passedVariants = [];

        $multilogReader->readConfigured($logReaderConfig, MultilogPeriod::MINUTE, FilterVariant::VARIANT_2);

        $this->assertSame([FilterVariant::VARIANT_2, FilterVariant::VARIANT_2], $passedVariants);
    }

    public function testReadConfiguredShouldSkipNotReadableIfCheckAccessEnabled(): void
    {
        $logReaderConfig = new LogReaderConfig(
            '0001-01-01 00:00:01',
            'UTC',
            '',
            '',
            2,
            [
                new LogReaderConfigFile(
                    '1/readable.txt',
                    '~(?<second>\d)~',
                ),
                new LogReaderConfigFile(
                    '1/unreadable.txt',
                    '~(?<second>\d)~',
                    checkAccess: true,
                ),
            ],
        );

        $fileReaderMemoryFactory = new FileReaderMemoryFactory();
        $fileReaderMemoryFactory->unreadable[] = '1/unreadable.txt';
        $multilogReader = new MultilogReader($fileReaderMemoryFactory);

        $recordList = $multilogReader->readConfigured(
            $logReaderConfig,
            MultilogPeriod::MINUTE,
        );

        $this->assertCount(1, $recordList);
        $this->assertArrayHasKey(0, $recordList);

        $record = $recordList[0];

        $this->assertSame('readable.txt', $record->source);
    }

    public function testReadConfiguredShouldUseBasenameAsRecordSource(): void
    {
        $logReaderConfig = new LogReaderConfig(
            '0001-01-01 00:00:01',
            'UTC',
            '',
            '',
            2,
            [
                new LogReaderConfigFile(
                    '1/2.txt',
                    '~(?<second>\d)~',
                ),
            ],
        );

        $fileReaderMemoryFactory = new FileReaderMemoryFactory();
        $multilogReader = new MultilogReader($fileReaderMemoryFactory);

        $recordList = $multilogReader->readConfigured(
            $logReaderConfig,
            MultilogPeriod::MINUTE,
        );

        $this->assertArrayHasKey(0, $recordList);

        $record = $recordList[0];

        $this->assertSame('2.txt', $record->source);
    }
}
