<?php

declare(strict_types=1);

namespace Introibo\Ordo\Tests\Surface;

use Introibo\Ordo\Assets;
use Introibo\Ordo\Engine\Core;
use Introibo\Ordo\Shortcodes;
use PHPUnit\Framework\TestCase;

/**
 * The WP-stub render harness (Epic #11): drive the shortcode render callbacks —
 * the same callbacks the blocks use — through the bundled engine with WordPress's
 * escaping, translation and option helpers stubbed, and assert the surfaces render
 * the current day to the approved markup. The configured calendar drives both the
 * engine overlay and the context chip, so the SSPX day resolves to first class and
 * is labelled "SSPX".
 */
final class RenderHarnessTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['__ordo_options'] = [];
        $_GET = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['__ordo_options']);
        $_GET = [];
    }

    private function shortcodes(): Shortcodes
    {
        return new Shortcodes(new Core(), new Assets());
    }

    private function selectCalendar(string $calendar): void
    {
        $GLOBALS['__ordo_options']['ordo_settings'] = ['calendar' => $calendar];
    }

    public function testTodayCardRendersSspxDay(): void
    {
        $this->selectCalendar('sspx');
        $html = $this->shortcodes()->renderToday(['date' => '2026-09-03']);

        self::assertStringContainsString('class="ordo-today"', $html);
        self::assertStringContainsString('S. Pii X Papae Confessoris', $html);
        self::assertStringContainsString('Class</span> I</span>', $html);
        self::assertStringContainsString('var(--ordo-lit-white)', $html);
        self::assertStringContainsString('Time after Pentecost — 14th week', $html);
        self::assertStringContainsString('/ordo/2026-09-03/', $html);
        self::assertStringContainsString('Cal.</span> SSPX</span>', $html);
    }

    public function testTodayCardUniversalCalendarChipAndClass(): void
    {
        $this->selectCalendar('universal');
        $html = $this->shortcodes()->renderToday(['date' => '2026-09-03']);

        self::assertStringContainsString('Class</span> III</span>', $html);
        self::assertStringContainsString('Cal.</span> Universal 1962</span>', $html);
    }

    public function testBlockAttributesProduceIdenticalMarkup(): void
    {
        $this->selectCalendar('sspx');
        $shortcode = $this->shortcodes()->renderToday(['date' => '2026-09-03']);
        // A block passes its own attributes (plus supports like align); the extras
        // must not change the rendered markup.
        $block = $this->shortcodes()->renderToday(['date' => '2026-09-03', 'align' => 'wide']);

        self::assertSame($shortcode, $block);
    }

    public function testStripRendersLinkedWindowCentredOnDate(): void
    {
        $this->selectCalendar('sspx');
        $html = $this->shortcodes()->renderStrip(['date' => '2026-09-03', 'days' => '7']);

        self::assertSame(7, substr_count($html, 'class="ordo-strip__day'));
        self::assertStringContainsString('data-ordo-strip', $html);
        self::assertStringContainsString('/ordo/2026-09-03/', $html); // centre
        self::assertStringContainsString('/ordo/2026-08-31/', $html); // three days before
        self::assertStringContainsString('/ordo/2026-09-06/', $html); // three days after
        self::assertStringContainsString('Cal.</span> SSPX</span>', $html);
    }

    public function testStripMarksTodayWhenCentredOnToday(): void
    {
        $this->selectCalendar('universal');
        // No date attribute → centred on today, so the current date is in the window.
        $html = $this->shortcodes()->renderStrip(['days' => '5']);

        self::assertStringContainsString('is-today', $html);
        self::assertStringContainsString('data-ordo-today="1"', $html);
    }

    public function testStripClampsDayCountToSaneWindow(): void
    {
        $this->selectCalendar('universal');
        $html = $this->shortcodes()->renderStrip(['date' => '2026-09-03', 'days' => '999']);

        self::assertSame(31, substr_count($html, 'class="ordo-strip__day'));
    }

    public function testRibbonVariantRendersOneLine(): void
    {
        $this->selectCalendar('sspx');
        $html = $this->shortcodes()->renderStrip(['date' => '2026-09-03', 'variant' => 'ribbon']);

        self::assertStringContainsString('class="ordo-ribbon"', $html);
        self::assertStringContainsString('S. Pii X Papae Confessoris', $html);
        self::assertStringContainsString('I classis', $html);
        self::assertStringContainsString('Feria V · 3 Septembris mmxxvi', $html);
        self::assertStringContainsString('var(--ordo-on-white)', $html);
    }

    public function testCalendarShortcodeRendersMonthFromAttributes(): void
    {
        $this->selectCalendar('sspx');
        $html = $this->shortcodes()->renderCalendar(['year' => '2026', 'month' => '9']);

        self::assertStringContainsString('class="ordo-cal"', $html);
        self::assertStringContainsString('September', $html);
        self::assertStringContainsString('/ordo/2026-09-03/', $html);
        self::assertStringContainsString('data-ordo-day="2026-09-03"', $html);
        self::assertStringContainsString('ordo-is-first', $html);
    }

    public function testCalendarShortcodeQueryStringOverridesAttributes(): void
    {
        $this->selectCalendar('universal');
        $_GET = ['ordo_y' => '2026', 'ordo_m' => '12'];
        $html = $this->shortcodes()->renderCalendar(['year' => '2026', 'month' => '9']);

        // The query string wins: December is shown, not the September attribute.
        self::assertStringContainsString('December', $html);
        self::assertStringContainsString('/ordo/2026-12-25/', $html);
    }

    public function testOutputEscapesAndCarriesNoRawScript(): void
    {
        $this->selectCalendar('sspx');
        $html = $this->shortcodes()->renderToday(['date' => '2026-09-03']);

        self::assertStringNotContainsString('<script', $html);
    }
}
