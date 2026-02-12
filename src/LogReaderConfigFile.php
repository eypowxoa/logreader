<?php

declare(strict_types=1);

namespace LogReader;

final readonly class LogReaderConfigFile
{
    public \Closure $filterFunction;

    public function __construct(
        public string $filePath,
        public string $datePattern,
        ?\Closure $filterFunction = null,
    ) {
        if ('' === $filePath) {
            throw new \InvalidArgumentException('Empty filePath parameter');
        }

        if ('' === $datePattern) {
            throw new \InvalidArgumentException('Empty datePattern parameter');
        }

        $this->filterFunction = ($filterFunction ?? (static fn(Record $record): true => true));
    }
}
