<?php

/**
 * PHPStan bootstrap: declare the plugin's runtime constants so static analysis can
 * resolve them. At runtime these are defined by ordo.php from the plugin header;
 * here they are declared (guarded) purely so analysis of the class files that read
 * them does not report an undefined constant.
 *
 * @package Directorium\Ordo
 */

declare(strict_types=1);

if (!defined('ORDO_VERSION')) {
    define('ORDO_VERSION', '0.0.0-analysis');
}
if (!defined('ORDO_MIN_PHP')) {
    define('ORDO_MIN_PHP', '7.4');
}
if (!defined('ORDO_FILE')) {
    define('ORDO_FILE', __FILE__);
}
if (!defined('ORDO_DIR')) {
    define('ORDO_DIR', __DIR__ . '/');
}
if (!defined('ORDO_URL')) {
    define('ORDO_URL', '');
}
if (!defined('ORDO_OPTION_PREFIX')) {
    define('ORDO_OPTION_PREFIX', 'ordo_');
}
