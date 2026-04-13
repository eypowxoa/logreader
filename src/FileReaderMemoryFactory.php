<?php

declare(strict_types=1);

namespace LogReader;

final class FileReaderMemoryFactory implements FileReaderFactoryInterface
{
    /** @var string[] */
    public array $unreadable = [];

    public function createFileReader(string $file): FileReaderMemory
    {
        return new FileReaderMemory($file, \in_array($file, $this->unreadable, true));
    }
}
