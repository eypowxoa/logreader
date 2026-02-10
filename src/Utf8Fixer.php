<?php

declare(strict_types=1);

namespace LogParser;

final readonly class Utf8Fixer
{
    private const string BYTE_ENCODING = '8bit';

    private const int MAXIMAL_OFFSET = 10;

    /**
     * @return array{int,int,int}
     *
     * @throws Utf8WrongException
     */
    #[\NoDiscard()]
    public static function trimUtf8(string $data): array
    {
        $length = mb_strlen($data, self::BYTE_ENCODING);

        $maximalOffsetAtStart = min($length, self::MAXIMAL_OFFSET);

        $offsetAtStart = 0;

        while ($offsetAtStart < $maximalOffsetAtStart) {
            $byteAtStart = mb_ord(mb_substr($data, $offsetAtStart, 1, self::BYTE_ENCODING), self::BYTE_ENCODING);

            if (($byteAtStart & 0b11000000) !== 0b10000000) {
                break;
            }

            ++$offsetAtStart;
        }

        if ($offsetAtStart >= self::MAXIMAL_OFFSET) {
            throw new Utf8WrongException('Not an UTF-8');
        }

        $utf8Length = ($length - $offsetAtStart);
        $minimalUtf8Length = max($utf8Length - 10, 0);
        $skipLength = 0;
        $offsetAtEnd = 0;

        while (($utf8Length - $skipLength) > 0) {
            $byteAtEnd = mb_ord(mb_substr($data, $offsetAtStart + $utf8Length - $skipLength - 1, 1, self::BYTE_ENCODING), self::BYTE_ENCODING);

            if (($byteAtEnd & 0b11000000) !== 0b10000000) {
                if ((($byteAtEnd & 0b10000000) === 0b00000000) && ($skipLength > 0)) {
                    $offsetAtEnd = $skipLength;
                }

                if (($byteAtEnd & 0b11100000) === 0b11000000) {
                    if ($skipLength < 1) {
                        $offsetAtEnd = 1;
                    }

                    if ($skipLength > 1) {
                        $offsetAtEnd = ($skipLength - 1);
                    }
                }

                if (($byteAtEnd & 0b11110000) === 0b11100000) {
                    if ($skipLength < 2) {
                        $offsetAtEnd = ($skipLength + 1);
                    }

                    if ($skipLength > 2) {
                        $offsetAtEnd = ($skipLength - 2);
                    }
                }

                if (($byteAtEnd & 0b11111000) === 0b11110000) {
                    if ($skipLength < 3) {
                        $offsetAtEnd = ($skipLength + 1);
                    }

                    if ($skipLength > 3) {
                        $offsetAtEnd = ($skipLength - 3);
                    }
                }

                $utf8Length -= $offsetAtEnd;

                break;
            }

            ++$skipLength;
        }

        if (($utf8Length <= $minimalUtf8Length) && ($utf8Length > 0)) {
            throw new Utf8WrongException('Not an UTF-8');
        }

        $utf8 = mb_substr($data, $offsetAtStart, $utf8Length, self::BYTE_ENCODING);

        if (!mb_check_encoding($utf8, 'UTF-8')) {
            throw new Utf8WrongException('Not an UTF-8');
        }

        return [$offsetAtStart, $utf8Length, $offsetAtEnd];
    }
}
