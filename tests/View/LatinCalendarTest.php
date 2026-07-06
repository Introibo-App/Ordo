<?php

declare(strict_types=1);

namespace Directorium\Ordo\Tests\View;

use DateTimeImmutable;
use Directorium\Ordo\View\LatinCalendar;
use PHPUnit\Framework\TestCase;

/**
 * The Latin date vocabulary the ribbon speaks: the weekday as a feria, the month
 * in the genitive, and the year in lower-case Roman numerals.
 */
final class LatinCalendarTest extends TestCase
{
    public function testFeriaNamesTheWeekday(): void
    {
        self::assertSame('Feria V', LatinCalendar::feria(new DateTimeImmutable('2026-09-03'))); // Thursday
        self::assertSame('Sabbato', LatinCalendar::feria(new DateTimeImmutable('2026-09-05'))); // Saturday
        self::assertSame('Dominica', LatinCalendar::feria(new DateTimeImmutable('2026-09-06'))); // Sunday
        self::assertSame('Feria II', LatinCalendar::feria(new DateTimeImmutable('2026-09-07'))); // Monday
    }

    public function testDateGenitiveWithRomanYear(): void
    {
        self::assertSame('3 Septembris mmxxvi', LatinCalendar::dateGenitive(new DateTimeImmutable('2026-09-03')));
        self::assertSame('1 Ianuarii mmxxvi', LatinCalendar::dateGenitive(new DateTimeImmutable('2026-01-01')));
        self::assertSame('25 Decembris mcmlxii', LatinCalendar::dateGenitive(new DateTimeImmutable('1962-12-25')));
    }

    public function testRomanYear(): void
    {
        self::assertSame('mmxxvi', LatinCalendar::romanYear(2026));
        self::assertSame('mcmlxii', LatinCalendar::romanYear(1962));
        self::assertSame('mdlxxxiii', LatinCalendar::romanYear(1583));
    }
}
