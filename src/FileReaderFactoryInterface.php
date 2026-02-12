<?php

declare(strict_types=1);

namespace LogReader;

interface FileReaderFactoryInterface
{
    public function createFileReader(string $file): FileReader;
}
