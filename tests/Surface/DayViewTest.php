<?php

declare(strict_types=1);

namespace Introibo\Ordo\Tests\Surface;

use DateTimeImmutable;
use Introibo\Ordo\Engine\Core;
use Introibo\Ordo\Surface\Context;
use Introibo\Ordo\Surface\DayView;
use Introibo\Ordo\View\DayPresenter;
use PHPUnit\Framework\TestCase;

/**
 * The day view is the shared body of the pretty page and the modal (Epic #15). These
 * tests render real days offline through the bundled engine and assert the view shows
 * only what the contract carries — the rank line, chips, commemorations and office
 * facts — with no invented Mass text, and that the modal shell is empty but accessible.
 */
final class DayViewTest extends TestCase
{
    private function day(string $iso, ?string $calendar = null): DayPresenter
    {
        return new DayPresenter((new Core())->day(new DateTimeImmutable($iso), $calendar));
    }

    public function testArticleRendersSspxDayWithRankChipsAndOffice(): void
    {
        $html = DayView::article($this->day('2026-09-03', 'sspx'), Context::forCalendar('sspx'));

        self::assertStringContainsString('class="ordo-day"', $html);
        self::assertStringContainsString('First class · Feast', $html);
        self::assertStringContainsString('S. Pii X Papae Confessoris', $html);
        self::assertStringContainsString('Class</span> I</span>', $html);
        self::assertStringContainsString('Cal.</span> SSPX</span>', $html);
        // A first-class feast admits no commemoration.
        self::assertStringContainsString('None on this day.', $html);
        // Office facts carry the season and temporal day.
        self::assertStringContainsString('Time after Pentecost — 14th week', $html);
    }

    public function testArticleListsCommemorations(): void
    {
        $html = DayView::article($this->day('2026-09-16'), Context::forCalendar('universal'));

        self::assertStringContainsString('Commemorations', $html);
        self::assertStringContainsString('Ss. Euphemiae Virginis et Martyris', $html);
    }

    public function testPageWrapsArticleWithLatinDate(): void
    {
        $html = DayView::page($this->day('2026-09-03', 'sspx'), Context::forCalendar('sspx'));

        self::assertStringContainsString('class="ordo-daypage"', $html);
        self::assertStringContainsString('3 Septembris mmxxvi · Feria V', $html);
        self::assertStringContainsString('S. Pii X Papae Confessoris', $html);
    }

    public function testModalShellIsEmptyButAccessible(): void
    {
        $html = DayView::modalShell();

        self::assertStringContainsString('data-ordo-modal', $html);
        self::assertStringContainsString('role="dialog"', $html);
        self::assertStringContainsString('aria-modal="true"', $html);
        self::assertStringContainsString('aria-labelledby="ordo-modal-title"', $html);
        self::assertStringContainsString('data-ordo-navtrack', $html);
        // The shell carries no day content until the script fills it.
        self::assertStringNotContainsString('S. Pii X', $html);
    }

    public function testOutputCarriesNoRawScript(): void
    {
        $html = DayView::article($this->day('2026-09-03', 'sspx'), Context::forCalendar('sspx'));

        self::assertStringNotContainsString('<script', $html);
    }
}
