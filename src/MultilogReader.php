<?php

declare(strict_types=1);

namespace LogReader;

final readonly class MultilogReader
{
    private const int BUFFER_SIZE = 10_000;

    public function __construct(private FileReaderFactoryInterface $fileReaderFactory) {}

    /**
     * @return Record[]
     */
    public function readConfigured(LogReaderConfig $logReaderConfig, MultilogPeriod $multilogPeriod): iterable
    {
        /** @var Record[] $recordList */
        $recordList = [];

        /** @var \Iterator<int,Record>[] $readerList */
        $readerList = [];

        /** @var int[] $timestampList */
        $timestampList = [];

        /** @var \Closure[] $filterList */
        $filterList = [];

        $until = $logReaderConfig->date;
        $since = $until->sub(new \DateInterval($multilogPeriod->getIntervalString()));

        foreach ($logReaderConfig->files as $file) {
            $fileReader = $this->fileReaderFactory->createFileReader($file->filePath);
            $recordReader = new RecordReader($file->datePattern, $logReaderConfig->date->getTimezone());
            $recordSearch = new RecordSearch($fileReader, $recordReader, self::BUFFER_SIZE);
            $reader = new LogReader($fileReader, $recordReader, $recordSearch, self::BUFFER_SIZE);

            $readerList[] = $reader->readLog($since, $until);
            $filterList[] = $file->filterFunction;
            $timestampList[] = 0;
            $currentList[] = null;
        }

        foreach ($readerList as $readerNumber => $reader) {
            $timestampList[$readerNumber] = PHP_INT_MAX;

            $filter = $filterList[$readerNumber];

            while ($reader->valid()) {
                $record = $reader->current();
                $reader->next();

                if ($filter($record)) {
                    $timestampList[$readerNumber] = $record->date->getTimestamp();
                    $currentList[$readerNumber] = $record;

                    break;
                }
            }
        }

        for ($recordNumber = 0; $recordNumber < $logReaderConfig->limit; ++$recordNumber) {
            $minimalTimestamp = PHP_INT_MAX;
            $minimalNumber = -1;

            for ($readerNumber = 0, $readerCount = \count($readerList); $readerNumber < $readerCount; ++$readerNumber) {
                if ($timestampList[$readerNumber] < $minimalTimestamp) {
                    $minimalNumber = $readerNumber;
                    $minimalTimestamp = $timestampList[$readerNumber];
                }
            }

            $currentRecord = ($currentList[$minimalNumber] ?? null);

            if ($currentRecord instanceof Record) {
                $recordList[] = $currentRecord;

                $timestampList[$minimalNumber] = PHP_INT_MAX;

                $filter = $filterList[$minimalNumber];
                $reader = $readerList[$minimalNumber];

                while ($reader->valid()) {
                    $record = $reader->current();
                    $reader->next();

                    if ($filter($record)) {
                        $timestampList[$minimalNumber] = $record->date->getTimestamp();
                        $currentList[$minimalNumber] = $record;

                        break;
                    }
                }
            }
        }

        return $recordList;
    }
}
