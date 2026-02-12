<?php

declare(strict_types=1);

namespace LogParser;

final readonly class LogReaderConfig
{
    public \DateTimeImmutable $date;

    /** @var LogReaderConfigFile[] */
    public array $files;

    public string $httpAuth;

    /**
     * @param LogReaderConfigFile[] $fileList
     */
    public function __construct(
        string $date,
        string $timezone,
        public string $login,
        public string $password,
        array $fileList,
    ) {
        $this->date = new \DateTimeImmutable($date, new \DateTimeZone($timezone));

        if ([] === $fileList) {
            throw new \InvalidArgumentException('Empty fileList parameter');
        }

        $this->httpAuth = ('Basic ' . base64_encode($login . ':' . $password));
        $this->files =  $fileList;
    }
}
