<?php

namespace LogParser;

final class LogReader
{
    /**
     * @readonly
     * @var \LogParser\FileReader
     */
    private $fileReader;
    /**
     * @readonly
     * @var \LogParser\RecordReader
     */
    private $recordReader;
    /**
     * @readonly
     * @var \LogParser\RecordSearch
     */
    private $recordSearch;
    /**
     * @readonly
     * @var int
     */
    private $bufferSize;
    public function __construct(FileReader $fileReader, RecordReader $recordReader, RecordSearch $recordSearch, $bufferSize)
    {
        $this->fileReader = $fileReader;
        $this->recordReader = $recordReader;
        $this->recordSearch = $recordSearch;
        $this->bufferSize = $bufferSize;
    }

    /**
     * @return iterable<Record>
     *
     * @throws LogWrongException
     */
    public function readLog(\DateTimeInterface $since, \DateTimeInterface $until)
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

                $a = Utf8Fixer::trimUtf8($buffer);
                $utf8Offset = $a[0];
                $utf8Length = $a[1];

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
                $checkedException->getMessage()
            ));
        }
    }
}
