<?php

declare(strict_types=1);

namespace LogParserTests;

use LogParser\Utf8Fixer;
use LogParser\Utf8WrongException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class Utf8FixerTest extends TestCase
{
    /**
     * @param array{int,int,int}|string $expected
     */
    #[DataProvider('provideTrimUtf8Cases')]
    public function testTrimUtf8(string $data, array|string $expected): void
    {
        if (\is_string($expected)) {
            $this->expectExceptionObject(new Utf8WrongException($expected));
        }

        [$offset,$length,$skip] = Utf8Fixer::trimUtf8($data);

        if (\is_array($expected)) {
            $this->assertSame($expected[0], $offset);
            $this->assertSame($expected[1], $length);
            $this->assertSame($expected[2], $skip);
        }
    }

    /**
     * @return iterable<string,array{string,array{int,int,int}|string}>
     */
    public static function provideTrimUtf8Cases(): iterable
    {
        yield 'should do nothing for empty string' => ['', [0, 0, 0]];

        yield 'should do nothing for zero byte string' => ["\x00", [0, 1, 0]];

        yield 'should do nothing for corrct 1 byte string' => ['z', [0, 1, 0]];

        yield 'should do nothing for correct 2 byte string' => ['ё', [0, 2, 0]];

        yield 'should do nothing for correct 3 byte string' => ['⚽', [0, 3, 0]];

        yield 'should do nothing for correct 4 byte string' => ['🎲', [0, 4, 0]];

        yield 'should skip bytes 129-137 at start' => ["\x80\x81\x82\x83\x84\x85\x86\x87\x88", [9, 0, 0]];

        yield 'should skip bytes 138-146 at start' => ["\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91", [9, 0, 0]];

        yield 'should skip bytes 147-155 at start' => ["\x92\x93\x94\x95\x96\x97\x98\x99\x9A", [9, 0, 0]];

        yield 'should skip bytes 156-164 at start' => ["\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3", [9, 0, 0]];

        yield 'should skip bytes 165-173 at start' => ["\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC", [9, 0, 0]];

        yield 'should skip bytes 174-182 at start' => ["\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5", [9, 0, 0]];

        yield 'should skip bytes 183-191 at start' => ["\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE", [9, 0, 0]];

        yield 'should fail when first 10 bytes is bad' => ["\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBE", 'Not an UTF-8'];

        yield 'should return data after skipped bytes at start' => ["\x80ё", [1, 2, 0]];

        yield 'should skip bytes 129-137 at end after 1 byte symbol' => ["z\x80\x81\x82\x83\x84\x85\x86\x87\x88", [0, 1, 9]];

        yield 'should skip bytes 138-146 at end after 1 byte symbol' => ["z\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91", [0, 1, 9]];

        yield 'should skip bytes 147-155 at end after 1 byte symbol' => ["z\x92\x93\x94\x95\x96\x97\x98\x99\x9A", [0, 1, 9]];

        yield 'should skip bytes 156-164 at end after 1 byte symbol' => ["z\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3", [0, 1, 9]];

        yield 'should skip bytes 165-173 at end after 1 byte symbol' => ["z\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC", [0, 1, 9]];

        yield 'should skip bytes 174-182 at end after 1 byte symbol' => ["z\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5", [0, 1, 9]];

        yield 'should skip bytes 183-191 at end after 1 byte symbol' => ["z\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE", [0, 1, 9]];

        yield 'should fail when last 10 bytes is bad after 1 byte symbol' => ["z\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBE", 'Not an UTF-8'];

        yield 'should skip bytes 129-137 at end after 2 byte symbol' => ["ё\x80\x81\x82\x83\x84\x85\x86\x87\x88", [0, 2, 9]];

        yield 'should skip bytes 138-146 at end after 2 byte symbol' => ["ё\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91", [0, 2, 9]];

        yield 'should skip bytes 147-155 at end after 2 byte symbol' => ["ё\x92\x93\x94\x95\x96\x97\x98\x99\x9A", [0, 2, 9]];

        yield 'should skip bytes 156-164 at end after 2 byte symbol' => ["ё\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3", [0, 2, 9]];

        yield 'should skip bytes 165-173 at end after 2 byte symbol' => ["ё\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC", [0, 2, 9]];

        yield 'should skip bytes 174-182 at end after 2 byte symbol' => ["ё\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5", [0, 2, 9]];

        yield 'should skip bytes 183-191 at end after 2 byte symbol' => ["ё\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE", [0, 2, 9]];

        yield 'should fail when last 10 bytes is bad after 2 byte symbol' => ["ё\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBE", 'Not an UTF-8'];

        yield 'should skip bytes 129-137 at end after 3 byte symbol' => ["⚽\x80\x81\x82\x83\x84\x85\x86\x87\x88", [0, 3, 9]];

        yield 'should skip bytes 138-146 at end after 3 byte symbol' => ["⚽\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91", [0, 3, 9]];

        yield 'should skip bytes 147-155 at end after 3 byte symbol' => ["⚽\x92\x93\x94\x95\x96\x97\x98\x99\x9A", [0, 3, 9]];

        yield 'should skip bytes 156-164 at end after 3 byte symbol' => ["⚽\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3", [0, 3, 9]];

        yield 'should skip bytes 165-173 at end after 3 byte symbol' => ["⚽\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC", [0, 3, 9]];

        yield 'should skip bytes 174-182 at end after 3 byte symbol' => ["⚽\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5", [0, 3, 9]];

        yield 'should skip bytes 183-191 at end after 3 byte symbol' => ["⚽\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE", [0, 3, 9]];

        yield 'should fail when last 10 bytes is bad after 3 byte symbol' => ["⚽\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBE", 'Not an UTF-8'];

        yield 'should skip bytes 129-137 at end after 4 byte symbol' => ["🎲\x80\x81\x82\x83\x84\x85\x86\x87\x88", [0, 4, 9]];

        yield 'should skip bytes 138-146 at end after 4 byte symbol' => ["🎲\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91", [0, 4, 9]];

        yield 'should skip bytes 147-155 at end after 4 byte symbol' => ["🎲\x92\x93\x94\x95\x96\x97\x98\x99\x9A", [0, 4, 9]];

        yield 'should skip bytes 156-164 at end after 4 byte symbol' => ["🎲\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3", [0, 4, 9]];

        yield 'should skip bytes 165-173 at end after 4 byte symbol' => ["🎲\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC", [0, 4, 9]];

        yield 'should skip bytes 174-182 at end after 4 byte symbol' => ["🎲\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5", [0, 4, 9]];

        yield 'should skip bytes 183-191 at end after 4 byte symbol' => ["🎲\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE", [0, 4, 9]];

        yield 'should fail when last 10 bytes is bad after 4 byte symbol' => ["🎲\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBE", 'Not an UTF-8'];

        yield 'should skip incomplete 2 byte symbol at end' => ["ё\xD1", [0, 2, 1]];

        yield 'should skip incomplete 3 byte symbol at end' => ["ё\xE2\x9A", [0, 2, 2]];

        yield 'should skip incomplete 4 byte symbol at end' => ["ё\xF0\x9F\x8E", [0, 2, 3]];

        yield 'should fail when middle bytes is bad' => ["z\x80z", 'Not an UTF-8'];

    }
}
