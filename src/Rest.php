<?php

declare(strict_types=1);

namespace Introibo\Ordo;

use DateTimeImmutable;
use Introibo\Ordo\Engine\Core;
use Introibo\Ordo\Support\IsoDate;
use Introibo\Ordo\Surface\Context;
use Introibo\Ordo\Surface\DayView;
use Introibo\Ordo\View\DayPresenter;
use Introibo\Ordo\View\LatinCalendar;
use Introibo\Ordo\View\Palette;

/**
 * The read-only REST route that feeds the day-view modal: GET /wp-json/ordo/v1/day/
 * {date}. It resolves the requested day under the site's configured calendar and
 * returns the same article body the pretty page renders, plus a short window of
 * neighbouring days for the modal's day-by-day navigator.
 *
 * The data is public (the same facts the page already shows), so the route is a
 * plain public GET — no nonce, cacheable — mirroring the calendar API. The heavy
 * lifting lives in {@see payload()}, a pure builder that needs no WordPress runtime
 * and is unit-tested directly.
 */
final class Rest
{
    /** The versioned REST namespace this plugin owns. */
    public const NS = 'ordo/v1';

    /** How many days either side of the requested day the navigator window spans. */
    private const NAV_RADIUS = 3;

    private Core $engine;

    public function __construct(Core $engine)
    {
        $this->engine = $engine;
    }

    /** Register the route. Hooked on `rest_api_init`. */
    public function register(): void
    {
        register_rest_route(self::NS, '/day/(?P<date>\d{4}-\d{2}-\d{2})', [
            'methods' => 'GET',
            'callback' => [$this, 'handle'],
            'permission_callback' => '__return_true',
            'args' => [
                'date' => [
                    'validate_callback' => static function ($value): bool {
                        return is_string($value) && IsoDate::parseInRange($value) !== null;
                    },
                ],
            ],
        ]);
    }

    /**
     * Resolve the request into a day payload or a 404 when the date is unresolvable.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle(\WP_REST_Request $request)
    {
        $date = IsoDate::parseInRange((string) $request['date']);
        if ($date === null) {
            return new \WP_Error(
                'ordo_bad_date',
                __('That date cannot be resolved.', 'ordo'),
                ['status' => 404]
            );
        }

        return new \WP_REST_Response($this->payload($date, Context::current()));
    }

    /**
     * Build the modal payload for a resolvable day: its Latin bar label, feast title,
     * the shared article body, and a window of neighbouring days for the navigator.
     *
     * @return array{iso: string, barLabel: string, title: string, html: string,
     *     nav: list<array{iso: string, wd: string, dd: string, colour: string}>}
     */
    public function payload(DateTimeImmutable $date, Context $context): array
    {
        $calendar = $context->engineCalendar();
        $day = new DayPresenter($this->engine->day($date, $calendar));

        $nav = [];
        for ($offset = -self::NAV_RADIUS; $offset <= self::NAV_RADIUS; $offset++) {
            $neighbour = self::shift($date, $offset);
            $navDay = new DayPresenter($this->engine->day($neighbour, $calendar));
            $nav[] = [
                'iso' => $neighbour->format('Y-m-d'),
                'wd' => strtoupper($neighbour->format('D')),
                'dd' => $neighbour->format('j'),
                'colour' => Palette::normalise($navDay->colour()),
            ];
        }

        return [
            'iso' => $day->isoDate(),
            'barLabel' => LatinCalendar::dateGenitive($day->date()) . ' · ' . LatinCalendar::feria($day->date()),
            'title' => $day->feastLatin(),
            'html' => DayView::article($day, $context),
            'nav' => $nav,
        ];
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
