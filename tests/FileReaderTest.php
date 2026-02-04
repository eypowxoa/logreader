<?php

declare(strict_types=1);

namespace LogParserTests;

use LogParser\FileNotExistsException;
use LogParser\FileNotReadableException;
use LogParser\FileNotSeekableException;
use LogParser\FileReader;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileReaderTest extends TestCase
{
    public function testConstructShouldSavePath(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertSame(__FILE__, $fileReader->path);
    }

    public function testReadCounterShouldBeZeroAtStart(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertSame(0, $fileReader->readCounter);
    }

    public function testReadShouldContinueReadingAtLastPosition(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertNotEmpty($fileReader->read(2));
        $this->assertSame('php', $fileReader->read(3));
    }

    public function testReadShouldFailIfDirectory(): void
    {
        $fileReader = new FileReader(__DIR__);
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReader->read(1));
    }

    public function testReadShouldFailIfNegativeLength(): void
    {
        $fileReader = new FileReader('php://stdout');
        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotEmpty($fileReader->read(-1));
    }

    public function testReadShouldFailIfNotExists(): void
    {
        $fileReader = new FileReader(__FILE__ . '.worng');
        $this->expectException(FileNotExistsException::class);
        $this->assertNotEmpty($fileReader->read(1));
    }

    public function testReadShouldFailIfNotReadable(): void
    {
        $fileReader = new FileReader('php://stdout');
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReader->read(1));
    }

    public function testReadShouldFailIfZeroLength(): void
    {
        $fileReader = new FileReader('php://stdout');
        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotEmpty($fileReader->read(0));
    }

    public function testReadShouldIncrementReadCounter(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertNotEmpty($fileReader->read(3));
        $this->assertSame(1, $fileReader->readCounter);
        $this->assertNotEmpty($fileReader->read(3));
        $this->assertSame(2, $fileReader->readCounter);
    }

    public function testReadShouldReadAllFile(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertSame(file_get_contents(__FILE__), $fileReader->read(10_000));
    }

    public function testReadShouldReadFromStart(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertSame('<?php', $fileReader->read(5));
    }

    public function testReadShouldReturnEmptyStringAtTheEnd(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertNotEmpty($fileReader->read(10_000));
        $this->assertSame('', $fileReader->read(1));
    }

    public function testSeekShouldFailIfNegativePosition(): void
    {
        $fileReader = new FileReader('php://stdout');
        $this->expectException(\InvalidArgumentException::class);
        $fileReader->seek(-1);
    }

    public function testSeekShouldFailIfNotSeekable(): void
    {
        $fileReader = new FileReader('php://stdout');
        $this->expectException(FileNotSeekableException::class);
        $fileReader->seek(0);
    }

    public function testSeekShouldMoveReadingPosition(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertNotEmpty($fileReader->read(1));
        $fileReader->seek(2);
        $this->assertSame('php', $fileReader->read(3));
    }

    public function testSeekShouldSetStartingPosition(): void
    {
        $fileReader = new FileReader(__FILE__);
        $fileReader->seek(2);
        $this->assertSame('php', $fileReader->read(3));
    }

    public function testSizeShouldFailIfNotExists(): void
    {
        $fileReader = new FileReader(__FILE__ . '.wrong');
        $this->expectException(FileNotExistsException::class);
        $this->assertNotEmpty($fileReader->size());
    }

    public function testSizeShouldFailIfNotReadable(): void
    {
        $fileReader = new FileReader('php://stdout');
        $this->expectException(FileNotExistsException::class);
        $this->assertNotEmpty($fileReader->size());
    }

    public function testSizeShouldReturnFileSize(): void
    {
        $fileReader = new FileReader(__FILE__);
        $this->assertSame(filesize(__FILE__), $fileReader->size());
    }
}
