<?php

declare(strict_types=1);

namespace LogParser;

final class LogReaderConfig
{
    /**
     * @readonly
     * @var \DateTimeImmutable
     */
    public $date;

    /** @var LogReaderConfigFile[]
     * @readonly */
    public $files;

    /**
     * @param LogReaderConfigFile[] $fileList
     */
    public function __construct(
        $date = 'now',
        $timezone = 'UTC',
        array $fileList = []
    ) {
        $this->date = new \DateTimeImmutable($date, new \DateTimeZone($timezone));

        if ([] === $fileList) {
            throw new \InvalidArgumentException('Empty fileList parameter');
        }

        $this->files =  $fileList;
    }
}
