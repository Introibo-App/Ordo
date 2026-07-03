<?php

/**
 * Plugin Name:       Ordo
 * Plugin URI:        https://github.com/Introibo-App/Ordo
 * Description:       The traditional Roman liturgical calendar (1962) for WordPress. Bundles the Introibo engine.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Introibo
 * Author URI:        https://github.com/Introibo-App
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ordo
 * Domain Path:       /languages
 *
 * Ordo is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License, version 2 or (at your option) any later
 * version. It bundles the Introibo Core engine, which is licensed AGPL-3.0-or-later
 * (see src/Core/LICENSE); GPL-2.0-or-later is compatible because it reaches GPLv3,
 * with which AGPL-3.0 combines. The bundled corpus (src/Core/data/corpus/) is CC0.
 *
 * @package Introibo\Ordo
 */

// This file is written to parse on legacy PHP so the version guard below can show a
// friendly notice instead of a fatal parse error. Everything under src/ (loaded only
// once the guard passes) is free to use PHP 7.4 syntax.

if (!defined('ABSPATH')) {
    exit; // Never executed outside WordPress.
}

define('ORDO_VERSION_FALLBACK', '0.1.0');
define('ORDO_MIN_PHP', '7.4');
define('ORDO_FILE', __FILE__);
define('ORDO_DIR', plugin_dir_path(__FILE__));
define('ORDO_URL', plugin_dir_url(__FILE__));
define('ORDO_OPTION_PREFIX', 'ordo_');

// The plugin header is the single source of truth for the version (#2). Read it once
// (get_file_data is available whenever WordPress loads a plugin) and fall back to the
// literal above only if the header cannot be parsed.
$ordo_header = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');
define('ORDO_VERSION', ($ordo_header['Version'] !== '') ? $ordo_header['Version'] : ORDO_VERSION_FALLBACK);

if (version_compare(PHP_VERSION, ORDO_MIN_PHP, '<')) {
    // Running on PHP older than the floor: refuse to load the 7.4 engine, and make the
    // reason visible instead of fatal-erroring. Activation aborts with the same message.
    $ordo_php_notice = function () {
        $message = sprintf(
            /* translators: 1: required PHP version, 2: current PHP version. */
            __('Ordo requires PHP %1$s or newer. This site is running PHP %2$s, so Ordo has not been loaded.', 'ordo'),
            ORDO_MIN_PHP,
            PHP_VERSION
        );
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    };
    add_action('admin_notices', $ordo_php_notice);

    register_activation_hook(__FILE__, function () {
        if (function_exists('deactivate_plugins')) {
            deactivate_plugins(plugin_basename(ORDO_FILE));
        }
        wp_die(
            esc_html(sprintf(
                /* translators: 1: required PHP version, 2: current PHP version. */
                __('Ordo requires PHP %1$s or newer; this site runs PHP %2$s.', 'ordo'),
                ORDO_MIN_PHP,
                PHP_VERSION
            )),
            esc_html__('Plugin activation failed', 'ordo'),
            array('back_link' => true)
        );
    });

    return; // Do not load the engine on an unsupported PHP.
}

// PHP is supported — load the plugin's own PSR-4 autoloader and the bundled engine's
// function entry points, then wire lifecycle hooks and boot on plugins_loaded.
require_once ORDO_DIR . 'src/Autoloader.php';
Introibo\Ordo\Autoloader::register();
require_once ORDO_DIR . 'src/Core/src/functions.php';

register_activation_hook(__FILE__, array('Introibo\\Ordo\\Lifecycle', 'activate'));
register_deactivation_hook(__FILE__, array('Introibo\\Ordo\\Lifecycle', 'deactivate'));

add_action('plugins_loaded', array('Introibo\\Ordo\\Plugin', 'boot'));
