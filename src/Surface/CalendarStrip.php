<?php

declare(strict_types=1);

namespace Directorium\Ordo\Surface;

use DateTimeImmutable;
use Directorium\Ordo\Engine\Core;
use Directorium\Ordo\View\DayPresenter;
use Directorium\Ordo\View\LatinCalendar;
use Directorium\Ordo\View\Palette;

/**
 * The masthead surfaces: a rolling week strip that slides a day at a time, and a
 * one-line ribbon naming the day on the site's navy bar. Both read from the
 * bundled engine and render to the approved tokens; each strip day is a link to
 * its own view, so the surface works — and every day stays reachable — without
 * JavaScript.
 */
final class CalendarStrip
{
    /** The widest and narrowest windows the strip will render. */
    private const MIN_DAYS = 3;
    private const MAX_DAYS = 31;

    /**
     * The sliding week strip: `$days` cells centred on `$centre`, each a link to
     * that day's view, with the actual current date marked. Chevrons and sliding
     * are progressive enhancement — without JavaScript the window simply scrolls.
     *
     * @param callable(string):string $urlFor maps an ISO date to its day-view URL
     */
    public static function week(
        Core $engine,
        Context $context,
        DateTimeImmutable $centre,
        DateTimeImmutable $today,
        int $days,
        callable $urlFor
    ): string {
        $days = max(self::MIN_DAYS, min(self::MAX_DAYS, $days));
        $before = intdiv($days - 1, 2);
        $calendar = $context->engineCalendar();
        $todayIso = $today->format('Y-m-d');

        $cells = '';
        for ($offset = -$before; $offset < $days - $before; $offset++) {
            $day = new DayPresenter($engine->day(self::shift($centre, $offset), $calendar));
            $iso = $day->isoDate();
            $cells .= self::stripCell($day, (string) $urlFor($iso), $iso === $todayIso);
        }

        return '<div class="ordo">'
            . '<div class="ordo-strip" data-ordo-strip>'
            . '<button type="button" class="ordo-strip__chev ordo-strip__chev--left" data-ordo-left'
            . ' aria-label="' . esc_attr__('Earlier days', 'ordo') . '">&lsaquo;</button>'
            . '<div class="ordo-strip__win" data-ordo-win>'
            . '<div class="ordo-strip__track" data-ordo-track>' . $cells . '</div>'
            . '</div>'
            . '<button type="button" class="ordo-strip__chev ordo-strip__chev--right" data-ordo-right'
            . ' aria-label="' . esc_attr__('Later days', 'ordo') . '">&rsaquo;</button>'
            . '</div>'
            . ContextChip::render($context)
            . '</div>';
    }

    /**
     * The one-line ribbon: feast · class · feria &amp; date, with a brightened
     * colour tab on each edge and a gold-ringed gem — legible on the navy bar.
     */
    public static function ribbon(DayPresenter $day, string $url): string
    {
        $tab = 'background:var(' . esc_attr(Palette::onVar($day->colour())) . ')';
        $when = LatinCalendar::feria($day->date()) . ' · ' . LatinCalendar::dateGenitive($day->date());

        $html = '<div class="ordo"><a class="ordo-ribbon" href="' . esc_url($url) . '"'
            . ' data-ordo-day="' . esc_attr($day->isoDate()) . '">';
        $html .= '<span class="ordo-ribbon__tab ordo-ribbon__tab--left" style="' . $tab . '"></span>';
        $html .= '<span class="ordo-ribbon__tab ordo-ribbon__tab--right" style="' . $tab . '"></span>';
        $html .= '<span class="ordo-ribbon__gem" style="' . $tab . '"></span>';
        $html .= '<b class="ordo-ribbon__feast">' . esc_html($day->feastLatin()) . '</b>';
        if ($day->hasClass()) {
            $html .= '<span class="ordo-ribbon__sep" aria-hidden="true">&#10010;</span>';
            /* translators: %s is a class as a Roman numeral (I, II, III). */
            $html .= '<span class="ordo-ribbon__class">'
                . esc_html(sprintf(__('%s classis', 'ordo'), $day->rankRoman()))
                . '</span>';
        }
        $html .= '<span class="ordo-ribbon__sep" aria-hidden="true">&#10010;</span>';
        $html .= '<span class="ordo-ribbon__when">' . esc_html($when) . '</span>';
        $html .= '</a></div>';

        return $html;
    }

    private static function stripCell(DayPresenter $day, string $url, bool $isToday): string
    {
        $classes = 'ordo-strip__day' . ($isToday ? ' is-today' : '');
        $var = Palette::litVar($day->colour());

        return '<a class="' . esc_attr($classes) . '" href="' . esc_url($url) . '"'
            . ($isToday ? ' aria-current="date"' : '')
            . ' data-ordo-day="' . esc_attr($day->isoDate()) . '"'
            . ' data-ordo-today="' . ($isToday ? '1' : '0') . '">'
            . '<span class="ordo-strip__wd">' . esc_html(strtoupper($day->date()->format('D'))) . '</span>'
            . '<span class="ordo-strip__dd">' . esc_html($day->date()->format('j')) . '</span>'
            . '<span class="ordo-strip__fe" title="' . esc_attr($day->feastLatin()) . '">'
            . esc_html($day->feastLatin()) . '</span>'
            . '<span class="ordo-strip__dot" style="background:var(' . esc_attr($var) . ')"></span>'
            . '</a>';
    }

    private static function shift(DateTimeImmutable $date, int $offset): DateTimeImmutable
    {
        if ($offset === 0) {
            return $date;
        }

        $spec = ($offset > 0 ? '+' : '-') . abs($offset) . ' days';
        $shifted = $date->modify($spec);

        return $shifted instanceof DateTimeImmutable ? $shifted : $date;
    }
}
