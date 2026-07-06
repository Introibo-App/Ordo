<?php

declare(strict_types=1);

namespace Directorium\Ordo\Surface;

use Directorium\Ordo\View\DayPresenter;
use Directorium\Ordo\View\Palette;

/**
 * The [ordo_today] card — an embeddable summary of a liturgical day: its Latin
 * title, class, colour, season and the active context, with a link into the day
 * view. Rendered to the approved Illuminated-Breviary tokens. Every dynamic value
 * is escaped on output.
 */
final class TodayCard
{
    /**
     * @param DayPresenter $day     the day to summarise
     * @param Context      $context the active rubric + calendar
     * @param bool         $isToday whether the day is the actual current date
     * @param string       $dayUrl  the permalink to the day's full view
     */
    public static function render(DayPresenter $day, Context $context, bool $isToday, string $dayUrl): string
    {
        $eyebrow = $isToday ? __('Today · Hodie', 'ordo') : $day->date()->format('l');

        $html = '<div class="ordo"><div class="ordo-today">';
        $html .= '<div class="ordo-today__eyebrow">' . esc_html($eyebrow) . '</div>';
        $html .= '<div class="ordo-today__date">' . esc_html($day->date()->format('l, j F Y')) . '</div>';
        $html .= '<h3 class="ordo-today__feast">' . esc_html($day->feastLatin()) . '</h3>';

        $html .= '<div class="ordo-chips">';
        if ($day->hasClass()) {
            $html .= '<span class="ordo-chip ordo-chip--rank">'
                . '<span class="ordo-chip__label">' . esc_html__('Class', 'ordo') . '</span> '
                . esc_html($day->rankRoman())
                . '</span>';
        }
        $html .= self::colourChip($day);
        $html .= '</div>';

        $season = $day->seasonName();
        if ($season !== '') {
            $html .= '<div class="ordo-today__season">'
                . esc_html__('Season', 'ordo') . ' · <b>' . esc_html($season) . '</b>'
                . '</div>';
        }

        $html .= ContextChip::render($context);

        $html .= '<a class="ordo-today__link" href="' . esc_url($dayUrl) . '"'
            . ' data-ordo-day="' . esc_attr($day->isoDate()) . '">'
            . esc_html__('Open the day', 'ordo')
            . ' <span class="ordo-today__arrow" aria-hidden="true">&rarr;</span>'
            . '</a>';

        $html .= '</div></div>';

        return $html;
    }

    private static function colourChip(DayPresenter $day): string
    {
        $var = Palette::litVar($day->colour());

        return '<span class="ordo-chip">'
            . '<span class="ordo-chip__dot" style="background:var(' . esc_attr($var) . ')"></span>'
            . esc_html(Palette::label($day->colour()))
            . '</span>';
    }
}
