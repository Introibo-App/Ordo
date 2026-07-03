<?php

declare(strict_types=1);

namespace Introibo\Ordo;

/**
 * Activation, deactivation, and the rewrite flush.
 *
 * Options are seeded with add_option(), which never overwrites an existing value,
 * so re-activating is idempotent and never duplicates or resets configuration.
 * Rewrite rules are flushed only here — at activation (after registering the route)
 * and at deactivation (to clear the stale route) — never on a normal request.
 * Deactivation deletes no data; that is uninstall's job (see uninstall.php).
 */
final class Lifecycle
{
    /**
     * Register the pretty-permalink route, then flush so /ordo/ URLs resolve, and
     * seed default options if they are not already present.
     */
    public static function activate(): void
    {
        self::seedOptions();
        Rewrites::register();
        flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules so the /ordo/ route does not linger after the plugin is
     * switched off. No options or user data are touched.
     */
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Seed the plugin's options idempotently. The default calendar is the universal
     * 1962 calendar; a site (e.g. the SSPX deployment) selects a particular calendar
     * in settings later.
     */
    private static function seedOptions(): void
    {
        add_option(ORDO_OPTION_PREFIX . 'version', ORDO_VERSION);
        add_option(ORDO_OPTION_PREFIX . 'settings', array(
            'calendar'            => 'universal',
            'palette'             => 'illuminated',
            'show_commemorations' => true,
        ));
    }
}
