<?php

declare(strict_types=1);

namespace Introibo\Ordo;

/**
 * Registers and enqueues the plugin's front-end and block-editor assets.
 *
 * Front-end assets are registered on `init` but enqueued lazily — a surface calls
 * enqueueStyle()/enqueueStripScript() only when it renders — so pages without an
 * Ordo surface load nothing. The block-editor scripts are registered with their
 * WordPress dependencies so the buildless blocks find `wp.serverSideRender`.
 */
final class Assets
{
    public const STYLE = 'ordo';
    public const STRIP_SCRIPT = 'ordo-strip';

    /** Register every handle. Called on `init`; registration is context-free. */
    public function register(): void
    {
        wp_register_style(self::STYLE, ORDO_URL . 'assets/css/ordo.css', [], ORDO_VERSION);
        wp_register_script(self::STRIP_SCRIPT, ORDO_URL . 'assets/js/ordo-strip.js', [], ORDO_VERSION, true);

        $deps = ['wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n'];
        wp_register_script('ordo-block-today', ORDO_URL . 'blocks/today/index.js', $deps, ORDO_VERSION, true);
        wp_register_script('ordo-block-strip', ORDO_URL . 'blocks/calendar-strip/index.js', $deps, ORDO_VERSION, true);
    }

    /** Enqueue the surface stylesheet (safe to call repeatedly). */
    public function enqueueStyle(): void
    {
        wp_enqueue_style(self::STYLE);
    }

    /** Enqueue the strip slider, the one script any surface needs. */
    public function enqueueStripScript(): void
    {
        wp_enqueue_script(self::STRIP_SCRIPT);
    }
}
