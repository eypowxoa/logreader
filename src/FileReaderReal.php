<?php

namespace LogParser;

final class FileReaderReal extends FileReader
{
    /**
     * @return resource
     *
     * @throws FileNotReadableException
     */
    protected function internalOpen()
    {
        $handle = @fopen($this->path, 'r');

        if (false === $handle) {
            throw new FileNotReadableException($this->path);
        }

        return $handle;
    }
}
