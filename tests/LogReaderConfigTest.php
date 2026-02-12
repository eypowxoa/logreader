<?php

declare(strict_types=1);

namespace LogParserTests;

use LogParser\LogReaderConfig;
use LogParser\LogReaderConfigFile;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LogReaderConfigTest extends TestCase
{
    public function testConstructShouldFailIfEmptyFileList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfig('now', 'UTC', '', '', 1, fileList: []);
    }

    public function testConstructShouldFailIfNegativeLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfig('now', 'UTC', '', '', -1, fileList: [new LogReaderConfigFile('a', 'b')]);
    }

    public function testConstructShouldFailIfZeroLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfig('now', 'UTC', '', '', 0, fileList: [new LogReaderConfigFile('a', 'b')]);
    }

    public function testConstructShouldSetDate(): void
    {
        $logReaderConfig = new LogReaderConfig('2001-01-01 00:00:00', 'UTC', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('2001-01-01 00:00:00', $logReaderConfig->date->format('Y-m-d H:i:s'));
    }

    public function testConstructShouldSetFileList(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile('a', 'b');
        $logReaderConfig = new LogReaderConfig('now', 'UTC', '', '', 1, fileList: [$logReaderConfigFile]);
        $this->assertSame([$logReaderConfigFile], $logReaderConfig->files);
    }

    public function testConstructShouldSetHttpAuth(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', 'l', 'p', 1, fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('Basic bDpw', $logReaderConfig->httpAuth);
    }

    public function testConstructShouldSetTimezone(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('GMT', $logReaderConfig->date->getTimezone()->getName());
    }
}
