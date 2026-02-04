<?php

declare(strict_types=1);

namespace LogParser;

final class FileNotSeekableException extends FileException
{
    protected string $prefix = 'Not seekable';
}
