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
    public function readConfigured(
        LogReaderConfig $logReaderConfig,
        MultilogPeriod $multilogPeriod,
        FilterVariant $filterVariant = FilterVariant::VARIANT_1,
    ): array {
        if (! $logReaderConfig->hasPeriod($multilogPeriod)) {
            throw new MultilogPeriodWrongException(\sprintf(
                'Not configured period, got %s, expected %s',
                $multilogPeriod->name,
                implode(', ', array_map(
                    static fn(MultilogPeriod $multilogPeriod): string => $multilogPeriod->name,
                    $logReaderConfig->periodList,
                )),
            ));
        }

        if (! $logReaderConfig->hasVariant($filterVariant)) {
            throw new FilterVariantWrongException(\sprintf(
                'Not configured variant, got %s, expected %s',
                $filterVariant->name,
                implode(', ', array_map(
                    static fn(FilterVariant $filterVariant): string => $filterVariant->name,
                    $logReaderConfig->variantList,
                )),
            ));
        }

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

            if ($file->checkAccess && !$fileReader->isReadable()) {
                continue;
            }

            $recordReader = new RecordReader(
                $file->datePattern,
                $logReaderConfig->date->getTimezone(),
                basename($file->filePath),
            );

            $recordSearch = new RecordSearch(
                $fileReader,
                $recordReader,
                self::BUFFER_SIZE,
            );

            $reader = new LogReader(
                $fileReader,
                $recordReader,
                $recordSearch,
                self::BUFFER_SIZE,
            );

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

                if ($filter($record, $filterVariant)) {
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

                    if ($filter($record, $filterVariant)) {
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
