<?php

declare(strict_types=1);

namespace Introibo\Ordo\Tests\View;

use Introibo\Ordo\View\Skin;
use PHPUnit\Framework\TestCase;

/**
 * The white-label skins (Epic #20). The baseline needs no overrides because the base
 * stylesheet already is it; the others emit brand-token overrides scoped to .ordo,
 * leaving the liturgical colours untouched.
 */
final class SkinTest extends TestCase
{
    public function testKnownAndUnknownKeys(): void
    {
        self::assertTrue(Skin::has('illuminated'));
        self::assertTrue(Skin::has('parchment'));
        self::assertTrue(Skin::has('slate'));
        self::assertFalse(Skin::has('rococo'));

        self::assertSame('illuminated', Skin::normalise('rococo'));
        self::assertSame('slate', Skin::normalise('slate'));
        self::assertSame(Skin::DEFAULT, 'illuminated');
    }

    public function testChoicesCoverEverySkin(): void
    {
        $choices = Skin::choices();

        self::assertSame(['illuminated', 'parchment', 'slate'], array_keys($choices));
        self::assertSame('Parchment', $choices['parchment']['label']);
        self::assertArrayHasKey('note', $choices['slate']);
    }

    public function testBaselineEmitsNoOverrides(): void
    {
        self::assertSame('', Skin::css('illuminated'));
        // An unknown key falls back to the baseline, which also needs no overrides.
        self::assertSame('', Skin::css('nope'));
    }

    public function testSkinEmitsScopedBrandOverridesOnly(): void
    {
        $css = Skin::css('parchment');

        self::assertStringStartsWith('.ordo {', $css);
        self::assertStringContainsString('--ordo-navy: #5b4a36;', $css);
        self::assertStringContainsString('--ordo-gold: #a98a55;', $css);
        // Liturgical colours are fixed by the rite and never reskinned.
        self::assertStringNotContainsString('--ordo-lit-', $css);
        self::assertStringNotContainsString('--ordo-on-', $css);
    }
}
