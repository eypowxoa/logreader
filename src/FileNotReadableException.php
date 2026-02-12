<?php

declare(strict_types=1);

namespace LogReader;

final class FileNotReadableException extends FileException
{
    protected string $prefix = 'Not readable';
}
