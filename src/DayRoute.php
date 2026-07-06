<?php

declare(strict_types=1);

namespace Directorium\Ordo;

use DateTimeImmutable;
use Directorium\Ordo\Engine\Core;
use Directorium\Ordo\Support\IsoDate;
use Directorium\Ordo\Surface\Context;
use Directorium\Ordo\Surface\DayView;
use Directorium\Ordo\View\DayPresenter;

/**
 * Serves the /ordo/YYYY-MM-DD/ pretty page that {@see Rewrites} routes. When the
 * date query var is present it takes over the response: it clears the would-be 404,
 * enqueues the surface style, sets the document title to the feast, and swaps in the
 * plugin's day template — which renders the full day view inside the active theme.
 *
 * The page is the no-JavaScript home of a day (the calendar and strip link here) and
 * a stable, shareable URL, so it is a plain server render with no client dependency.
 */
final class DayRoute
{
    private Assets $assets;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }

    /** Attach the response hooks. Called once during boot. */
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('template_redirect', [$this, 'prepareResponse']);
        add_filter('template_include', [$this, 'template']);
        add_filter('document_title_parts', [$this, 'documentTitle']);
    }

    /** The valid, resolvable date carried by the current request, or null. */
    public static function currentDate(): ?DateTimeImmutable
    {
        $raw = get_query_var(Rewrites::QUERY_VAR);

        return is_string($raw) && $raw !== '' ? IsoDate::parseInRange($raw) : null;
    }

    /** Enqueue the surface style on the day page (before wp_head prints it). */
    public function enqueueAssets(): void
    {
        if (self::currentDate() !== null) {
            $this->assets->enqueueStyle();
        }
    }

    /** Tell WordPress this is a real page, not a 404, and send a 200. */
    public function prepareResponse(): void
    {
        if (self::currentDate() === null) {
            return;
        }

        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->is_404 = false;
        }
        status_header(200);
    }

    /**
     * Route the day request to the plugin's template.
     *
     * @param string $template
     */
    public function template($template): string
    {
        return self::currentDate() !== null ? ORDO_DIR . 'templates/day.php' : (string) $template;
    }

    /**
     * Title the tab with the feast.
     *
     * @param array<string, string> $parts
     * @return array<string, string>
     */
    public function documentTitle($parts): array
    {
        $date = self::currentDate();
        if ($date === null) {
            return $parts;
        }

        $context = Context::current();
        $day = new DayPresenter((new Core())->day($date, $context->engineCalendar()));
        $parts['title'] = $day->feastLatin();

        return $parts;
    }

    /**
     * Render the current day page body. Called from the template inside the theme's
     * header/footer; builds its own (stateless) engine so the template stays a thin
     * include. Returns "" when there is no valid date — the template guards on it.
     */
    public static function renderCurrentPage(): string
    {
        $date = self::currentDate();
        if ($date === null) {
            return '';
        }

        $context = Context::current();
        $day = new DayPresenter((new Core())->day($date, $context->engineCalendar()));

        return DayView::page($day, $context);
    }
}
