<?php

declare(strict_types=1);

namespace LogParserTests;

use Carbon\CarbonImmutable;
use LogParser\DateReader;
use LogParser\DateWrongException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DateReaderTest extends TestCase
{
    #[DataProvider('provideReadDateCases')]
    public function testReadDate(string $pattern, string $buffer, int $offset, string|\Throwable|null $expectedDate, int $expectedOffset): void
    {
        $dateReader = new DateReader($pattern);
        $dateReader->buffer = $buffer;

        if ($expectedDate instanceof \Throwable) {
            $this->expectExceptionObject($expectedDate);
            $expectedDate = null;
        }

        $dateReader->readDate($offset);
        $this->assertSame($expectedDate, $dateReader->date?->format('Y-m-d H:i:s.u'));
        $this->assertSame($expectedOffset, $dateReader->offset);
    }

    /**
     * @return iterable<string,array{string,string,int,null|string|\Throwable,int}>
     */
    public static function provideReadDateCases(): iterable
    {
        $b = <<<'BUFFER'
            [2001-01-01 01:01:01.00001] A
            [2002-02-02 02:02:02.00002] B
            [2003-03-03 03:03:03.00003] C
            BUFFER;

        $p = '~\[(?<year>[^-]{1,5})\-(?<month>[^-]{1,5})\-(?<day>[^-]{1,3}) (?<hour>[^:]{1,3}):(?<minute>[^:]{1,3}):(?<second>[^\.]{1,3})(?:\.(?<microsecond>[^\]]{0,7}))?\]~';

        yield 'should fail if empty pattern' => ['', $b, 0, new \InvalidArgumentException('Bad pattern'), 0];

        yield 'should fail if bad pattern one' => ['~', $b, 0, new \InvalidArgumentException('Bad pattern ~'), 0];

        yield 'should fail if bad pattern two' => ['~[~', $b, 0, new \InvalidArgumentException('Bad pattern ~[~'), 0];

        yield 'should fail if no groups' => ['~\[[^\]]+\]~', $b, 0, new DateWrongException('Empty date [2001-01-01 01:01:01.00001] at 0'), 0];

        yield 'should fail if zero date' => [$p, '[1-1-1 0:0:0.0]', 0, new DateWrongException('Empty date [1-1-1 0:0:0.0] at 0'), 0];

        // offset tests

        yield 'should fail if negative offset' => [$p, $b, -1, new \InvalidArgumentException(\sprintf('Bad offset -1, expected integer from 0 to %d', PHP_INT_MAX)), -1];

        yield 'should find at the start' => [$p, $b, 0, '2001-01-01 01:01:01.000001', 27];

        yield 'should not find at the start with offset' => [$p, $b, 1, null, 1];

        yield 'should find at the middle' => [$p, $b, 30, '2002-02-02 02:02:02.000002', 57];

        yield 'should not find at the middle with offset' => [$p, $b, 31, null, 31];

        yield 'should find at the end' => [$p, $b, 60, '2003-03-03 03:03:03.000003', 87];

        yield 'should not find at the end with offset' => [$p, $b, 61, null, 61];

        // year tests

        yield 'year should be one by default' => [str_replace('?<year>', '', $p), '[9-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should fail if year not a number' => [$p, '[x-1-1 0:0:0.1]', 0, new DateWrongException('Wrong year x at 1, expected integer from 1 to 9999'), 0];

        yield 'should fail if year too small' => [$p, '[0-1-1 0:0:0.1]', 0, new DateWrongException('Wrong year 0 at 1, expected integer from 1 to 9999'), 0];

        yield 'should parse minimal year' => [$p, '[1-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should parse year with leading zero' => [$p, '[01-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 16];

        yield 'should parse maximal year' => [$p, '[9999-1-1 0:0:0.1]', 0, '9999-01-01 00:00:00.000001', 18];

        yield 'should fail if year too big' => [$p, '[10000-1-1 0:0:0.1]', 0, new DateWrongException('Wrong year 10000 at 1, expected integer from 1 to 9999'), 0];

        // month tests

        yield 'month should be one by default' => [str_replace('?<month>', '', $p), '[1-9-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should fail if month not a number' => [$p, '[1-x-1 0:0:0.1]', 0, new DateWrongException('Wrong month x at 3, expected integer from 1 to 12'), 0];

        yield 'should fail if month too small' => [$p, '[1-0-1 0:0:0.1]', 0, new DateWrongException('Wrong month 0 at 3, expected integer from 1 to 12'), 0];

        yield 'should parse minimal month' => [$p, '[1-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should parse month with leading zero' => [$p, '[1-01-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 16];

        yield 'should parse maximal month' => [$p, '[1-12-1 0:0:0.1]', 0, '0001-12-01 00:00:00.000001', 16];

        yield 'should fail if month too big' => [$p, '[1-13-1 0:0:0.1]', 0, new DateWrongException('Wrong month 13 at 3, expected integer from 1 to 12'), 0];

        // month names tests

        yield 'should parse month jan lower' => [$p, '[1-xjanx-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 19];

        yield 'should parse month jan upper' => [$p, '[1-xJANx-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 19];

        yield 'should parse month feb lower' => [$p, '[1-xfebx-1 0:0:0.1]', 0, '0001-02-01 00:00:00.000001', 19];

        yield 'should parse month feb upper' => [$p, '[1-xFEBx-1 0:0:0.1]', 0, '0001-02-01 00:00:00.000001', 19];

        yield 'should parse month mar lower' => [$p, '[1-xmarx-1 0:0:0.1]', 0, '0001-03-01 00:00:00.000001', 19];

        yield 'should parse month MAR upper' => [$p, '[1-xMARx-1 0:0:0.1]', 0, '0001-03-01 00:00:00.000001', 19];

        yield 'should parse month apr lower' => [$p, '[1-xaprx-1 0:0:0.1]', 0, '0001-04-01 00:00:00.000001', 19];

        yield 'should parse month APR upper' => [$p, '[1-xAPRx-1 0:0:0.1]', 0, '0001-04-01 00:00:00.000001', 19];

        yield 'should parse month may lower' => [$p, '[1-xmayx-1 0:0:0.1]', 0, '0001-05-01 00:00:00.000001', 19];

        yield 'should parse month MAY upper' => [$p, '[1-xMAYx-1 0:0:0.1]', 0, '0001-05-01 00:00:00.000001', 19];

        yield 'should parse month jun lower' => [$p, '[1-xjunx-1 0:0:0.1]', 0, '0001-06-01 00:00:00.000001', 19];

        yield 'should parse month JUN upper' => [$p, '[1-xJUNx-1 0:0:0.1]', 0, '0001-06-01 00:00:00.000001', 19];

        yield 'should parse month jul lower' => [$p, '[1-xjulx-1 0:0:0.1]', 0, '0001-07-01 00:00:00.000001', 19];

        yield 'should parse month JUL upper' => [$p, '[1-xJULx-1 0:0:0.1]', 0, '0001-07-01 00:00:00.000001', 19];

        yield 'should parse month aug lower' => [$p, '[1-xaugx-1 0:0:0.1]', 0, '0001-08-01 00:00:00.000001', 19];

        yield 'should parse month AUG upper' => [$p, '[1-xAUGx-1 0:0:0.1]', 0, '0001-08-01 00:00:00.000001', 19];

        yield 'should parse month sep lower' => [$p, '[1-xsepx-1 0:0:0.1]', 0, '0001-09-01 00:00:00.000001', 19];

        yield 'should parse month SEP upper' => [$p, '[1-xSEPx-1 0:0:0.1]', 0, '0001-09-01 00:00:00.000001', 19];

        yield 'should parse month oct lower' => [$p, '[1-xoctx-1 0:0:0.1]', 0, '0001-10-01 00:00:00.000001', 19];

        yield 'should parse month OCT upper' => [$p, '[1-xOCTx-1 0:0:0.1]', 0, '0001-10-01 00:00:00.000001', 19];

        yield 'should parse month nov lower' => [$p, '[1-xnovx-1 0:0:0.1]', 0, '0001-11-01 00:00:00.000001', 19];

        yield 'should parse month NOV upper' => [$p, '[1-xNOVx-1 0:0:0.1]', 0, '0001-11-01 00:00:00.000001', 19];

        yield 'should parse month dec lower' => [$p, '[1-xdecx-1 0:0:0.1]', 0, '0001-12-01 00:00:00.000001', 19];

        yield 'should parse month DEC upper' => [$p, '[1-xDECx-1 0:0:0.1]', 0, '0001-12-01 00:00:00.000001', 19];

        // day tests

        yield 'day should be one by default' => [str_replace('?<day>', '', $p), '[1-1-9 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should fail if day not a number' => [$p, '[1-1-x 0:0:0.1]', 0, new DateWrongException('Wrong day x at 5, expected integer from 1 to 31'), 0];

        yield 'should fail if day too small' => [$p, '[1-1-0 0:0:0.1]', 0, new DateWrongException('Wrong day 0 at 5, expected integer from 1 to 31'), 0];

        yield 'should parse minimal day' => [$p, '[1-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should parse day with leading zero' => [$p, '[1-1-01 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 16];

        yield 'should parse maximal day' => [$p, '[1-1-31 0:0:0.1]', 0, '0001-01-31 00:00:00.000001', 16];

        yield 'should fail if day too big' => [$p, '[1-1-32 0:0:0.1]', 0, new DateWrongException('Wrong day 32 at 5, expected integer from 1 to 31'), 0];

        // hour tests

        yield 'hour should be zero by default' => [str_replace('?<hour>', '', $p), '[1-1-1 9:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should fail if hour not a number' => [$p, '[1-1-1 x:0:0.1]', 0, new DateWrongException('Wrong hour x at 7, expected integer from 0 to 23'), 0];

        yield 'should fail if hour too small' => [$p, '[1-1-1 -1:0:0.1]', 0, new DateWrongException('Wrong hour -1 at 7, expected integer from 0 to 23'), 0];

        yield 'should parse minimal hour' => [$p, '[1-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should parse hour with leading zero' => [$p, '[1-1-1 00:0:0.1]', 0, '0001-01-01 00:00:00.000001', 16];

        yield 'should parse maximal hour' => [$p, '[1-1-1 23:0:0.1]', 0, '0001-01-01 23:00:00.000001', 16];

        yield 'should fail if hour too big' => [$p, '[1-1-1 24:0:0.1]', 0, new DateWrongException('Wrong hour 24 at 7, expected integer from 0 to 23'), 0];

        // minute tests

        yield 'minute should be zero by default' => [str_replace('?<minute>', '', $p), '[1-1-1 0:9:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should fail if minute not a number' => [$p, '[1-1-1 0:x:0.1]', 0, new DateWrongException('Wrong minute x at 9, expected integer from 0 to 59'), 0];

        yield 'should fail if minute too small' => [$p, '[1-1-1 0:-1:0.1]', 0, new DateWrongException('Wrong minute -1 at 9, expected integer from 0 to 59'), 0];

        yield 'should parse minimal minute' => [$p, '[1-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should parse minute with leading zero' => [$p, '[1-1-1 0:00:0.1]', 0, '0001-01-01 00:00:00.000001', 16];

        yield 'should parse maximal minute' => [$p, '[1-1-1 0:59:0.1]', 0, '0001-01-01 00:59:00.000001', 16];

        yield 'should fail if minute too big' => [$p, '[1-1-1 0:60:0.1]', 0, new DateWrongException('Wrong minute 60 at 9, expected integer from 0 to 59'), 0];

        // second tests

        yield 'second should be zero by default' => [str_replace('?<second>', '', $p), '[1-1-1 0:0:9.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should fail if second not a number' => [$p, '[1-1-1 0:0:x.1]', 0, new DateWrongException('Wrong second x at 11, expected integer from 0 to 59'), 0];

        yield 'should fail if second too small' => [$p, '[1-1-1 0:0:-1.1]', 0, new DateWrongException('Wrong second -1 at 11, expected integer from 0 to 59'), 0];

        yield 'should parse minimal second' => [$p, '[1-1-1 0:0:0.1]', 0, '0001-01-01 00:00:00.000001', 15];

        yield 'should parse second with leading zero' => [$p, '[1-1-1 0:0:00.1]', 0, '0001-01-01 00:00:00.000001', 16];

        yield 'should parse maximal second' => [$p, '[1-1-1 0:0:59.1]', 0, '0001-01-01 00:00:59.000001', 16];

        yield 'should fail if second too big' => [$p, '[1-1-1 0:0:60.1]', 0, new DateWrongException('Wrong second 60 at 11, expected integer from 0 to 59'), 0];

        // microsecond tests

        yield 'microsecond should be zero by default' => [str_replace('?<microsecond>', '', $p), '[1-1-1 0:0:1.9]', 0, '0001-01-01 00:00:01.000000', 15];

        yield 'should fail if microsecond not a number' => [$p, '[1-1-1 0:0:1.x]', 0, new DateWrongException('Wrong microsecond x at 13, expected integer from 0 to 999999'), 0];

        yield 'should fail if microsecond too small' => [$p, '[1-1-1 0:0:1.-1]', 0, new DateWrongException('Wrong microsecond -1 at 13, expected integer from 0 to 999999'), 0];

        yield 'should parse minimal microsecond' => [$p, '[1-1-1 0:0:1.0]', 0, '0001-01-01 00:00:01.000000', 15];

        yield 'should parse microsecond with leading zero' => [$p, '[1-1-1 0:0:1.00]', 0, '0001-01-01 00:00:01.000000', 16];

        yield 'should parse maximal microsecond' => [$p, '[1-1-1 0:0:1.999999]', 0, '0001-01-01 00:00:01.999999', 20];

        yield 'should fail if microsecond too big' => [$p, '[1-1-1 0:0:1.1000000]', 0, new DateWrongException('Wrong microsecond 1000000 at 13, expected integer from 0 to 999999'), 0];
    }

    public function testReadDateShouldUseTimezone(): void
    {
        $dateReader = new DateReader('~(?<year>\d{4})\-(?<month>\d{2})\-(?<day>\d{2}) (?<hour>\d{2}):(?<minute>\d{2}):(?<second>\d{2})~', new \DateTimeZone('Africa/Tunis'));
        $dateReader->buffer = '2001-01-01 12:00:00';
        $dateReader->readDate(0);

        $date = $dateReader->date;

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertSame(CarbonImmutable::parse('2001-01-01 12:00:00', 'Africa/Tunis')->getTimestamp(), $date->getTimestamp());
    }
}
