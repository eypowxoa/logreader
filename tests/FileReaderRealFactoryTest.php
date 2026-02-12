<?php

declare(strict_types=1);

namespace LogReaderTests;

use LogReader\FileReaderReal;
use LogReader\FileReaderRealFactory;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileReaderRealFactoryTest extends TestCase
{
    public function testCreateFileReader(): void
    {
        $fileReaderRealFactory = new FileReaderRealFactory();
        $fileReaderReal = $fileReaderRealFactory->createFileReader(__FILE__);
        $this->assertInstanceOf(FileReaderReal::class, $fileReaderReal);
        $this->assertSame('<?php', $fileReaderReal->read(5));
    }
}
