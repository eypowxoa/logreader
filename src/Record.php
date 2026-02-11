<?php

namespace LogParser;

final class Record
{
    /**
     * @readonly
     * @var \DateTimeImmutable
     */
    public $date;
    /**
     * @readonly
     * @var int
     */
    public $position;
    /**
     * @readonly
     * @var int
     */
    public $length;
    /**
     * @readonly
     * @var string
     */
    public $record;
    /**
     * @readonly
     * @var int
     */
    public $border;
    public function __construct(
        \DateTimeImmutable $date,
        $position,
        $length,
        $record
    ) {
        $this->date = $date;
        $this->position = $position;
        $this->length = $length;
        $this->record = $record;
        $this->border = ($position + $length);
    }
    public function __toString()
    {
        return \sprintf('%s %s', $this->date->format('Y-m-d H:i:s'), $this->record);
    }
}
