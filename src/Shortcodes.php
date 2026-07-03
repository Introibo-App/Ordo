<?php

declare(strict_types=1);

namespace Introibo\Ordo;

use DateTimeImmutable;
use Introibo\Ordo\Engine\Core;
use Introibo\Ordo\Support\Clock;
use Introibo\Ordo\Surface\CalendarStrip;
use Introibo\Ordo\Surface\Context;
use Introibo\Ordo\Surface\TodayCard;
use Introibo\Ordo\View\DayPresenter;

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

    /** Build the /ordo/YYYY-MM-DD/ permalink for a day (public: used as a callback). */
    public static function dayUrl(string $iso): string
    {
        return home_url('/ordo/' . $iso . '/');
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
