<?php

declare(strict_types=1);

namespace Introibo\Ordo\Surface;

use Introibo\Ordo\View\DayPresenter;
use Introibo\Ordo\View\LatinCalendar;
use Introibo\Ordo\View\Palette;

/**
 * The full day view: one day's office as the plugin can state it from the bundled
 * engine — its rank, colour, commemorations, season and calendar context. The same
 * body backs two surfaces: the shareable /ordo/YYYY-MM-DD/ page (a no-JavaScript
 * render) and the modal that the calendar and strip open through JavaScript, so the
 * two never disagree.
 *
 * It renders only what the contract carries. Mass propers and rubric texts are a
 * later milestone (the Missal); until they exist this view does not invent them —
 * the "At Mass" pane appears when there is text to show, not before.
 */
final class DayView
{
    /**
     * The day's content, shared by the pretty page and the modal body. Excludes any
     * outer chrome (the modal bar, the page header) so either caller can wrap it.
     */
    public static function article(DayPresenter $day, Context $context): string
    {
        $html = '<div class="ordo-day">';

        $rankLine = $day->rankLine();
        if ($rankLine !== '') {
            $html .= '<div class="ordo-day__rankline">' . esc_html($rankLine) . '</div>';
        }

        $html .= '<h2 class="ordo-day__feast">' . esc_html($day->feastLatin()) . '</h2>';

        $html .= '<div class="ordo-chips">';
        if ($day->hasClass()) {
            $html .= '<span class="ordo-chip ordo-chip--rank">'
                . '<span class="ordo-chip__label">' . esc_html__('Class', 'ordo') . '</span> '
                . esc_html($day->rankRoman())
                . '</span>';
        }
        $html .= self::colourChip($day);
        $html .= ContextChip::calendarChip($context);
        $html .= '</div>';

        $html .= '<div class="ordo-day__cols">';
        if ($context->showCommemorations()) {
            $html .= self::commemorationsColumn($day);
        }
        $html .= self::officeColumn($day, $context);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * The standalone /ordo/YYYY-MM-DD/ page body: the Latin date over the shared
     * article. This is the no-JavaScript render and the shareable permalink; the
     * theme supplies the surrounding chrome.
     */
    public static function page(DayPresenter $day, Context $context): string
    {
        $eyebrow = LatinCalendar::dateGenitive($day->date()) . ' · ' . LatinCalendar::feria($day->date());

        return '<div class="ordo"><article class="ordo-daypage">'
            . '<div class="ordo-daypage__eyebrow">' . esc_html($eyebrow) . '</div>'
            . self::article($day, $context)
            . '</article></div>';
    }

    /**
     * The empty modal dialog printed once per page that carries a modal trigger. Its
     * bar label, title and body are filled by the day-view script from the REST
     * endpoint; without JavaScript it stays hidden and triggers follow their links.
     */
    public static function modalShell(): string
    {
        return '<div class="ordo">'
            . '<div class="ordo-modal-backdrop" data-ordo-modal hidden>'
            . '<div class="ordo-modal" role="dialog" aria-modal="true"'
            . ' aria-labelledby="ordo-modal-title" tabindex="-1">'
            . '<div class="ordo-modal__bar">'
            . '<span class="ordo-modal__barlabel" data-ordo-barlabel></span>'
            . '<button type="button" class="ordo-modal__close" data-ordo-close'
            . ' aria-label="' . esc_attr__('Close', 'ordo') . '">&#10005;</button>'
            . '</div>'
            . '<h2 id="ordo-modal-title" class="ordo-modal__title" data-ordo-title></h2>'
            . '<div class="ordo-modal__body" data-ordo-body></div>'
            . '<div class="ordo-strip ordo-strip--nav" data-ordo-nav>'
            . '<button type="button" class="ordo-strip__chev ordo-strip__chev--left" data-ordo-prev'
            . ' aria-label="' . esc_attr__('Previous day', 'ordo') . '">&lsaquo;</button>'
            . '<div class="ordo-strip__win"><div class="ordo-strip__track" data-ordo-navtrack></div></div>'
            . '<button type="button" class="ordo-strip__chev ordo-strip__chev--right" data-ordo-next'
            . ' aria-label="' . esc_attr__('Next day', 'ordo') . '">&rsaquo;</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function colourChip(DayPresenter $day): string
    {
        $var = Palette::litVar($day->colour());

        return '<span class="ordo-chip">'
            . '<span class="ordo-chip__dot" style="background:var(' . esc_attr($var) . ')"></span>'
            . esc_html(Palette::label($day->colour()))
            . '</span>';
    }

    private static function commemorationsColumn(DayPresenter $day): string
    {
        $html = '<div class="ordo-day__col">';
        $html .= '<h3 class="ordo-day__h">' . esc_html__('Commemorations', 'ordo') . '</h3>';

        $commemorations = $day->commemorations();
        if ($commemorations === []) {
            $html .= '<p class="ordo-day__none">' . esc_html__('None on this day.', 'ordo') . '</p>';
        } else {
            $html .= '<ul class="ordo-day__commems">';
            foreach ($commemorations as $commemoration) {
                $var = Palette::litVar($commemoration['colour']);
                $html .= '<li>'
                    . '<span class="ordo-chip__dot" style="background:var(' . esc_attr($var) . ')"></span>'
                    . '<span>' . esc_html($commemoration['name']) . '</span>'
                    . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    private static function officeColumn(DayPresenter $day, Context $context): string
    {
        $html = '<div class="ordo-day__col">';
        $html .= '<h3 class="ordo-day__h">' . esc_html__('The office', 'ordo') . '</h3>';
        $html .= '<dl class="ordo-day__facts">';

        $season = $day->seasonName();
        if ($season !== '') {
            $html .= self::fact(esc_html__('Season', 'ordo'), esc_html($season));
        }

        $temporal = $day->temporalName();
        if ($temporal !== '') {
            $html .= self::fact(esc_html__('Feria', 'ordo'), esc_html($temporal));
        }

        $html .= self::fact(esc_html__('Rubrics', 'ordo'), esc_html($context->rubricLabel()));
        $html .= self::fact(esc_html__('Calendar', 'ordo'), esc_html($context->calendarLabel()));

        $html .= '</dl>';
        $html .= '</div>';

        return $html;
    }

    /** One term/definition pair; both parts are already escaped by the caller. */
    private static function fact(string $term, string $value): string
    {
        return '<div class="ordo-day__fact">'
            . '<dt>' . $term . '</dt>'
            . '<dd>' . $value . '</dd>'
            . '</div>';
    }
}
