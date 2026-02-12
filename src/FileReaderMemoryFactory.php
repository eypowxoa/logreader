<?php

declare(strict_types=1);

namespace LogReader;

final class FileReaderMemoryFactory implements FileReaderFactoryInterface
{
    public function createFileReader(string $file): FileReaderMemory
    {
        return new FileReaderMemory($file);
    }
}
