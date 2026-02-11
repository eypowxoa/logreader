<?php

declare(strict_types=1);

namespace LogParser;

final readonly class LogReader
{
    public function __construct(
        private FileReader $fileReader,
        private RecordReader $recordReader,
        private RecordSearch $recordSearch,
        private int $bufferSize,
    ) {}

    /**
     * @return iterable<Record>
     *
     * @throws LogWrongException
     */
    public function readLog(\DateTimeInterface $since, \DateTimeInterface $until): iterable
    {
        $position = 0;

        try {
            $start = $this->recordSearch->findRecord($since, true);
            $end = $this->recordSearch->findRecord($until, false);

            if (!$start instanceof Record) {
                return;
            }

            if (!$end instanceof Record) {
                return;
            }

            $position = $start->position;
            while ($position < $end->border) {
                $length = ($end->border - $position);

                if ($length > $this->bufferSize) {
                    $length = $this->bufferSize;
                }

                $this->fileReader->seek($position);

                $buffer = $this->fileReader->read($length);

                [$utf8Offset, $utf8Length] = Utf8Fixer::trimUtf8($buffer);

                if ($utf8Length < $length) {
                    $buffer = mb_substr($buffer, $utf8Offset, $utf8Length, '8bit');
                    $position += $utf8Offset;
                }

                $this->recordReader->setBuffer($buffer, ($position + $length) >= $end->border, $position);

                $record = $this->recordReader->readRecord();

                if (!$record instanceof Record) {
                    return;
                }

                while ($record instanceof Record) {
                    yield $record;
                    $position = $record->border;
                    $record = $this->recordReader->readRecord();
                }
            }
        } catch (CheckedException $checkedException) {
            throw new LogWrongException(\sprintf(
                'Failed to read log %s after %d. %s',
                $this->fileReader->path,
                $position,
                $checkedException->getMessage(),
            ));
        }
    }
}
