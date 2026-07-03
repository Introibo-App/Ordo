<?php

declare(strict_types=1);

namespace Introibo\Ordo\View;

use DateTimeImmutable;

/**
 * A view-ready reading of one day's output contract.
 *
 * The engine returns a JSON-shaped array of mixed values; this is the single
 * place that interprets it, so every surface works with typed accessors instead
 * of reaching into nested keys. It reads the winning celebration and the day's
 * season — all the today card, strip and ribbon display — and is deliberately
 * free of WordPress so it can be unit-tested against the bundled engine offline.
 */
final class DayPresenter
{
    private DateTimeImmutable $date;
    private string $feastLatin;
    private string $rankRoman;
    private int $rankOrdinal;
    private string $colour;
    private string $seasonToken;
    private string $temporaId;

    /**
     * @param array<string, mixed> $contract A day contract as returned by the engine boundary.
     */
    public function __construct(array $contract)
    {
        $this->date = self::readDate($contract);

        $celebration = self::firstCelebration($contract);
        $this->feastLatin = self::nestedString($celebration, 'names', 'la');
        $this->rankRoman = self::string($celebration['rank'] ?? '');
        $this->rankOrdinal = (int) ($celebration['rankOrdinal'] ?? 4);
        $this->colour = Palette::normalise(self::nestedString($celebration, 'colour', 'base'));

        $this->seasonToken = self::string($contract['season'] ?? '');
        $this->temporaId = self::firstTemporaId($contract);
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function isoDate(): string
    {
        return $this->date->format('Y-m-d');
    }

    /** The winning celebration's Latin name, or "Feria" when the day is nameless. */
    public function feastLatin(): string
    {
        return $this->feastLatin !== '' ? $this->feastLatin : 'Feria';
    }

    /** The class as a Roman numeral: "I", "II", "III", "IV". */
    public function rankRoman(): string
    {
        return $this->rankRoman;
    }

    public function rankOrdinal(): int
    {
        return $this->rankOrdinal;
    }

    /** Whether the day is a ranked feast or Sunday (I–III class) that names a class. */
    public function hasClass(): bool
    {
        return $this->rankOrdinal >= 1 && $this->rankOrdinal <= 3;
    }

    /** The liturgical colour key ("white", "red", …), normalised to the known set. */
    public function colour(): string
    {
        return $this->colour;
    }

    /** The season as a translated display name, e.g. "Time after Pentecost — 14th week". */
    public function seasonName(): string
    {
        $base = self::seasonLabel($this->seasonToken);
        $week = $this->weekOrdinal();

        if ($base !== '' && $week !== '') {
            /* translators: 1: season name, 2: ordinal week within the season (e.g. "14th"). */
            return sprintf(__('%1$s — %2$s week', 'ordo'), $base, $week);
        }

        return $base;
    }

    private static function seasonLabel(string $token): string
    {
        switch ($token) {
            case 'advent':
                return __('Advent', 'ordo');
            case 'christmastide':
                return __('Christmastide', 'ordo');
            case 'epiphany':
                return __('Time after Epiphany', 'ordo');
            case 'septuagesima':
                return __('Septuagesima', 'ordo');
            case 'lent':
                return __('Lent', 'ordo');
            case 'passiontide':
                return __('Passiontide', 'ordo');
            case 'eastertide':
                return __('Eastertide', 'ordo');
            case 'pentecost':
                return __('Time after Pentecost', 'ordo');
            default:
                return '';
        }
    }

    /** The week within the season, as an English ordinal, read from the temporal id. */
    private function weekOrdinal(): string
    {
        if (preg_match('/:week-(\d+)/', $this->temporaId, $matches) === 1) {
            return self::ordinal((int) $matches[1]);
        }

        return '';
    }

    private static function ordinal(int $number): string
    {
        $mod100 = $number % 100;
        if ($mod100 >= 11 && $mod100 <= 13) {
            return $number . 'th';
        }

        switch ($number % 10) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
            default:
                return $number . 'th';
        }
    }

    /**
     * @param array<string, mixed> $contract
     */
    private static function readDate(array $contract): DateTimeImmutable
    {
        $iso = self::string($contract['date'] ?? '');
        if ($iso !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $iso);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return new DateTimeImmutable('today');
    }

    /**
     * @param array<string, mixed> $contract
     * @return array<string, mixed>
     */
    private static function firstCelebration(array $contract): array
    {
        $celebration = $contract['celebration'] ?? null;
        if (is_array($celebration) && isset($celebration[0]) && is_array($celebration[0])) {
            return $celebration[0];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $contract
     */
    private static function firstTemporaId(array $contract): string
    {
        $tempora = $contract['tempora'] ?? null;
        if (is_array($tempora) && isset($tempora[0]) && is_array($tempora[0])) {
            return self::string($tempora[0]['id'] ?? '');
        }

        return '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function nestedString(array $data, string $outer, string $inner): string
    {
        $level = $data[$outer] ?? null;
        if (is_array($level)) {
            return self::string($level[$inner] ?? '');
        }

        return '';
    }

    /**
     * @param mixed $value
     */
    private static function string($value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
