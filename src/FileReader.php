<?php

declare(strict_types=1);

namespace LogParser;

final class FileReader
{
    public private(set) int $readCounter = 0;

    /** @var null|resource */
    private $handle;

    public function __construct(public readonly string $path) {}

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
            throw new FileNotReadableException($this->path);
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
            throw new FileNotSeekableException($this->path);
        }
    }

    /**
     * @throws FileNotExistsException
     * @throws FileNotReadableException
     */
    #[\NoDiscard()]
    public function size(): int
    {
        $size = @filesize($this->path);

        if (false === $size) {
            if (file_exists($this->path)) {
                throw new FileNotReadableException($this->path);
            }

            throw new FileNotExistsException($this->path);
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

        $handle = @fopen($this->path, 'r');

        if (false === $handle) {
            throw new FileNotExistsException($this->path);
        }

        $this->handle = $handle;

        return $handle;
    }
}
