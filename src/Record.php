<?php

declare(strict_types=1);

namespace LogParser;

final readonly class Record
{
    public function __construct(
        public \DateTimeImmutable $date,
        public int $position,
        public int $length,
        public string $record,
    ) {}
}
