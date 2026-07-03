<?php

declare(strict_types=1);

namespace Introibo\Ordo;

/**
 * The pretty-permalink route for shareable day pages: /ordo/YYYY-MM-DD/.
 *
 * The rule and its query var are registered on every request (via `init`) so the
 * route resolves, and the same registration runs at activation immediately before
 * the one-time rewrite flush — WordPress only rebuilds its rule cache when the set
 * changes, so the flush must follow a registration. The template that renders the
 * day for this query var is wired by the day-view work (Epic #15); here the route
 * exists and carries the date so the lifecycle is complete and idempotent.
 */
final class Rewrites
{
    /** The public query var carrying the requested ISO date. */
    public const QUERY_VAR = 'ordo_date';

    /** Matches /ordo/2026-09-03/ (trailing slash optional). */
    private const ROUTE = '^ordo/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$';

    /**
     * Register the rewrite tag and rule. Adding the tag also whitelists the query
     * var, so a matched date survives WordPress's query-var sanitisation.
     */
    public static function register(): void
    {
        add_rewrite_tag('%' . self::QUERY_VAR . '%', '([0-9]{4}-[0-9]{2}-[0-9]{2})');
        add_rewrite_rule(self::ROUTE, 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top');
    }
}
