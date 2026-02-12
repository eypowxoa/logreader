<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\LogReaderConfigFile;
use LogReader\Record;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LogReaderConfigFileTest extends TestCase
{
    public function testConstructShouldFailIfEmptyDatePattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfigFile('a', '');
    }

    public function testConstructShouldFailIfEmptyFilePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfigFile('', 'b');
    }

    public function testConstructShouldNotTrimDatePattern(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile('a', ' ');
        $this->assertSame(' ', $logReaderConfigFile->datePattern);
    }

    public function testConstructShouldNotTrimFilePath(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile(' ', 'b');
        $this->assertSame(' ', $logReaderConfigFile->filePath);
    }

    public function testConstructShouldSetDatePattern(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile('a', 'b');
        $this->assertSame('b', $logReaderConfigFile->datePattern);
    }

    public function testConstructShouldSetDefaultFilterFunction(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile('a', 'b');
        $this->assertInstanceOf(\Closure::class, $logReaderConfigFile->filterFunction);
    }

    public function testConstructShouldSetFilePath(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile('a', 'b');
        $this->assertSame('a', $logReaderConfigFile->filePath);
    }

    public function testConstructShouldSetFilterFunction(): void
    {
        $filterFunction = static fn(Record $record): false => false;
        $logReaderConfigFile = new LogReaderConfigFile('a', 'b', $filterFunction);
        $this->assertSame($filterFunction, $logReaderConfigFile->filterFunction);
    }
}
