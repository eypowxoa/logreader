<?php

namespace LogParser;

abstract class FileReader
{
    /**
     * @readonly
     * @var string
     */
    public $path;
    /**
     * @var int
     */
    public $readCounter = 0;

    /** @var null|resource */
    private $handle;

    public function __construct($path)
    {
        $this->path = $path;
    }

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
     * @param int $length
     */
    public function read($length)
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
     * @param int $position
     */
    public function seek($position)
    {
        if ($position < 0) {
            throw new \InvalidArgumentException(\sprintf('Seek position must not be negative, got %d', $position));
        }

        $this->internalSeek($position, false);
    }

    /**
     * @throws FileNotReadableException
     */
    public function size()
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
    public function tell()
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
    private function internalSeek($position, $end)
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
