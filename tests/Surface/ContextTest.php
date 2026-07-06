<?php

declare(strict_types=1);

namespace Directorium\Ordo\Tests\Surface;

use Directorium\Ordo\Surface\Context;
use PHPUnit\Framework\TestCase;

/**
 * The site context reads one stored settings array (Epic #20): the calendar drives the
 * engine overlay and labels the chip; the palette and the show-commemorations flag are
 * display preferences the surfaces honour. A pre-0.1 install (no flag stored) keeps the
 * shipped behaviour of showing commemorations.
 */
final class ContextTest extends TestCase
{
    protected function tearDown(): void
    {
        $GLOBALS['__ordo_options'] = [];
    }

    public function testCurrentReadsCalendarPaletteAndDisplayFlag(): void
    {
        $GLOBALS['__ordo_options']['ordo_settings'] = [
            'calendar' => 'sspx',
            'palette' => 'slate',
            'show_commemorations' => false,
        ];

        $context = Context::current();

        self::assertSame('sspx', $context->engineCalendar());
        self::assertSame('SSPX', $context->calendarLabel());
        self::assertSame('slate', $context->skin());
        self::assertFalse($context->showCommemorations());
    }

    public function testDefaultsWhenNothingStored(): void
    {
        $context = Context::current();

        self::assertNull($context->engineCalendar());
        self::assertSame('Universal 1962', $context->calendarLabel());
        self::assertSame('illuminated', $context->skin());
        // Absent flag means the shipped default: commemorations shown.
        self::assertTrue($context->showCommemorations());
    }

    public function testUnknownValuesFallBackSafely(): void
    {
        $GLOBALS['__ordo_options']['ordo_settings'] = [
            'calendar' => 'byzantine',
            'palette' => 'neon',
        ];

        $context = Context::current();

        self::assertSame('Universal 1962', $context->calendarLabel());
        self::assertNull($context->engineCalendar());
        self::assertSame('illuminated', $context->skin());
    }

    public function testForCalendarKeepsShowCommemorationsOn(): void
    {
        $context = Context::forCalendar('fssp');

        self::assertSame('fssp', $context->engineCalendar());
        self::assertSame('FSSP', $context->calendarLabel());
        self::assertTrue($context->showCommemorations());
    }
}
