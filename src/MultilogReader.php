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

        $until = $logReaderConfig->date;
        $since = $until->sub(new \DateInterval($multilogPeriod->getIntervalString()));

        foreach ($logReaderConfig->files as $file) {
            $fileReader = $this->fileReaderFactory->createFileReader($file->filePath);
            $recordReader = new RecordReader($file->datePattern, $logReaderConfig->date->getTimezone());
            $recordSearch = new RecordSearch($fileReader, $recordReader, self::BUFFER_SIZE);
            $reader = new LogReader($fileReader, $recordReader, $recordSearch, self::BUFFER_SIZE);
            $filterFunction = $file->filterFunction;
            $recordCount = 0;

            foreach ($reader->readLog($since, $until) as $record) {
                if ($filterFunction($record)) {
                    $recordList[] = $record;
                    ++$recordCount;
                    if ($recordCount > $logReaderConfig->limit) {
                        break;
                    }
                }
            }
        }

        usort($recordList, static fn(Record $a, Record $b): int => $a->date->getTimestamp() <=> $b->date->getTimestamp());

        /** @var iterable<Record> $recordList */
        $recordList = \array_slice($recordList, 0, $logReaderConfig->limit);

        return $recordList;
    }
}
