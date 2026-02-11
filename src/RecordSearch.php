<?php

declare(strict_types=1);

namespace LogParser;

final readonly class RecordSearch
{
    public function __construct(
        private FileReader $fileReader,
        private RecordReader $recordReader,
        private int $bufferSize,
    ) {
        if ($bufferSize <= 0) {
            throw new \InvalidArgumentException(\sprintf('Wrong buffer size %d, expected integer above zero', $bufferSize));
        }
    }

    /**
     * @throws FileWrongException
     */
    #[\NoDiscard()]
    public function findRecord(\DateTimeInterface $date, bool $since): ?Record
    {
        $position = 0;

        try {
            $below = null;
            $above = null;

            $target = $date->getTimestamp();
            $lower = 0;
            $upper = $this->fileReader->size();

            $persistentBuffer = null;
            $persistentBufferStart = $lower;

            while ($lower < $upper) {
                $record = null;

                $middle = $upper;

                $length = ($upper - $lower);

                if (($length >= $this->bufferSize) && (null === $persistentBuffer)) {
                    $this->fileReader->seek($lower);
                    $persistentBuffer = $this->fileReader->read($length);
                    $persistentBufferStart = $lower;
                }

                while ((!$record instanceof Record) && ($middle > $lower)) {
                    $middle = intdiv($lower + $middle, 2);

                    $length = ($upper - $middle);

                    if ($length > $this->bufferSize) {
                        $length = $this->bufferSize;
                    }

                    if (null === $persistentBuffer) {
                        $this->fileReader->seek($middle);
                        $buffer = $this->fileReader->read($length);
                    } else {
                        $buffer = mb_substr($persistentBuffer, $middle - $persistentBufferStart, $length, '8bit');
                    }

                    $position = $middle;

                    [$utf8Offset,$utf8Length] = Utf8Fixer::trimUtf8($buffer);

                    if ($utf8Length < $length) {
                        $buffer = mb_substr($buffer, $utf8Offset, $utf8Length, '8bit');
                        $position += $utf8Offset;
                    }

                    $complete = (($position + $length) >= $upper);

                    $this->recordReader->setBuffer($buffer, $complete, $position);

                    $record = $this->recordReader->readRecord();
                }

                if (!$record instanceof Record) {
                    return null;
                }

                $found = $record->date->getTimestamp();

                if ($found < $target) {
                    $lower = $record->border;
                    $below = $record;
                } elseif ($found === $target) {
                    if ($since) {
                        $upper = $record->position;
                        $above = $record;
                    } else {
                        $lower = $record->border;
                        $below = $record;
                    }
                } else {
                    $upper = $record->position;
                    $above = $record;
                }
            }

            if ($since) {
                return $above;
            }

            return $below;
        } catch (CheckedException $checkedException) {
            throw new FileWrongException(
                \sprintf(
                    'File %s is wrong somewhere after %d. %s',
                    $this->fileReader->path,
                    $position,
                    $checkedException->getMessage()
                ),
                0,
                $checkedException
            );
        }
    }
}
