<?php

declare(strict_types=1);

namespace LogParser;

final class FileNotReadableException extends FileException
{
    /**
     * @var string
     */
    protected $prefix = 'Not readable';
}
