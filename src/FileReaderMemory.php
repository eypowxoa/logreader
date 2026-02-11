<?php

declare(strict_types=1);

namespace LogParser;

final class FileReaderMemory extends FileReader
{
    public function __construct(
        private readonly string $data,
    ) {
        parent::__construct(md5($data));
    }

    /**
     * @return resource
     *
     * @throws FileNotReadableException
     */
    protected function internalOpen()
    {
        /** @var resource $handle */
        $handle = @fopen('php://memory', 'w');

        @fwrite($handle, $this->data);
        @fseek($handle, 0);

        return $handle;
    }
}
