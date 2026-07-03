<?php

/**
 * PHPUnit bootstrap.
 *
 * The suite is deliberately WordPress-independent: it exercises the engine
 * boundary, the autoloader and the surface renderers, none of which need a WP
 * runtime. Composer's autoloader maps the plugin namespace, the vendored Core
 * namespace, and Core's function entry points — the same files the runtime
 * autoloader loads on a live site — and the WordPress function stubs stand in for
 * the escaping, translation and option helpers the surfaces call.
 *
 * @package Introibo\Ordo
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/wp-stubs.php';
