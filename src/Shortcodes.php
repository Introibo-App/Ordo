<?php

declare(strict_types=1);

namespace Directorium\Ordo;

use DateTimeImmutable;
use Directorium\Ordo\Engine\Core;
use Directorium\Ordo\Support\Clock;
use Directorium\Ordo\Support\IsoDate;
use Directorium\Ordo\Surface\CalendarStrip;
use Directorium\Ordo\Surface\Context;
use Directorium\Ordo\Surface\MonthGrid;
use Directorium\Ordo\Surface\TodayCard;
use Directorium\Ordo\View\DayPresenter;

/**
 * Registers the public shortcodes and renders them from the bundled engine.
 *
 * The same render methods back the Gutenberg blocks (see {@see Blocks}), so a
 * shortcode and its block produce byte-identical markup. Each renderer enqueues
 * the stylesheet (and, for the strip, its slider) only when it actually runs, so
 * pages without a surface stay asset-free.
 */
final class Shortcodes
{
    private Core $engine;
    private Assets $assets;

    public function __construct(Core $engine, Assets $assets)
    {
        $this->engine = $engine;
        $this->assets = $assets;
    }

    public function register(): void
    {
        add_shortcode('ordo_today', [$this, 'renderToday']);
        add_shortcode('ordo_calendar_strip', [$this, 'renderStrip']);
        add_shortcode('ordo_calendar', [$this, 'renderCalendar']);
    }

    /**
     * The [ordo_today] card / `ordo/today` block.
     *
     * @param array<string, mixed>|string $atts shortcode or block attributes
     */
    public function renderToday($atts = []): string
    {
        $atts = shortcode_atts(['date' => ''], self::attributes($atts), 'ordo_today');
        $this->assets->enqueueStyle();
        $this->assets->enqueueModal();

        $context = Context::current();
        $today = Clock::today();
        $centre = self::resolveDate((string) $atts['date'], $today);

        $day = new DayPresenter($this->engine->day($centre, $context->engineCalendar()));
        $isToday = $day->isoDate() === $today->format('Y-m-d');

        return TodayCard::render($day, $context, $isToday, self::dayUrl($day->isoDate()));
    }

    /**
     * The [ordo_calendar_strip] week strip / ribbon — `ordo/calendar-strip` block.
     *
     * @param array<string, mixed>|string $atts shortcode or block attributes
     */
    public function renderStrip($atts = []): string
    {
        $atts = shortcode_atts(
            ['date' => '', 'days' => '15', 'variant' => 'week'],
            self::attributes($atts),
            'ordo_calendar_strip'
        );
        $this->assets->enqueueStyle();
        $this->assets->enqueueModal();

        $context = Context::current();
        $today = Clock::today();
        $centre = self::resolveDate((string) $atts['date'], $today);

        if ((string) $atts['variant'] === 'ribbon') {
            $day = new DayPresenter($this->engine->day($centre, $context->engineCalendar()));

            return CalendarStrip::ribbon($day, self::dayUrl($day->isoDate()));
        }

        $this->assets->enqueueStripScript();

        return CalendarStrip::week(
            $this->engine,
            $context,
            $centre,
            $today,
            (int) $atts['days'],
            [self::class, 'dayUrl']
        );
    }

    /**
     * The [ordo_calendar] month grid — `ordo/calendar` block.
     *
     * The displayed month comes from the page query string (so month navigation is a
     * plain link and each month is shareable), falling back to the shortcode's
     * year/month attributes, then to the current month.
     *
     * @param array<string, mixed>|string $atts shortcode or block attributes
     */
    public function renderCalendar($atts = []): string
    {
        $atts = shortcode_atts(['year' => '', 'month' => ''], self::attributes($atts), 'ordo_calendar');
        $this->assets->enqueueStyle();
        $this->assets->enqueueModal();

        $context = Context::current();
        $today = Clock::today();
        [$year, $month] = self::resolveMonth((string) $atts['year'], (string) $atts['month'], $today);

        $baseUrl = self::currentUrl();
        $navUrl = static function (int $navYear, int $navMonth) use ($baseUrl): string {
            return add_query_arg(
                [MonthGrid::QV_YEAR => $navYear, MonthGrid::QV_MONTH => $navMonth],
                $baseUrl
            ) . '#ordo-cal';
        };

        return MonthGrid::render(
            $this->engine,
            $context,
            $year,
            $month,
            $today,
            [self::class, 'dayUrl'],
            $navUrl,
            $baseUrl
        );
    }

    /** Build the /ordo/YYYY-MM-DD/ permalink for a day (public: used as a callback). */
    public static function dayUrl(string $iso): string
    {
        return home_url('/ordo/' . $iso . '/');
    }

    /**
     * Resolve the month to display: the query string wins, then attributes, then the
     * current month, so navigation is shareable but an embed can still set a start.
     *
     * @return array{0: int, 1: int} the [year, month] to render
     */
    private static function resolveMonth(string $attYear, string $attMonth, DateTimeImmutable $today): array
    {
        $fromQuery = self::monthFromQuery();
        if ($fromQuery !== null) {
            return $fromQuery;
        }

        if ($attYear !== '' && $attMonth !== '') {
            $fromAtts = self::validateYearMonth($attYear, $attMonth);
            if ($fromAtts !== null) {
                return $fromAtts;
            }
        }

        return [(int) $today->format('Y'), (int) $today->format('n')];
    }

    /**
     * The month named by the ?ordo_y=&ordo_m= query string, if both are valid.
     *
     * @return array{0: int, 1: int}|null
     */
    private static function monthFromQuery(): ?array
    {
        $rawYear = $_GET[MonthGrid::QV_YEAR] ?? null;
        $rawMonth = $_GET[MonthGrid::QV_MONTH] ?? null;
        if (!is_string($rawYear) || !is_string($rawMonth)) {
            return null;
        }

        return self::validateYearMonth($rawYear, $rawMonth);
    }

    /**
     * Validate a year/month pair against the resolvable range and 1–12 months.
     *
     * @return array{0: int, 1: int}|null
     */
    private static function validateYearMonth(string $year, string $month): ?array
    {
        if (preg_match('/^\d{1,4}$/', $year) !== 1 || preg_match('/^\d{1,2}$/', $month) !== 1) {
            return null;
        }

        $y = (int) $year;
        $m = (int) $month;
        if ($m < 1 || $m > 12 || $y < IsoDate::MIN_YEAR || $y > IsoDate::MAX_YEAR) {
            return null;
        }

        return [$y, $m];
    }

    /** The current page's permalink, used as the base for month navigation links. */
    private static function currentUrl(): string
    {
        $permalink = get_permalink();

        return is_string($permalink) && $permalink !== '' ? $permalink : home_url('/');
    }

    /**
     * @param array<string, mixed>|string $atts
     * @return array<string, mixed>
     */
    private static function attributes($atts): array
    {
        return is_array($atts) ? $atts : [];
    }

    /** Parse a validated ISO date attribute, falling back to the given date. */
    private static function resolveDate(string $raw, DateTimeImmutable $fallback): DateTimeImmutable
    {
        if (
            preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])
        ) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return $fallback;
    }
}
