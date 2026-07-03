<?php

declare(strict_types=1);

namespace Introibo\Ordo\Tests\View;

use DateTimeImmutable;
use Introibo\Ordo\Engine\Core;
use Introibo\Ordo\View\DayPresenter;
use PHPUnit\Framework\TestCase;

/**
 * The presenter is the one place that interprets the engine contract. These tests
 * read real days through the bundled engine offline, proving the mapping of the
 * winning celebration (name, class, colour) and the season — including the SSPX
 * overlay raising St Pius X from third to first class.
 */
final class DayPresenterTest extends TestCase
{
    public function testMapsSspxPiusXAsFirstClass(): void
    {
        $day = new DayPresenter((new Core())->day(new DateTimeImmutable('2026-09-03'), 'sspx'));

        self::assertSame('2026-09-03', $day->isoDate());
        self::assertSame('S. Pii X Papae Confessoris', $day->feastLatin());
        self::assertSame('I', $day->rankRoman());
        self::assertSame(1, $day->rankOrdinal());
        self::assertTrue($day->hasClass());
        self::assertSame('white', $day->colour());
        self::assertSame('Time after Pentecost — 14th week', $day->seasonName());
    }

    public function testUniversalCalendarKeepsPiusXThirdClass(): void
    {
        $day = new DayPresenter((new Core())->day(new DateTimeImmutable('2026-09-03')));

        self::assertSame('III', $day->rankRoman());
        self::assertSame(3, $day->rankOrdinal());
        self::assertTrue($day->hasClass());
    }

    public function testFeriaHasNoClassAndGreenColour(): void
    {
        $day = new DayPresenter((new Core())->day(new DateTimeImmutable('2026-09-04')));

        self::assertSame(4, $day->rankOrdinal());
        self::assertFalse($day->hasClass());
        self::assertSame('green', $day->colour());
        self::assertSame('Time after Pentecost — 14th week', $day->seasonName());
    }

    public function testNativityMapsNameColourAndSeason(): void
    {
        $day = new DayPresenter((new Core())->day(new DateTimeImmutable('1962-12-25')));

        self::assertSame('In Nativitate Domini', $day->feastLatin());
        self::assertSame('I', $day->rankRoman());
        self::assertSame('white', $day->colour());
        self::assertSame('Christmastide', $day->seasonName());
    }
}
