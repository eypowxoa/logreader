<?php

declare(strict_types=1);

namespace LogParser;

abstract class FileException extends \Exception implements CheckedException
{
    /**
     * @readonly
     * @var string
     */
    public $path;
    /**
     * @var string
     */
    protected $prefix = 'Wrong';

    public function __construct(string $path, int $code = 0, ?\Throwable $previous = null)
    {
        $this->path = $path;
        parent::__construct($this->prefix . ' ' . $path, $code, $previous);
    }
}
