<?php

declare(strict_types=1);

namespace Introibo\Ordo;

use Introibo\Ordo\Engine\Core;

/**
 * The plugin bootstrap: wired once on `plugins_loaded`.
 *
 * This is the composition root. It registers the pretty-permalink route, loads
 * translations, wires the today card and masthead surfaces (their shortcodes and
 * blocks), and registers the assets; the month grid, day view and settings attach
 * here as their epics land. It holds the single {@see Core} engine boundary so
 * every surface shares one instance.
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

        add_action('init', array(Rewrites::class, 'register'));
        add_action('init', array($this, 'loadTextDomain'));
        add_action('init', array($assets, 'register'));
        add_action('init', array($shortcodes, 'register'));
        add_action('init', array($blocks, 'register'));
    }

    /** Load the plugin's translations from the bundled /languages directory. */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain('ordo', false, dirname(plugin_basename(ORDO_FILE)) . '/languages');
    }
}
