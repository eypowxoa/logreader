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
        public int $limit,
        array $fileList,
    ) {
        $this->date = new \DateTimeImmutable($date, new \DateTimeZone($timezone));

        if ([] === $fileList) {
            throw new \InvalidArgumentException('Empty fileList parameter');
        }

        if ($limit <= 0) {
            throw new \InvalidArgumentException('Wrong limit, expected positive integer');
        }

        $this->httpAuth = ('Basic ' . base64_encode($login . ':' . $password));
        $this->files =  $fileList;
    }
}
