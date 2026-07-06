<?php

declare(strict_types=1);

namespace Directorium\Ordo\View;

/**
 * The liturgical colours a surface can paint, each mapped to the two CSS custom
 * properties the Illuminated-Breviary stylesheet defines for it: the plain swatch
 * (`--ordo-lit-*`) used on parchment, and the brightened variant (`--ordo-on-*`)
 * that stays legible on the navy masthead.
 *
 * The set is closed and matches the colours the engine's contract emits from its
 * `colour.base`; an unknown value falls back to white rather than emitting an
 * unstyled swatch.
 */
final class Palette
{
    /** @var list<string> */
    private const COLOURS = ['white', 'red', 'green', 'violet', 'black', 'rose'];

    /** The colour key normalised to the known set (unknown → white). */
    public static function normalise(string $colour): string
    {
        return in_array($colour, self::COLOURS, true) ? $colour : 'white';
    }

    /** The `--ordo-lit-*` custom property for a colour, e.g. "--ordo-lit-white". */
    public static function litVar(string $colour): string
    {
        return '--ordo-lit-' . self::normalise($colour);
    }

    /** The brightened `--ordo-on-*` custom property, for use on the navy bar. */
    public static function onVar(string $colour): string
    {
        return '--ordo-on-' . self::normalise($colour);
    }

    /** The translated display name of a colour ("White", "Red", …). */
    public static function label(string $colour): string
    {
        switch (self::normalise($colour)) {
            case 'red':
                return __('Red', 'ordo');
            case 'green':
                return __('Green', 'ordo');
            case 'violet':
                return __('Violet', 'ordo');
            case 'black':
                return __('Black', 'ordo');
            case 'rose':
                return __('Rose', 'ordo');
            case 'white':
            default:
                return __('White', 'ordo');
        }
    }
}
