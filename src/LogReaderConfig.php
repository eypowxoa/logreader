<?php

declare(strict_types=1);

namespace LogParser;

final readonly class LogReaderConfig
{
    public \DateTimeImmutable $date;

    /** @var LogReaderConfigFile[] */
    public array $files;

    /**
     * @param LogReaderConfigFile[] $fileList
     */
    public function __construct(
        string $date = 'now',
        string $timezone = 'UTC',
        array $fileList = [],
    ) {
        $this->date = new \DateTimeImmutable($date, new \DateTimeZone($timezone));

        if ([] === $fileList) {
            throw new \InvalidArgumentException('Empty fileList parameter');
        }

        $this->files =  $fileList;
    }
}
