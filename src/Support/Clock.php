<?php

declare(strict_types=1);

namespace Directorium\Ordo\Support;

use DateTimeImmutable;

/**
 * The current civil date in the site's timezone.
 *
 * Isolated so every surface shares one definition of "today" and the tests can
 * resolve days deterministically. Uses WordPress's timezone-aware clock when it
 * is available, and falls back to the server date otherwise (e.g. under the
 * WordPress-free unit tests).
 */
final class Clock
{
    /** Midnight today, in the site's timezone. */
    public static function today(): DateTimeImmutable
    {
        if (function_exists('current_datetime')) {
            return current_datetime()->setTime(0, 0);
        }

        return new DateTimeImmutable('today');
    }
}
