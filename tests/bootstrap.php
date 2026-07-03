<?php

/**
 * PHPUnit bootstrap.
 *
 * The suite is deliberately WordPress-independent: it exercises the engine
 * boundary and the autoloader, which need no WP runtime. Composer's autoloader
 * maps the plugin namespace, the vendored Core namespace, and Core's function
 * entry points — the same files the runtime autoloader loads on a live site.
 *
 * @package Introibo\Ordo
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
