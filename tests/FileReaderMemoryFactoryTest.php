<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\FileReaderMemory;
use LogReader\FileReaderMemoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileReaderMemoryFactoryTest extends TestCase
{
    public function testCreateFileReader(): void
    {
        $fileReaderMemoryFactory = new FileReaderMemoryFactory();
        $fileReaderMemory = $fileReaderMemoryFactory->createFileReader('test');
        $this->assertInstanceOf(FileReaderMemory::class, $fileReaderMemory);
        $this->assertSame('test', $fileReaderMemory->read(4));
    }
}
