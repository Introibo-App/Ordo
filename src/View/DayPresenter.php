<?php

declare(strict_types=1);

namespace Directorium\Ordo\View;

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
    private string $kind;
    private string $colour;
    private string $seasonToken;
    private string $temporaId;
    private string $temporalName;
    /** @var list<array{name: string, colour: string, kindLabel: string}> */
    private array $commemorations;

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
        $this->kind = self::string($celebration['kind'] ?? '');
        $this->colour = Palette::normalise(self::nestedString($celebration, 'colour', 'base'));

        $this->seasonToken = self::string($contract['season'] ?? '');
        $this->temporaId = self::firstTemporaId($contract);
        $this->temporalName = self::firstTemporaName($contract);
        $this->commemorations = self::readCommemorations($contract);
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

    /** The coarse contract kind ("feast", "feria", "sunday", …); "" when absent. */
    public function kind(): string
    {
        return $this->kind;
    }

    /** The class named in words: "First class" … "Fourth class" (or "" if unranked). */
    public function classLabel(): string
    {
        switch ($this->rankOrdinal) {
            case 1:
                return __('First class', 'ordo');
            case 2:
                return __('Second class', 'ordo');
            case 3:
                return __('Third class', 'ordo');
            case 4:
                return __('Fourth class', 'ordo');
            default:
                return '';
        }
    }

    /** The kind named for a reader ("Feast", "Feria", "Sunday", …); "" if unknown. */
    public function kindLabel(): string
    {
        return self::kindToLabel($this->kind);
    }

    /**
     * The one-line rank statement shown above the feast in the day view, combining
     * the class and the kind — e.g. "First class · Feast" — or just the class when
     * the kind has no reader-facing label.
     */
    public function rankLine(): string
    {
        $class = $this->classLabel();
        $kind = $this->kindLabel();

        if ($class !== '' && $kind !== '') {
            return $class . ' · ' . $kind;
        }

        return $class !== '' ? $class : $kind;
    }

    /** The temporal day's Latin name (the feria/Sunday of the season), or "". */
    public function temporalName(): string
    {
        return $this->temporalName;
    }

    /**
     * The commemorations of the day: the second (and third) offices and orations
     * yielded to the winning celebration, each with its name, normalised colour and
     * reader-facing kind. Empty when the day admits none.
     *
     * @return list<array{name: string, colour: string, kindLabel: string}>
     */
    public function commemorations(): array
    {
        return $this->commemorations;
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
     * @param array<string, mixed> $contract
     */
    private static function firstTemporaName(array $contract): string
    {
        $tempora = $contract['tempora'] ?? null;
        if (is_array($tempora) && isset($tempora[0]) && is_array($tempora[0])) {
            return self::nestedString($tempora[0], 'names', 'la');
        }

        return '';
    }

    /**
     * @param array<string, mixed> $contract
     * @return list<array{name: string, colour: string, kindLabel: string}>
     */
    private static function readCommemorations(array $contract): array
    {
        $raw = $contract['commemoration'] ?? null;
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $name = self::nestedString($entry, 'names', 'la');
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'colour' => Palette::normalise(self::nestedString($entry, 'colour', 'base')),
                'kindLabel' => self::kindToLabel(self::string($entry['kind'] ?? '')),
            ];
        }

        return $out;
    }

    /** Map a contract kind token to a reader-facing label, or "" when unlabelled. */
    private static function kindToLabel(string $kind): string
    {
        switch ($kind) {
            case 'feast':
                return __('Feast', 'ordo');
            case 'feria':
                return __('Feria', 'ordo');
            case 'sunday':
                return __('Sunday', 'ordo');
            case 'vigil':
                return __('Vigil', 'ordo');
            case 'octave-day':
                return __('Octave day', 'ordo');
            case 'within-octave':
                return __('Within the octave', 'ordo');
            case 'ember-day':
                return __('Ember day', 'ordo');
            case 'rogation-day':
                return __('Rogation day', 'ordo');
            case 'commemoration-only':
                return __('Commemoration', 'ordo');
            case 'lady-on-saturday':
                return __('Our Lady on Saturday', 'ordo');
            case 'office-of-the-dead':
                return __('Office of the Dead', 'ordo');
            default:
                return '';
        }
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
