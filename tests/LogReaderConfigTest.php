<?php

declare(strict_types=1);

namespace LogParserTests;

use Carbon\CarbonImmutable;
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
        new LogReaderConfig(fileList: []);
    }

    public function testConstructShouldFailIfNoFileList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfig();
    }

    public function testConstructShouldSetDate(): void
    {
        $logReaderConfig = new LogReaderConfig(date: '2001-01-01 00:00:00', fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('2001-01-01 00:00:00', $logReaderConfig->date->format('Y-m-d H:i:s'));
    }

    public function testConstructShouldSetDefaultDate(): void
    {
        $now = CarbonImmutable::now()->sub(new \DateInterval('PT1S'));
        $logReaderConfig = new LogReaderConfig(fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertGreaterThanOrEqual($now->getTimestamp(), $logReaderConfig->date->getTimestamp());
    }

    public function testConstructShouldSetDefaultTimezone(): void
    {
        $logReaderConfig = new LogReaderConfig(fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('UTC', $logReaderConfig->date->getTimezone()->getName());
    }

    public function testConstructShouldSetFileList(): void
    {
        $logReaderConfigFile = new LogReaderConfigFile('a', 'b');
        $logReaderConfig = new LogReaderConfig(fileList: [$logReaderConfigFile]);
        $this->assertSame([$logReaderConfigFile], $logReaderConfig->files);
    }

    public function testConstructShouldSetTimezone(): void
    {
        $logReaderConfig = new LogReaderConfig(timezone: 'GMT', fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('GMT', $logReaderConfig->date->getTimezone()->getName());
    }
}
