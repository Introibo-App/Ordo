<?php

declare(strict_types=1);

namespace Directorium\Ordo;

use Directorium\Ordo\Surface\Context;
use Directorium\Ordo\Surface\DayView;
use Directorium\Ordo\View\Skin;

/**
 * Registers and enqueues the plugin's front-end and block-editor assets.
 *
 * Front-end assets are registered on `init` but enqueued lazily — a surface calls
 * enqueueStyle()/enqueueStripScript()/enqueueModal() only when it renders — so pages
 * without an Ordo surface load nothing. Every handle is versioned by the file's own
 * modification time, so a byte change busts the browser cache with no manual bump and
 * unchanged files stay cached. The block-editor scripts are registered with their
 * WordPress dependencies so the buildless blocks find `wp.serverSideRender`.
 */
final class Assets
{
    public const STYLE = 'ordo';
    public const STRIP_SCRIPT = 'ordo-strip';
    public const MODAL_SCRIPT = 'ordo-modal';

    /** Whether the day-view modal shell has been scheduled to print this request. */
    private bool $modalPrinted = false;

    /** Whether the configured skin's inline overrides have been attached this request. */
    private bool $skinApplied = false;

    /** Register every handle. Called on `init`; registration is context-free. */
    public function register(): void
    {
        wp_register_style(self::STYLE, ORDO_URL . 'assets/css/ordo.css', [], self::version('assets/css/ordo.css'));
        wp_register_script(
            self::STRIP_SCRIPT,
            ORDO_URL . 'assets/js/ordo-strip.js',
            [],
            self::version('assets/js/ordo-strip.js'),
            true
        );
        wp_register_script(
            self::MODAL_SCRIPT,
            ORDO_URL . 'assets/js/ordo-modal.js',
            [],
            self::version('assets/js/ordo-modal.js'),
            true
        );

        $deps = ['wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n'];
        wp_register_script(
            'ordo-block-today',
            ORDO_URL . 'blocks/today/index.js',
            $deps,
            self::version('blocks/today/index.js'),
            true
        );
        wp_register_script(
            'ordo-block-strip',
            ORDO_URL . 'blocks/calendar-strip/index.js',
            $deps,
            self::version('blocks/calendar-strip/index.js'),
            true
        );
        wp_register_script(
            'ordo-block-calendar',
            ORDO_URL . 'blocks/calendar/index.js',
            $deps,
            self::version('blocks/calendar/index.js'),
            true
        );
    }

    /**
     * Enqueue the surface stylesheet (safe to call repeatedly), and — once per request —
     * attach the configured white-label skin as inline overrides that follow it.
     */
    public function enqueueStyle(): void
    {
        wp_enqueue_style(self::STYLE);

        if (!$this->skinApplied) {
            $this->skinApplied = true;
            $css = Skin::css(Context::current()->skin());
            if ($css !== '') {
                wp_add_inline_style(self::STYLE, $css);
            }
        }
    }

    /** Enqueue the strip slider, the one script the masthead strip needs. */
    public function enqueueStripScript(): void
    {
        wp_enqueue_script(self::STRIP_SCRIPT);
    }

    /**
     * Enqueue the day-view modal: its style and script (told where the REST route
     * lives), and — once per request — schedule the modal shell to print in the
     * footer so a single dialog serves every trigger on the page.
     */
    public function enqueueModal(): void
    {
        $this->enqueueStyle();
        wp_enqueue_script(self::MODAL_SCRIPT);
        wp_localize_script(self::MODAL_SCRIPT, 'ordoModal', [
            'rest' => esc_url_raw(rest_url(Rest::NS . '/day/')),
        ]);

        if (!$this->modalPrinted) {
            $this->modalPrinted = true;
            // The shell markup is escaped where it is built (see DayView::modalShell).
            add_action('wp_footer', static function (): void {
                echo DayView::modalShell();
            });
        }
    }

    /**
     * A cache-busting version for a bundled asset: its modification time, so any byte
     * change invalidates the browser cache. Falls back to the plugin version if the
     * file cannot be stat'd (it always can on a real install).
     */
    private static function version(string $relativePath): string
    {
        $path = ORDO_DIR . $relativePath;
        if (file_exists($path)) {
            $mtime = filemtime($path);
            if ($mtime !== false) {
                return (string) $mtime;
            }
        }

        return ORDO_VERSION;
    }
}
