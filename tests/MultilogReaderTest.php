<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\FileReaderMemoryFactory;
use LogReader\LogReaderConfig;
use LogReader\LogReaderConfigFile;
use LogReader\MultilogPeriod;
use LogReader\MultilogReader;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MultilogReaderTest extends TestCase
{
    public function testReadConfigured(): void
    {
        $recordList = [];

        $logReaderConfig = new LogReaderConfig(
            '0001-01-01 00:01:00',
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

        foreach ($multilogReader->readConfigured($logReaderConfig, MultilogPeriod::MINUTE) as $record) {
            $recordList[] = $record->record;
        }

        $this->assertSame(['d', 'a'], $recordList);
    }
}
