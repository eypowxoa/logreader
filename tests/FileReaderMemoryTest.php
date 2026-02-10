<?php

declare(strict_types=1);

namespace LogParserTests;

use LogParser\FileReaderMemory;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileReaderMemoryTest extends TestCase
{
    public function testConstructShouldSavePath(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertSame(md5('<?php echo "OK\n";'), $fileReaderMemory->path);
    }

    public function testReadCounterShouldBeZeroAtStart(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertSame(0, $fileReaderMemory->readCounter);
    }

    public function testReadShouldContinueReadingAtLastPosition(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertNotEmpty($fileReaderMemory->read(2));
        $this->assertSame('php', $fileReaderMemory->read(3));
    }

    public function testReadShouldFailIfNegativeLength(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotEmpty($fileReaderMemory->read(-1));
    }

    public function testReadShouldFailIfZeroLength(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotEmpty($fileReaderMemory->read(0));
    }

    public function testReadShouldIncrementReadCounter(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertNotEmpty($fileReaderMemory->read(3));
        $this->assertSame(1, $fileReaderMemory->readCounter);
        $this->assertNotEmpty($fileReaderMemory->read(3));
        $this->assertSame(2, $fileReaderMemory->readCounter);
    }

    public function testReadShouldReadAllFile(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertSame('<?php echo "OK\n";', $fileReaderMemory->read(10_000));
    }

    public function testReadShouldReadFromStart(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertSame('<?php', $fileReaderMemory->read(5));
    }

    public function testReadShouldReturnEmptyIfEmptyData(): void
    {
        $fileReaderMemory = new FileReaderMemory('');
        $this->assertSame('', $fileReaderMemory->read(1));
    }

    public function testReadShouldReturnEmptyStringAtTheEnd(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertNotEmpty($fileReaderMemory->read(10_000));
        $this->assertSame('', $fileReaderMemory->read(1));
    }

    public function testSeekShouldFailIfNegativePosition(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->expectException(\InvalidArgumentException::class);
        $fileReaderMemory->seek(-1);
    }

    public function testSeekShouldMoveReadingPosition(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertNotEmpty($fileReaderMemory->read(1));
        $fileReaderMemory->seek(2);
        $this->assertSame('php', $fileReaderMemory->read(3));
    }

    public function testSeekShouldSetStartingPosition(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $fileReaderMemory->seek(2);
        $this->assertSame('php', $fileReaderMemory->read(3));
    }

    public function testSizeShouldReturnFileSize(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertSame(18, $fileReaderMemory->size());
    }

    public function testTellShouldReturnPosition(): void
    {
        $fileReaderMemory = new FileReaderMemory('<?php echo "OK\n";');
        $this->assertSame(0, $fileReaderMemory->tell());
        $this->assertNotEmpty($fileReaderMemory->read(10_000));
        $this->assertSame(18, $fileReaderMemory->tell());
    }
}
