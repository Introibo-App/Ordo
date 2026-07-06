<?php

declare(strict_types=1);

namespace Directorium\Ordo\Engine;

use DateTimeImmutable;

use function Directorium\Core\contract;

/**
 * The single boundary between the plugin and the bundled Directorium engine.
 *
 * Every liturgical fact the plugin renders passes through here, so the rest of
 * the plugin never calls the engine directly — mirroring the API's one-gateway
 * design. The engine is bundled (src/Core/), so resolution is fully offline: no
 * HTTP, no external service. This class deliberately touches no WordPress API,
 * which keeps it unit-testable without a WordPress install.
 */
final class Core
{
    /**
     * The resolved output contract for a civil date, as the versioned JSON-ready
     * array the whole Directorium platform shares (celebration, commemorations,
     * season, provenance, and — when a particular calendar is named — its stamp).
     *
     * @param string|null $calendar The particular calendar to resolve under: null
     *                              for the universal 1962 calendar, or a slug such
     *                              as `sspx`.
     *
     * @return array<string, mixed>
     */
    public function day(DateTimeImmutable $date, ?string $calendar = null): array
    {
        return contract($date, false, $calendar);
    }

    /**
     * The version of the Core engine vendored into this plugin, read from the pin
     * that bin/vendor-core.php writes. Used to stamp responses and caches so a
     * re-vendor invalidates anything keyed on the engine.
     */
    public function bundledCoreVersion(): string
    {
        $pin = dirname(__DIR__) . '/Core/CORE_VERSION';
        if (is_file($pin)) {
            $contents = (string) file_get_contents($pin);
            if (preg_match('/^version:\s*(.+)$/m', $contents, $m) === 1) {
                return trim($m[1]);
            }
        }

        return 'unknown';
    }
}
