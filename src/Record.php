<?php

declare(strict_types=1);

namespace LogParser;

final readonly class Record implements \Stringable
{
    public int $border;

    public function __construct(
        public \DateTimeImmutable $date,
        public int $position,
        public int $length,
        public string $record,
    ) {
        $this->border = ($position + $length);
    }

    public function __toString(): string
    {
        return \sprintf('%s %s', $this->date->format('Y-m-d H:i:s'), $this->record);
    }
}
