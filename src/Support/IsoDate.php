<?php

declare(strict_types=1);

namespace Directorium\Ordo\Support;

use DateTimeImmutable;

/**
 * Parsing and validation for the ISO dates the plugin accepts from a URL — the
 * pretty-permalink date, the REST path, the calendar's year field. Centralised so
 * every entry point agrees on the same shape (a real calendar date) and the same
 * resolvable window (the engine's 1583–2200 range, mirrored from the API).
 */
final class IsoDate
{
    public const MIN_YEAR = 1583;
    public const MAX_YEAR = 2200;

    /** Parse a strict YYYY-MM-DD that names a real calendar date, or null. */
    public static function parse(string $raw): ?DateTimeImmutable
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) !== 1) {
            return null;
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    /** As {@see parse()}, additionally requiring the year to be resolvable (1583–2200). */
    public static function parseInRange(string $raw): ?DateTimeImmutable
    {
        $date = self::parse($raw);
        if ($date === null) {
            return null;
        }

        $year = (int) $date->format('Y');

        return $year >= self::MIN_YEAR && $year <= self::MAX_YEAR ? $date : null;
    }
}
