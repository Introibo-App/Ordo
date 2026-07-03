<?php

declare(strict_types=1);

namespace Introibo\Ordo\Surface;

use DateTimeImmutable;
use Introibo\Ordo\Engine\Core;
use Introibo\Ordo\View\DayPresenter;
use Introibo\Ordo\View\LatinCalendar;
use Introibo\Ordo\View\Palette;

/**
 * The [ordo_calendar] month grid: a Sunday-first calendar of square cells, each a
 * link to its day view, with the leading liturgical colour, class numeral, today
 * ring and first-class gold box the approved design specifies. A corner is left
 * clear for the fasting mark a later engine version will supply.
 *
 * Below a narrow breakpoint the same month is presented as an agenda list; both are
 * server-rendered, so the calendar works — and every day stays reachable — without
 * JavaScript. Month navigation is carried in the page query string (?ordo_y=&
 * ordo_m=), so stepping months is a plain link and each month is a shareable URL.
 */
final class MonthGrid
{
    /** Query vars carrying the displayed year and month (read back by the shortcode). */
    public const QV_YEAR = 'ordo_y';
    public const QV_MONTH = 'ordo_m';

    /** The engine's resolvable range, mirrored from the API's validation. */
    public const MIN_YEAR = 1583;
    public const MAX_YEAR = 2200;

    /**
     * @param callable(string):string   $urlFor maps an ISO date to its day-view URL
     * @param callable(int,int):string  $navUrl maps a (year, month) to the month's URL
     * @param string                    $baseUrl the current page path, for the year form's action
     */
    public static function render(
        Core $engine,
        Context $context,
        int $year,
        int $month,
        DateTimeImmutable $today,
        callable $urlFor,
        callable $navUrl,
        string $baseUrl
    ): string {
        $first = self::firstOfMonth($year, $month);
        $calendar = $context->engineCalendar();
        $todayIso = $today->format('Y-m-d');

        $lead = (int) $first->format('w');
        $daysInMonth = (int) $first->format('t');
        $span = $lead + $daysInMonth;
        $total = $span + ((7 - ($span % 7)) % 7);

        $cursor = self::shift($first, -$lead);
        $cells = [];
        for ($i = 0; $i < $total; $i++) {
            $date = self::shift($cursor, $i);
            $inMonth = (int) $date->format('n') === $month && (int) $date->format('Y') === $year;
            $cells[] = [
                'date' => $date,
                'iso' => $date->format('Y-m-d'),
                'day' => new DayPresenter($engine->day($date, $calendar)),
                'inMonth' => $inMonth,
                'isToday' => $date->format('Y-m-d') === $todayIso,
            ];
        }

        $seasonLatin = LatinCalendar::seasonLatin(self::seasonToken($engine, $context, $year, $month));

        return '<div class="ordo"><div class="ordo-cal" id="ordo-cal">'
            . self::head($year, $month, $first, $seasonLatin, $navUrl, $baseUrl)
            . '<div class="ordo-cal__scroll">' . self::dowRow() . self::grid($cells, $urlFor) . '</div>'
            . self::agenda($cells, $urlFor)
            . '</div></div>';
    }

    private static function seasonToken(Core $engine, Context $context, int $year, int $month): string
    {
        $mid = self::firstOfMonth($year, $month)->modify('+14 days');
        $contract = $engine->day($mid, $context->engineCalendar());

        return is_string($contract['season'] ?? null) ? (string) $contract['season'] : '';
    }

    /**
     * @param callable(int,int):string $navUrl
     */
    private static function head(
        int $year,
        int $month,
        DateTimeImmutable $first,
        string $seasonLatin,
        callable $navUrl,
        string $baseUrl
    ): string {
        [$prevY, $prevM] = self::step($year, $month, -1);
        [$nextY, $nextM] = self::step($year, $month, 1);

        $title = '<div class="ordo-cal__m">' . esc_html(date_i18n('F', $first->getTimestamp()));
        if ($seasonLatin !== '') {
            $title .= '<small>' . esc_html($seasonLatin) . '</small>';
        }
        $title .= '</div>';

        $nav = '<form class="ordo-cal__nav" method="get" action="' . esc_url($baseUrl) . '">'
            . '<a class="ordo-cal__arw" href="' . esc_url((string) $navUrl($prevY, $prevM)) . '"'
            . ' aria-label="' . esc_attr__('Previous month', 'ordo') . '">&lsaquo;</a>'
            . '<input type="hidden" name="' . esc_attr(self::QV_MONTH) . '" value="' . esc_attr((string) $month) . '">'
            . '<input class="ordo-cal__yr" type="number" name="' . esc_attr(self::QV_YEAR) . '"'
            . ' value="' . esc_attr((string) $year) . '"'
            . ' min="' . self::MIN_YEAR . '" max="' . self::MAX_YEAR . '"'
            . ' inputmode="numeric" aria-label="' . esc_attr__('Year', 'ordo') . '">'
            . '<a class="ordo-cal__arw" href="' . esc_url((string) $navUrl($nextY, $nextM)) . '"'
            . ' aria-label="' . esc_attr__('Next month', 'ordo') . '">&rsaquo;</a>'
            . '</form>';

        return '<div class="ordo-cal__head">' . $title . $nav . '</div>';
    }

