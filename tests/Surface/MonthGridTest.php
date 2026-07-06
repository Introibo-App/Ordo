<?php

declare(strict_types=1);

namespace Directorium\Ordo\Tests\Surface;

use DateTimeImmutable;
use Directorium\Ordo\Engine\Core;
use Directorium\Ordo\Shortcodes;
use Directorium\Ordo\Surface\Context;
use Directorium\Ordo\Surface\MonthGrid;
use PHPUnit\Framework\TestCase;

/**
 * The month grid renders a whole month offline through the bundled engine (Epic #15).
 * These tests assert the Sunday-first grid, its liturgical markings (colour edges,
 * first-class boxes, the today ring), the adjacent-month out-cells, the mobile agenda,
 * and the query-string month navigation — all server-rendered, links intact, no JS.
 */
final class MonthGridTest extends TestCase
{
    private function render(string $calendar, DateTimeImmutable $today): string
    {
        $navUrl = static function (int $year, int $month): string {
            return '/cal?ordo_y=' . $year . '&ordo_m=' . $month;
        };

        return MonthGrid::render(
            new Core(),
            Context::forCalendar($calendar),
            2026,
            9,
            $today,
            [Shortcodes::class, 'dayUrl'],
            $navUrl,
            '/cal'
        );
    }

    public function testHeadNamesMonthAndLatinSeason(): void
    {
        $html = $this->render('universal', new DateTimeImmutable('2000-01-01'));

        self::assertStringContainsString('September', $html);
        self::assertStringContainsString('Tempus post Pentecosten', $html);
    }

    public function testDayOfWeekHeaderIsSundayFirst(): void
    {
        $html = $this->render('universal', new DateTimeImmutable('2000-01-01'));

        self::assertStringContainsString('ordo-cal__dow', $html);
        foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday) {
            self::assertStringContainsString('>' . $weekday . '<', $html);
        }
    }

    public function testGridLinksDaysAndMarksFirstClassUnderSspx(): void
    {
        $html = $this->render('sspx', new DateTimeImmutable('2000-01-01'));

        self::assertStringContainsString('href="https://example.test/ordo/2026-09-03/"', $html);
        self::assertStringContainsString('data-ordo-day="2026-09-03"', $html);
        // St Pius X (3rd) and Seven Sorrows (15th) are raised to first class in the SSPX overlay.
        self::assertStringContainsString('ordo-is-first', $html);
        self::assertStringContainsString('ordo-wl-white', $html);
        self::assertStringContainsString('ordo-wl-green', $html);
        self::assertStringContainsString('ordo-wl-red', $html);
    }

    public function testCarriesOutCellsAgendaAndMonthNavigation(): void
    {
        $html = $this->render('universal', new DateTimeImmutable('2000-01-01'));

        // Adjacent-month days appear as non-interactive out-cells.
        self::assertStringContainsString('ordo-cal__cell--out', $html);
        // The mobile agenda mirrors the month.
        self::assertStringContainsString('ordo-cal__agenda', $html);
        self::assertStringContainsString('class="ordo-cal__ag ', $html);
        // Month navigation steps to August and October.
        self::assertStringContainsString('ordo_m=8', $html);
        self::assertStringContainsString('ordo_m=10', $html);
    }

    public function testRingsTodayWhenInDisplayedMonth(): void
    {
        $html = $this->render('universal', new DateTimeImmutable('2026-09-03'));

        self::assertStringContainsString('ordo-is-today', $html);
        self::assertStringContainsString('aria-current="date"', $html);
        self::assertStringContainsString('Hodie', $html);
    }
}
