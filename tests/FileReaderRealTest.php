<?php

declare(strict_types=1);

namespace LogParserTests;

use LogParser\FileNotReadableException;
use LogParser\FileReaderReal;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileReaderRealTest extends TestCase
{
    public function testConstructShouldSavePath(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertSame(__FILE__, $fileReaderReal->path);
    }

    public function testReadCounterShouldBeZeroAtStart(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertSame(0, $fileReaderReal->readCounter);
    }

    public function testReadShouldContinueReadingAtLastPosition(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertNotEmpty($fileReaderReal->read(2));
        $this->assertSame('php', $fileReaderReal->read(3));
    }

    public function testReadShouldFailIfDirectory(): void
    {
        $fileReaderReal = new FileReaderReal(__DIR__);
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReaderReal->read(1));
    }

    public function testReadShouldFailIfNegativeLength(): void
    {
        $fileReaderReal = new FileReaderReal('php://stdout');
        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotEmpty($fileReaderReal->read(-1));
    }

    public function testReadShouldFailIfNotExists(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__ . '.worng');
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReaderReal->read(1));
    }

    public function testReadShouldFailIfNotReadable(): void
    {
        $fileReaderReal = new FileReaderReal('php://stdout');
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReaderReal->read(1));
    }

    public function testReadShouldFailIfZeroLength(): void
    {
        $fileReaderReal = new FileReaderReal('php://stdout');
        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotEmpty($fileReaderReal->read(0));
    }

    public function testReadShouldIncrementReadCounter(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertNotEmpty($fileReaderReal->read(3));
        $this->assertSame(1, $fileReaderReal->readCounter);
        $this->assertNotEmpty($fileReaderReal->read(3));
        $this->assertSame(2, $fileReaderReal->readCounter);
    }

    public function testReadShouldReadAllFile(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertSame(file_get_contents(__FILE__), $fileReaderReal->read(10_000));
    }

    public function testReadShouldReadFromStart(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertSame('<?php', $fileReaderReal->read(5));
    }

    public function testReadShouldReturnEmptyStringAtTheEnd(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertNotEmpty($fileReaderReal->read(10_000));
        $this->assertSame('', $fileReaderReal->read(1));
    }

    public function testSeekShouldFailIfNegativePosition(): void
    {
        $fileReaderReal = new FileReaderReal('php://stdout');
        $this->expectException(\InvalidArgumentException::class);
        $fileReaderReal->seek(-1);
    }

    public function testSeekShouldFailIfNotSeekable(): void
    {
        $fileReaderReal = new FileReaderReal('php://stdout');
        $this->expectException(FileNotReadableException::class);
        $fileReaderReal->seek(0);
    }

    public function testSeekShouldMoveReadingPosition(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertNotEmpty($fileReaderReal->read(1));
        $fileReaderReal->seek(2);
        $this->assertSame('php', $fileReaderReal->read(3));
    }

    public function testSeekShouldSetStartingPosition(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $fileReaderReal->seek(2);
        $this->assertSame('php', $fileReaderReal->read(3));
    }

    public function testSizeShouldFailIfNotExists(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__ . '.wrong');
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReaderReal->size());
    }

    public function testSizeShouldFailIfNotReadable(): void
    {
        $fileReaderReal = new FileReaderReal('php://stdout');
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReaderReal->size());
    }

    public function testSizeShouldReturnFileSize(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertSame(filesize(__FILE__), $fileReaderReal->size());
    }

    public function testTellShouldFailIfFileNotExists(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__ . '.wrong');
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReaderReal->tell());
    }

    public function testTellShouldFailIfFileNotReadable(): void
    {
        $fileReaderReal = new FileReaderReal('php://stdout');
        $this->expectException(FileNotReadableException::class);
        $this->assertNotEmpty($fileReaderReal->tell());
    }

    public function testTellShouldReturnPosition(): void
    {
        $fileReaderReal = new FileReaderReal(__FILE__);
        $this->assertSame(0, $fileReaderReal->tell());
        $this->assertNotEmpty($fileReaderReal->read(10_000));
        $this->assertSame(filesize(__FILE__), $fileReaderReal->tell());
    }
}
