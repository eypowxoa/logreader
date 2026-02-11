<?php

declare(strict_types=1);

namespace LogParser;

final class LogReaderConfigFile
{
    /**
     * @readonly
     * @var string
     */
    public $filePath;
    /**
     * @readonly
     * @var string
     */
    public $datePattern;
    /**
     * @readonly
     * @var \Closure
     */
    public $filterFunction;

    public function __construct(
        $filePath,
        $datePattern,
        \Closure $filterFunction = null
    ) {
        $this->filePath = $filePath;
        $this->datePattern = $datePattern;
        if ('' === $filePath) {
            throw new \InvalidArgumentException('Empty filePath parameter');
        }

        if ('' === $datePattern) {
            throw new \InvalidArgumentException('Empty datePattern parameter');
        }

        $this->filterFunction = ($filterFunction ?: (static function (Record $record) {
            return true;
        }));
    }
}