    private static function dowRow(): string
    {
        $days = [
            esc_html__('Sun', 'ordo'),
            esc_html__('Mon', 'ordo'),
            esc_html__('Tue', 'ordo'),
            esc_html__('Wed', 'ordo'),
            esc_html__('Thu', 'ordo'),
            esc_html__('Fri', 'ordo'),
            esc_html__('Sat', 'ordo'),
        ];

        return '<div class="ordo-cal__dow"><span>' . implode('</span><span>', $days) . '</span></div>';
    }

    /**
     * @param list<array{date: DateTimeImmutable, iso: string, day: DayPresenter, inMonth: bool, isToday: bool}> $cells
     * @param callable(string):string $urlFor
     */
    private static function grid(array $cells, callable $urlFor): string
    {
        $html = '<div class="ordo-cal__grid">';
        foreach ($cells as $cell) {
            $html .= $cell['inMonth']
                ? self::cell($cell['day'], (string) $urlFor($cell['iso']), $cell['iso'], $cell['isToday'])
                : self::outCell($cell['day'], (int) $cell['date']->format('j'));
        }
        $html .= '</div>';

        return $html;
    }

    private static function cell(DayPresenter $day, string $url, string $iso, bool $isToday): string
    {
        $classes = 'ordo-cal__cell ' . self::wlClass($day->colour());
        if ($day->rankOrdinal() === 1) {
            $classes .= ' ordo-is-first';
        }
        if ($isToday) {
            $classes .= ' ordo-is-today';
        }

        $html = '<a class="' . esc_attr($classes) . '" href="' . esc_url($url) . '"'
            . ' data-ordo-day="' . esc_attr($iso) . '"'
            . ($isToday ? ' aria-current="date"' : '') . '>'
            . '<span class="ordo-cal__n">' . esc_html($day->date()->format('j')) . '</span>'
            . '<span class="ordo-cal__rk">' . esc_html($day->rankRoman()) . '</span>'
            . '<span class="ordo-cal__f">' . esc_html(self::shortName($day)) . '</span>';
        /* A corner is reserved here for the fasting/abstinence mark (Core v0.4). */
        if ($isToday) {
            $html .= '<span class="ordo-cal__tag">' . esc_html__('Hodie', 'ordo') . '</span>';
        }
        $html .= '</a>';

        return $html;
    }

    private static function outCell(DayPresenter $day, int $dayNumber): string
    {
        return '<div class="ordo-cal__cell ordo-cal__cell--out" aria-hidden="true">'
            . '<span class="ordo-cal__n">' . esc_html((string) $dayNumber) . '</span>'
            . '<span class="ordo-cal__f">' . esc_html(self::shortName($day)) . '</span>'
            . '</div>';
    }

    /**
     * @param list<array{date: DateTimeImmutable, iso: string, day: DayPresenter, inMonth: bool, isToday: bool}> $cells
     * @param callable(string):string $urlFor
     */
    private static function agenda(array $cells, callable $urlFor): string
    {
        $html = '<div class="ordo-cal__agenda">';
        foreach ($cells as $cell) {
            if (!$cell['inMonth']) {
                continue;
            }
            $day = $cell['day'];
            $classes = 'ordo-cal__ag ' . self::wlClass($day->colour()) . ($cell['isToday'] ? ' ordo-is-today' : '');
            $html .= '<a class="' . esc_attr($classes) . '" href="' . esc_url((string) $urlFor($cell['iso'])) . '"'
                . ' data-ordo-day="' . esc_attr($cell['iso']) . '"'
                . ($cell['isToday'] ? ' aria-current="date"' : '') . '>'
                . '<span class="ordo-cal__agd">'
                . '<span class="ordo-cal__agw">' . esc_html(strtoupper($day->date()->format('D'))) . '</span>'
                . '<span class="ordo-cal__agn">' . esc_html($day->date()->format('j')) . '</span>'
                . '</span>'
                . '<span class="ordo-cal__agf">' . esc_html(self::shortName($day)) . '</span>'
                . '<span class="ordo-cal__agr">' . esc_html($day->rankRoman()) . '</span>'
                . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    /** The grid/agenda label: bare "Feria" for ferias, else the winning feast's name. */
    private static function shortName(DayPresenter $day): string
    {
        return $day->kind() === 'feria' ? __('Feria', 'ordo') : $day->feastLatin();
    }

    private static function wlClass(string $colour): string
    {
        return 'ordo-wl-' . Palette::normalise($colour);
    }

    private static function firstOfMonth(int $year, int $month): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('%04d-%02d-01', $year, $month));

        return $date instanceof DateTimeImmutable ? $date : new DateTimeImmutable('first day of this month');
    }

    /**
     * Step the (year, month) by whole months, clamped to the resolvable year range.
     *
     * @return array{0: int, 1: int}
     */
    private static function step(int $year, int $month, int $delta): array
    {
        $index = ($year * 12) + ($month - 1) + $delta;
        $year = intdiv($index, 12);
        $month = ($index % 12) + 1;

        if ($year < self::MIN_YEAR) {
            return [self::MIN_YEAR, 1];
        }
        if ($year > self::MAX_YEAR) {
            return [self::MAX_YEAR, 12];
        }

        return [$year, $month];
    }

    private static function shift(DateTimeImmutable $date, int $days): DateTimeImmutable
    {
        if ($days === 0) {
            return $date;
        }

        $spec = ($days > 0 ? '+' : '-') . abs($days) . ' days';
        $shifted = $date->modify($spec);

        return $shifted instanceof DateTimeImmutable ? $shifted : $date;
    }
}
