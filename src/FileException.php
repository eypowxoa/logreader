<?php

declare(strict_types=1);

namespace LogReader;

abstract class FileException extends \Exception implements CheckedException
{
    protected string $prefix = 'Wrong';

    public function __construct(public readonly string $path, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($this->prefix . ' ' . $path, $code, $previous);
    }
}
