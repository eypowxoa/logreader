<?php

declare(strict_types=1);

namespace LogReader;

final class FileReaderRealFactory implements FileReaderFactoryInterface
{
    public function createFileReader(string $file): FileReaderReal
    {
        return new FileReaderReal($file);
    }
}
