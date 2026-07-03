<?php

declare(strict_types=1);

namespace Introibo\Ordo\Surface;

/**
 * The reusable context chip: two small chips naming the active rubric system and
 * particular calendar, so a reader always knows which calendar produced the day
 * in front of them. Shown on both the today card and the masthead strip, styled
 * with the shared chip tokens.
 */
final class ContextChip
{
    /** Render the rubric + calendar chips for a context. */
    public static function render(Context $context): string
    {
        return '<div class="ordo-context">'
            . self::chip(__('Rubric', 'ordo'), $context->rubricLabel())
            . self::chip(_x('Cal.', 'abbreviation of "calendar"', 'ordo'), $context->calendarLabel())
            . '</div>';
    }

    /**
     * Just the calendar chip, for the day view's chip row where it sits beside the
     * class and colour chips rather than in the standalone two-chip context group.
     */
    public static function calendarChip(Context $context): string
    {
        return self::chip(_x('Cal.', 'abbreviation of "calendar"', 'ordo'), $context->calendarLabel());
    }

    private static function chip(string $label, string $value): string
    {
        return '<span class="ordo-chip">'
            . '<span class="ordo-chip__label">' . esc_html($label) . '</span> '
            . esc_html($value)
            . '</span>';
    }
}
