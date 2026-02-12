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
        new LogReaderConfig('now', 'UTC', '', '', fileList: []);
    }

    public function testConstructShouldSetDate(): void
    {
        $logReaderConfig = new LogReaderConfig('2001-01-01 00:00:00', 'UTC', '', '', fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('2001-01-01 00:00:00', $logReaderConfig->date->format('Y-m-d H:i:s'));
    }

    public function testConstructShouldSetFileList(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile('a', 'b');
        $logReaderConfig = new LogReaderConfig('now', 'UTC', '', '', fileList: [$logReaderConfigFile]);
        $this->assertSame([$logReaderConfigFile], $logReaderConfig->files);
    }

    public function testConstructShouldSetHttpAuth(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', 'l', 'p', fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('Basic bDpw', $logReaderConfig->httpAuth);
    }

    public function testConstructShouldSetTimezone(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('GMT', $logReaderConfig->date->getTimezone()->getName());
    }
}
