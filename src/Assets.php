<?php

declare(strict_types=1);

namespace Introibo\Ordo;

use Introibo\Ordo\Surface\DayView;

/**
 * Registers and enqueues the plugin's front-end and block-editor assets.
 *
 * Front-end assets are registered on `init` but enqueued lazily — a surface calls
 * enqueueStyle()/enqueueStripScript()/enqueueModal() only when it renders — so pages
 * without an Ordo surface load nothing. The block-editor scripts are registered with
 * their WordPress dependencies so the buildless blocks find `wp.serverSideRender`.
 */
final class Assets
{
    public const STYLE = 'ordo';
    public const STRIP_SCRIPT = 'ordo-strip';
    public const MODAL_SCRIPT = 'ordo-modal';

    /** Whether the day-view modal shell has been scheduled to print this request. */
    private bool $modalPrinted = false;

    /** Register every handle. Called on `init`; registration is context-free. */
    public function register(): void
    {
        wp_register_style(self::STYLE, ORDO_URL . 'assets/css/ordo.css', [], ORDO_VERSION);
        wp_register_script(self::STRIP_SCRIPT, ORDO_URL . 'assets/js/ordo-strip.js', [], ORDO_VERSION, true);
        wp_register_script(self::MODAL_SCRIPT, ORDO_URL . 'assets/js/ordo-modal.js', [], ORDO_VERSION, true);

        $deps = ['wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n'];
        wp_register_script('ordo-block-today', ORDO_URL . 'blocks/today/index.js', $deps, ORDO_VERSION, true);
        wp_register_script('ordo-block-strip', ORDO_URL . 'blocks/calendar-strip/index.js', $deps, ORDO_VERSION, true);
        wp_register_script('ordo-block-calendar', ORDO_URL . 'blocks/calendar/index.js', $deps, ORDO_VERSION, true);
    }

    /** Enqueue the surface stylesheet (safe to call repeatedly). */
    public function enqueueStyle(): void
    {
        wp_enqueue_style(self::STYLE);
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
}
