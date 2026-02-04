<?php

declare(strict_types=1);

namespace LogParser;

final class FileReader
{
    public private(set) int $readCounter = 0;

    /** @var null|resource */
    private $handle;

    public function __construct(public readonly string $file) {}

    public function __destruct()
    {
        $handle = $this->handle;

        if (null === $handle) {
            return;
        }

        fclose($handle);
    }

    /**
     * @throws FileNotExistsException
     * @throws FileNotReadableException
     */
    #[\NoDiscard()]
    public function read(int $length): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException(\sprintf('Read length must be positive, got %d', $length));
        }

        $handle = $this->open();

        $read = @fread($handle, $length);

        if (false === $read) {
            throw new FileNotReadableException($this->file);
        }

        ++$this->readCounter;

        return $read;
    }

    /**
     * @throws FileNotExistsException
     * @throws FileNotSeekableException
     */
    public function seek(int $position): void
    {
        if ($position < 0) {
            throw new \InvalidArgumentException(\sprintf('Seek position must not be negative, got %d', $position));
        }

        $handle = $this->open();

        $seeked = @fseek($handle, $position);

        if (0 !== $seeked) {
            throw new FileNotSeekableException($this->file);
        }
    }

    /**
     * @throws FileNotExistsException
     * @throws FileNotReadableException
     */
    #[\NoDiscard()]
    public function size(): int
    {
        $size = @filesize($this->file);

        if (false === $size) {
            if (file_exists($this->file)) {
                throw new FileNotReadableException($this->file);
            }

            throw new FileNotExistsException($this->file);
        }

        return $size;
    }

    /**
     * @return resource
     *
     * @throws FileNotExistsException
     */
    private function open()
    {
        $handle = $this->handle;

        if (null !== $handle) {
            return $handle;
        }

        $handle = @fopen($this->file, 'r');

        if (false === $handle) {
            throw new FileNotExistsException($this->file);
        }

        $this->handle = $handle;

        return $handle;
    }
}
