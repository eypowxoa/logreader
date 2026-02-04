<?php

declare(strict_types=1);

namespace LogParser;

final class FileNotReadableException extends FileException
{
    protected string $prefix = 'Not readable';
}
