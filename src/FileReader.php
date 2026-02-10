<?php

declare(strict_types=1);

namespace LogParser;

abstract class FileReader
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
     * @throws FileNotReadableException
     */
    public function seek(int $position): void
    {
        if ($position < 0) {
            throw new \InvalidArgumentException(\sprintf('Seek position must not be negative, got %d', $position));
        }

        $this->internalSeek($position, false);
    }

    /**
     * @throws FileNotReadableException
     */
    #[\NoDiscard()]
    public function size(): int
    {
        $position = $this->tell();

        $this->internalSeek(0, true);

        $size = $this->tell();

        $this->seek($position);

        return $size;
    }

    /**
     * @throws FileNotReadableException
     */
    #[\NoDiscard()]
    public function tell(): int
    {
        $handle = $this->open();

        $position = @ftell($handle);

        if (!\is_int($position)) {
            throw new FileNotReadableException($this->path);
        }

        return $position;
    }

    /**
     * @return resource
     *
     * @throws FileNotReadableException
     */
    abstract protected function internalOpen();

    /**
     * @throws FileNotReadableException
     */
    private function internalSeek(int $position, bool $end): void
    {
        $handle = $this->open();

        $seeked = @fseek($handle, $position, $end ? SEEK_END : SEEK_SET);

        if (0 !== $seeked) {
            throw new FileNotReadableException($this->path);
        }
    }

    /**
     * @return resource
     *
     * @throws FileNotReadableException
     */
    private function open()
    {
        $handle = $this->handle;

        if (null !== $handle) {
            return $handle;
        }

        $handle = $this->internalOpen();

        $this->handle = $handle;

        return $handle;
    }
}
