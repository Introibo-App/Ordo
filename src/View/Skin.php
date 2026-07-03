<?php

declare(strict_types=1);

namespace Introibo\Ordo\View;

/**
 * The white-label skins a site can choose in settings: named recolourings of the
 * surface design tokens. "Illuminated" is the shipped baseline (the 3mi.org /
 * Mary Michael Machabee navy-and-gold identity the CSS already defines); the others
 * override the brand custom properties so the same markup reskins coherently.
 *
 * Only the brand tokens change — the liturgical colours (--ordo-lit-*, --ordo-on-*)
 * are fixed by the rite, not the site, so they are never reskinned. A skin is emitted
 * as an inline stylesheet scoped to .ordo, appended after the base stylesheet, so a
 * page with no Ordo surface still loads nothing extra.
 */
final class Skin
{
    public const DEFAULT = 'illuminated';

    /**
     * Skin key → its label and one-line description (for the settings screen) plus the
     * brand-token overrides it applies. The baseline "illuminated" carries no overrides
     * because the base stylesheet already is that skin.
     *
     * @var array<string, array{label: string, note: string, tokens: array<string, string>}>
     */
    private const SKINS = [
        'illuminated' => [
            'label' => 'Illuminated',
            'note' => 'Navy & gold — the shipped identity',
            'tokens' => [],
        ],
        'parchment' => [
            'label' => 'Parchment',
            'note' => 'Warm sepia on cream',
            'tokens' => [
                '--ordo-navy' => '#5b4a36',
                '--ordo-navy2' => '#6f5a41',
                '--ordo-ink' => '#2e2a23',
                '--ordo-muted' => '#7a7060',
                '--ordo-gold' => '#a98a55',
                '--ordo-gold2' => '#c2a56e',
                '--ordo-gold-ink' => '#7c6338',
                '--ordo-rule' => '#dcc9a6',
                '--ordo-keyline' => 'rgba(169, 138, 85, 0.42)',
                '--ordo-cream' => '#faf6ec',
                '--ordo-parch' => '#f4efe2',
                '--ordo-paper' => '#fffdf8',
            ],
        ],
        'slate' => [
            'label' => 'Slate',
            'note' => 'Cool charcoal & steel',
            'tokens' => [
                '--ordo-navy' => '#2b3138',
                '--ordo-navy2' => '#3a424c',
                '--ordo-ink' => '#1f242a',
                '--ordo-muted' => '#6b7480',
                '--ordo-gold' => '#8a9bb0',
                '--ordo-gold2' => '#a7b6c6',
                '--ordo-gold-ink' => '#4a5a6e',
                '--ordo-rule' => '#c4ccd6',
                '--ordo-keyline' => 'rgba(138, 155, 176, 0.42)',
                '--ordo-cream' => '#f3f5f8',
                '--ordo-parch' => '#eef1f4',
                '--ordo-paper' => '#fbfcfe',
            ],
        ],
    ];

    /** Whether a key names a known skin. */
    public static function has(string $key): bool
    {
        return isset(self::SKINS[$key]);
    }

    /** A key coerced to a known skin, falling back to the baseline. */
    public static function normalise(string $key): string
    {
        return self::has($key) ? $key : self::DEFAULT;
    }

    /**
     * The choices for the settings screen: each key with its human label and note.
     * Labels/notes are translated at the point of display, not here, so this stays a
     * WordPress-free registry the tests can read directly.
     *
     * @return array<string, array{label: string, note: string}>
     */
    public static function choices(): array
    {
        $out = [];
        foreach (self::SKINS as $key => $skin) {
            $out[$key] = ['label' => $skin['label'], 'note' => $skin['note']];
        }

        return $out;
    }

    /**
     * The inline stylesheet that applies a skin — the brand-token overrides scoped to
     * .ordo — or an empty string for the baseline (or an unknown key), which needs no
     * overriding because the base stylesheet already defines it.
     */
    public static function css(string $key): string
    {
        $tokens = self::SKINS[self::normalise($key)]['tokens'];
        if ($tokens === []) {
            return '';
        }

        $declarations = '';
        foreach ($tokens as $property => $value) {
            $declarations .= $property . ': ' . $value . '; ';
        }

        return '.ordo { ' . rtrim($declarations) . ' }';
    }
}
