<?php

declare(strict_types=1);

namespace Introibo\Ordo;

/**
 * A tiny PSR-4 autoloader for the plugin and its bundled engine.
 *
 * The plugin ships with no Composer runtime: WordPress hosts cannot run
 * `composer install`, so classes are resolved by this self-contained loader
 * instead. It maps two prefixes to the two source trees committed in the repo —
 * the plugin's own namespace and the vendored Core engine — and touches nothing
 * in global scope. Core's function entry points (day(), contract()) are not
 * classes and are required directly by the bootstrap, not loaded here.
 */
final class Autoloader
{
    /** @var array<string, string> Namespace prefix (with trailing separator) => base directory. */
    private const PREFIXES = [
        'Introibo\\Ordo\\' => __DIR__ . '/',
        'Introibo\\Core\\' => __DIR__ . '/Core/src/',
    ];

    /**
     * Register the autoloader with the SPL stack.
     *
     * Idempotent: registering twice is harmless because SPL will not add the same
     * callable more than once.
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    /**
     * Resolve and include the file for a fully-qualified class name.
     *
     * Only class names under a known prefix are handled; anything else is left for
     * other registered autoloaders.
     */
    public static function load(string $class): void
    {
        foreach (self::PREFIXES as $prefix => $baseDir) {
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
}
