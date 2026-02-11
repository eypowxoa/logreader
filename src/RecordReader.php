<?php

namespace LogParser;

final class RecordReader
{
    /**
     * @var string
     */
    const BYTE_ENCODING = '8bit';

    /**
     * @var string
     */
    const NEW_LINE = "\n";

    /**
     * @var int
     */
    public $offset = 0;

    /**
     * @var string
     */
    private $buffer = '';

    /**
     * @var bool
     */
    private $complete = false;

    /**
     * @readonly
     * @var \LogParser\DateReader
     */
    private $dateReader;

    /**
     * @var int
     */
    private $position = 0;

    public function __construct(
        $datePattern,
        \DateTimeZone $dateTimeZone = null
    ) {
        $dateTimeZone = $dateTimeZone ?: new \DateTimeZone('UTC');
        $this->dateReader = new DateReader($datePattern, $dateTimeZone);
    }

    /**
     * @throws RecordWrongException
     */
    public function readRecord()
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
            $bodyContent = \trim(mb_substr($this->buffer, $dateOffset, $bodyLength, self::BYTE_ENCODING));

            return new Record($recordDate, $this->position + $recordOffset, $this->offset - $recordOffset, $bodyContent);
        }
        return null;
    }

    public function setBuffer($buffer, $complete = false, $position = 0)
    {
        $this->buffer = $buffer;
        $this->complete = $complete;
        $this->offset = 0;
        $this->position = $position;
    }
}
