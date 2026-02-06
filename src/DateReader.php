<?php

declare(strict_types=1);

namespace LogParser;

final class DateReader
{
    private const string KEY_DAY = 'day';

    private const string KEY_HOUR = 'hour';

    private const string KEY_MICROSECOND = 'microsecond';

    private const string KEY_MINUTE = 'minute';

    private const string KEY_MONTH = 'month';

    private const string KEY_SECOND = 'second';

    private const string KEY_YEAR = 'year';

    private const string UTF_8 = 'UTF-8';

    public string $buffer = '';

    public private(set) ?\DateTimeImmutable $date = null;

    public private(set) int $offset = 0;

    public function __construct(private readonly string $pattern) {}

    public function readDate(int $offset): void
    {
        $this->date = null;
        $this->offset = $offset;

        if ($offset < 0) {
            throw new \InvalidArgumentException(\sprintf('Bad offset %d, expected integer from 0 to %d', $offset, PHP_INT_MAX));
        }

        /** @var array<int|string,array{string,int}> $match */
        $match = [];

        $pattern = $this->pattern . 'Anu';

        $matchResult = @preg_match($pattern, $this->buffer, $match, PREG_OFFSET_CAPTURE, $offset);

        if (false === $matchResult) {
            throw new \InvalidArgumentException(\sprintf('Bad pattern %s', $this->pattern));
        }

        if (1 !== $matchResult) {
            return;
        }

        /** @var array<int|string,array{string,int<-1,max>}> $match */
        $year = $this->validateInt($match[self::KEY_YEAR][0] ?? null, 'year', 1, 9999, $match[self::KEY_YEAR][1] ?? 0) ?? 1;
        $month = $this->validateMonth($match[self::KEY_MONTH][0] ?? null, $match[self::KEY_MONTH][1] ?? 0) ?? 1;
        $day = $this->validateInt($match[self::KEY_DAY][0] ?? null, 'day', 1, 31, $match[self::KEY_DAY][1] ?? 0) ?? 1;
        $hour = $this->validateInt($match[self::KEY_HOUR][0] ?? null, 'hour', 0, 23, $match[self::KEY_HOUR][1] ?? 0) ?? 0;
        $minute = $this->validateInt($match[self::KEY_MINUTE][0] ?? null, 'minute', 0, 59, $match[self::KEY_MINUTE][1] ?? 0) ?? 0;
        $second = $this->validateInt($match[self::KEY_SECOND][0] ?? null, 'second', 0, 59, $match[self::KEY_SECOND][1] ?? 0) ?? 0;
        $microsecond = $this->validateInt($match[self::KEY_MICROSECOND][0] ?? null, 'microsecond', 0, 999999, $match[self::KEY_MICROSECOND][1] ?? 0) ?? 0;

        if ((1 === $year) && (1 === $month) && (1 === $day) && (0 === $hour) && (0 === $minute) && (0 === $second) && (0 === $microsecond)) {
            throw new DateWrongException(\sprintf('Empty date %s at %d', $match[0][0] ?? '', $offset));
        }

        $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $date = $date->setDate($year, $month, $day);
        $date = $date->setTime($hour, $minute, $second, $microsecond);

        $this->date = $date;
        $this->offset += mb_strlen($match[0][0], '8bit');
    }

    private function validateInt(?string $value, string $period, int $minimum, int $maximum, int $offset): ?int
    {
        if (null === $value) {
            return null;
        }

        $result = trim($value);

        $result = ((1 === preg_match('~^0+$~', $result)) ? '0' : ltrim($result, '0'));

        $result = filter_var($result, FILTER_VALIDATE_INT);

        if ((!\is_int($result)) || ($result < $minimum) || ($maximum < $result)) {
            throw new DateWrongException(\sprintf('Wrong %s %s at %d, expected integer from %d to %d', $period, $value, $offset, $minimum, $maximum));
        }

        return $result;
    }

    private function validateMonth(?string $value, int $offset): ?int
    {
        if (null === $value) {
            return null;
        }

        try {
            return $this->validateInt($value, 'month', 1, 12, $offset);
        } catch (DateWrongException $dateWrongException) {
            $error = $dateWrongException;
        }

        $normalized = mb_strtoupper($value, self::UTF_8);

        $monthNameList = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

        foreach ($monthNameList as $index => $name) {
            if (false !== mb_strpos($normalized, $name, 0, self::UTF_8)) {
                return $index + 1;
            }
        }

        throw $error;
    }
}
