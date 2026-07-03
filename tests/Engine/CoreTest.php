<?php

declare(strict_types=1);

namespace Introibo\Ordo\Tests\Engine;

use DateTimeImmutable;
use Introibo\Ordo\Engine\Core;
use PHPUnit\Framework\TestCase;

/**
 * The engine boundary resolves liturgical days through the bundled Core with no
 * network (issue #3): a fixed universal-calendar day, and the SSPX overlay changing
 * a day's outcome — proof the vendored engine and its corpus work fully offline.
 */
final class CoreTest extends TestCase
{
    public function testResolvesNativityUnderUniversalCalendar(): void
    {
        $day = (new Core())->day(new DateTimeImmutable('1962-12-25'));

        $celebration = $day['celebration'][0];
        self::assertSame('roman:temporale:christmas:nativity', $celebration['id']);
        self::assertSame(1, $celebration['rankOrdinal']);
        self::assertSame('In Nativitate Domini', $celebration['names']['la']);
    }

    public function testUniversalCalendarRanksPiusXAsThirdClass(): void
    {
        $day = (new Core())->day(new DateTimeImmutable('2026-09-03'));

        self::assertSame('roman:sanctorale:pius-x', $day['celebration'][0]['id']);
        self::assertSame(3, $day['celebration'][0]['rankOrdinal']);
        self::assertNull($day['calendar']);
    }

    public function testSspxOverlayElevatesPiusXToFirstClass(): void
    {
        $day = (new Core())->day(new DateTimeImmutable('2026-09-03'), 'sspx');

        self::assertSame('roman:sanctorale:pius-x', $day['celebration'][0]['id']);
        self::assertSame(1, $day['celebration'][0]['rankOrdinal']);
        self::assertSame('introibo:overlay:roman:sspx', $day['calendar']['particular']['id']);
    }

    public function testBundledCoreVersionIsPinned(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', (new Core())->bundledCoreVersion());
    }
}
