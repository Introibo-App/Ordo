<?php

declare(strict_types=1);

namespace Introibo\Ordo\View;

use DateTimeInterface;

/**
 * Latin renderings of a civil date — the month in the genitive, the weekday as a
 * feria, and the year in lower-case Roman numerals — as the traditional ordo
 * names them (e.g. "3 Septembris mmxxvi · Feria V").
 *
 * These are structural facts of the Roman calendar's own vocabulary rather than
 * user-facing prose, so they are intentionally not translated: the Latin is the
 * content. Localised civil-date formatting is a separate concern, handled where a
 * surface meets WordPress.
 */
final class LatinCalendar
{
    /** Month number (1–12) → its Latin name in the genitive, as a date is spoken. */
    private const MONTHS = [
        1 => 'Ianuarii',
        2 => 'Februarii',
        3 => 'Martii',
        4 => 'Aprilis',
        5 => 'Maii',
        6 => 'Iunii',
        7 => 'Iulii',
        8 => 'Augusti',
        9 => 'Septembris',
        10 => 'Octobris',
        11 => 'Novembris',
        12 => 'Decembris',
    ];

    /** ISO weekday (1 = Monday … 7 = Sunday) → its traditional feria name. */
    private const FERIAE = [
        1 => 'Feria II',
        2 => 'Feria III',
        3 => 'Feria IV',
        4 => 'Feria V',
        5 => 'Feria VI',
        6 => 'Sabbato',
        7 => 'Dominica',
    ];

    /** The weekday named as a feria: "Dominica", "Feria V", "Sabbato". */
    public static function feria(DateTimeInterface $date): string
    {
        return self::FERIAE[(int) $date->format('N')];
    }

    /** The date in the genitive with a Roman-numeral year: "3 Septembris mmxxvi". */
    public static function dateGenitive(DateTimeInterface $date): string
    {
        return sprintf(
            '%d %s %s',
            (int) $date->format('j'),
            self::MONTHS[(int) $date->format('n')],
            self::romanYear((int) $date->format('Y'))
        );
    }

    /** A year in lower-case Roman numerals, as the ordo sets its dates (2026 → "mmxxvi"). */
    public static function romanYear(int $year): string
    {
        return strtolower(self::toRoman($year));
    }

    private static function toRoman(int $number): string
    {
        $numerals = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        $out = '';
        foreach ($numerals as $symbol => $value) {
            while ($number >= $value) {
                $out .= $symbol;
                $number -= $value;
            }
        }

        return $out;
    }
}
