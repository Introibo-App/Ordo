<?php

declare(strict_types=1);

namespace Introibo\Ordo\Tests;

use Introibo\Ordo\Autoloader;
use PHPUnit\Framework\TestCase;

/**
 * The hand-rolled PSR-4 autoloader (issue #2) resolves both the plugin namespace
 * and the vendored Core namespace, and leaves foreign classes to other loaders —
 * the same contract the runtime relies on where no Composer autoloader exists.
 */
final class AutoloaderTest extends TestCase
{
    public function testRegistersWithoutError(): void
    {
        Autoloader::register();
        self::assertTrue(class_exists(Autoloader::class));
    }

    public function testResolvesPluginNamespace(): void
    {
        self::assertTrue(class_exists('Introibo\\Ordo\\Engine\\Core'));
    }

    public function testResolvesBundledCoreNamespace(): void
    {
        self::assertTrue(class_exists('Introibo\\Core\\Introibo'));
    }

    public function testIgnoresForeignClasses(): void
    {
        // A no-op for unknown prefixes: it must not error and must not define the class.
        Autoloader::load('Some\\Other\\Vendor\\Thing');
        self::assertFalse(class_exists('Some\\Other\\Vendor\\Thing', false));
    }
}
