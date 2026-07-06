<?php

declare(strict_types=1);

namespace Directorium\Ordo;

use Directorium\Ordo\Admin\Settings;
use Directorium\Ordo\Engine\Core;

/**
 * The plugin bootstrap: wired once on `plugins_loaded`.
 *
 * This is the composition root. It registers the pretty-permalink route, loads
 * translations, wires every surface (the today card, masthead, month grid and their
 * blocks), the day-view REST route, the /ordo/ day page and the admin settings
 * screen, and registers the assets. It holds the single {@see Core} engine boundary
 * so every surface shares one instance.
 */
final class Plugin
{
    private Core $engine;

    public function __construct()
    {
        $this->engine = new Core();
    }

    /**
     * Instantiate the plugin and register its hooks. Kept static so the bootstrap
     * file can reference it as a callable without carrying state of its own.
     */
    public static function boot(): void
    {
        (new self())->registerHooks();
    }

    /** The shared engine boundary, for surfaces wired in later epics. */
    public function engine(): Core
    {
        return $this->engine;
    }

    private function registerHooks(): void
    {
        $assets = new Assets();
        $shortcodes = new Shortcodes($this->engine, $assets);
        $blocks = new Blocks($shortcodes);
        $rest = new Rest($this->engine);
        $dayRoute = new DayRoute($assets);
        $settings = new Settings();

        add_action('init', array(Rewrites::class, 'register'));
        add_action('init', array($this, 'loadTextDomain'));
        add_action('init', array($assets, 'register'));
        add_action('init', array($shortcodes, 'register'));
        add_action('init', array($blocks, 'register'));
        add_action('rest_api_init', array($rest, 'register'));

        $dayRoute->register();
        $settings->register();
    }

    /** Load the plugin's translations from the bundled /languages directory. */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain('ordo', false, dirname(plugin_basename(ORDO_FILE)) . '/languages');
    }
}
