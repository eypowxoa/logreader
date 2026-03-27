<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\FilterVariant;
use LogReader\LogReaderConfig;
use LogReader\LogReaderConfigFile;
use LogReader\MultilogPeriod;
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

    public function testConstructShouldFailIfEmptyPeriodList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfig('now', 'UTC', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], periodList: []);
    }

    public function testConstructShouldFailIfEmptyVariantList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogReaderConfig('now', 'UTC', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], variantList: []);
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

    public function testConstructShouldSetDefaultPeriodList(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertNotEmpty($logReaderConfig->periodList);
    }

    public function testConstructShouldSetDefaultVariantList(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertNotEmpty($logReaderConfig->variantList);
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

    public function testConstructShouldSetPeriodList(): void
    {
        $expected = [MultilogPeriod::MINUTE, MultilogPeriod::HOUR];
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], periodList: $expected);
        $this->assertSame($expected, $logReaderConfig->periodList);
    }

    public function testConstructShouldSetTimezone(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')]);
        $this->assertSame('GMT', $logReaderConfig->date->getTimezone()->getName());
    }

    public function testConstructShouldSetVariantList(): void
    {
        $expected = [FilterVariant::VARIANT_1, FilterVariant::VARIANT_2];
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], variantList: $expected);
        $this->assertSame($expected, $logReaderConfig->variantList);
    }

    public function testHasPeriodShouldReturnFalseIfHasNot(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], periodList: [MultilogPeriod::DAY]);
        $this->assertFalse($logReaderConfig->hasPeriod(MultilogPeriod::HOUR));
    }

    public function testHasPeriodShouldReturnTrueIfHas(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], periodList: [MultilogPeriod::DAY]);
        $this->assertTrue($logReaderConfig->hasPeriod(MultilogPeriod::DAY));
    }

    public function testHasVariantShouldReturnFalseIfHasNot(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], variantList: [FilterVariant::VARIANT_3]);
        $this->assertFalse($logReaderConfig->hasVariant(FilterVariant::VARIANT_2));
    }

    public function testHasVariantShouldReturnTrueIfHas(): void
    {
        $logReaderConfig = new LogReaderConfig('now', 'GMT', '', '', 1, fileList: [new LogReaderConfigFile('a', 'b')], variantList: [FilterVariant::VARIANT_3]);
        $this->assertTrue($logReaderConfig->hasVariant(FilterVariant::VARIANT_3));
    }
}
