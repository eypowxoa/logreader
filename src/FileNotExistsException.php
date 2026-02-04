<?php

declare(strict_types=1);

namespace LogParser;

final class FileNotExistsException extends FileException
{
    protected string $prefix = 'Not exists';
}
