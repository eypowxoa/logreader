<?php

declare(strict_types=1);

namespace LogReader;

final readonly class LogReaderConfig
{
    public \DateTimeImmutable $date;

    /** @var LogReaderConfigFile[] */
    public array $files;

    public string $httpAuth;

    /**
     * @param LogReaderConfigFile[] $fileList
     * @param MultilogPeriod[]      $periodList
     * @param FilterVariant[]       $variantList
     */
    public function __construct(
        string $date,
        string $timezone,
        public string $login,
        public string $password,
        public int $limit,
        array $fileList,
        public array $periodList = [
            MultilogPeriod::MINUTE,
            MultilogPeriod::HOUR,
            MultilogPeriod::DAY,
            MultilogPeriod::WEEK,
            MultilogPeriod::MONTH,
        ],
        public array $variantList = [
            FilterVariant::VARIANT_1,
        ],
    ) {
        $this->date = new \DateTimeImmutable($date, new \DateTimeZone($timezone));

        if ([] === $fileList) {
            throw new \InvalidArgumentException('Empty fileList parameter');
        }

        if ([] === $periodList) {
            throw new \InvalidArgumentException('Empty periodList parameter');
        }

        if ([] === $variantList) {
            throw new \InvalidArgumentException('Empty variantList parameter');
        }

        if ($limit <= 0) {
            throw new \InvalidArgumentException('Wrong limit, expected positive integer');
        }

        $this->httpAuth = ('Basic ' . base64_encode($login . ':' . $password));
        $this->files =  $fileList;
    }

    public function hasPeriod(MultilogPeriod $multilogPeriod): bool
    {
        return \in_array($multilogPeriod, $this->periodList, true);
    }

    public function hasVariant(FilterVariant $filterVariant): bool
    {
        return \in_array($filterVariant, $this->variantList, true);
    }
}
