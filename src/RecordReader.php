<?php

declare(strict_types=1);

namespace LogReader;

final class RecordReader
{
    private const string BYTE_ENCODING = '8bit';

    private const string NEW_LINE = "\n";

    public int $offset = 0;

    private string $buffer = '';

    private bool $complete = false;

    private readonly DateReader $dateReader;

    private int $position = 0;

    public function __construct(
        string $datePattern,
        \DateTimeZone $dateTimeZone = new \DateTimeZone('UTC'),
    ) {
        $this->dateReader = new DateReader($datePattern, $dateTimeZone);
    }

    /**
     * @throws RecordWrongException
     */
    #[\NoDiscard()]
    public function readRecord(): ?Record
    {
        $this->dateReader->buffer = $this->buffer;

        $found = false;
        $dateOffset = 0;
        $recordDate = null;
        $recordOffset = 0;
        $newLinePosition = 0;

        while (true) {
            try {
                $this->dateReader->readDate($this->offset);
            } catch (DateWrongException $dateWrongException) {
                throw new RecordWrongException($dateWrongException->getMessage());
            }

            $offset = $this->offset;

            $date = $this->dateReader->date;

            if ($date instanceof \DateTimeImmutable) {
                if ($found) {
                    break;
                }

                $found = true;
                $dateOffset = $this->dateReader->offset;
                $recordDate = $date;
                $recordOffset = $this->offset;
                $offset = $this->dateReader->offset;
            }

            $newLinePosition = mb_strpos($this->buffer, self::NEW_LINE, $offset, self::BYTE_ENCODING);

            if (false === $newLinePosition) {
                if ($this->complete) {
                    $this->offset = mb_strlen($this->buffer, self::BYTE_ENCODING);
                } else {
                    $recordDate = null;
                }

                break;
            }

            $this->offset = ($newLinePosition + 1);
        }

        if ($recordDate instanceof \DateTimeImmutable) {
            $bodyLength = ($this->offset - $dateOffset);
            $bodyContent = mb_substr($this->buffer, $dateOffset, $bodyLength, self::BYTE_ENCODING) |> \trim(...);

            return new Record($recordDate, $this->position + $recordOffset, $this->offset - $recordOffset, $bodyContent);
        }

        return null;
    }

    public function setBuffer(string $buffer, bool $complete = false, int $position = 0): void
    {
        $this->buffer = $buffer;
        $this->complete = $complete;
        $this->offset = 0;
        $this->position = $position;
    }
}
