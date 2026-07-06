<?php

declare(strict_types=1);

namespace Directorium\Ordo\Tests\Admin;

use Directorium\Ordo\Admin\Settings;
use PHPUnit\Framework\TestCase;

/**
 * The settings sanitiser and tab logic (Epic #20). The sanitiser is the security
 * boundary for the options screen: it whitelists every field, preserves the values of
 * fields not on the submitted tab, and reads an unchecked checkbox as a definite "off"
 * (thanks to its hidden companion) rather than a preserved old value.
 */
final class SettingsTest extends TestCase
{
    public function testDefaultsAreTheShippedConfiguration(): void
    {
        self::assertSame(
            ['calendar' => 'universal', 'palette' => 'illuminated', 'show_commemorations' => true],
            Settings::defaults()
        );
    }

    public function testEmptyInputYieldsDefaultsWhenNothingIsStored(): void
    {
        self::assertSame(Settings::defaults(), Settings::sanitizeSettings([], []));
    }

    public function testWhitelistsCalendarAndPalette(): void
    {
        $out = Settings::sanitizeSettings(['calendar' => 'sspx', 'palette' => 'slate'], []);

        self::assertSame('sspx', $out['calendar']);
        self::assertSame('slate', $out['palette']);
    }

    public function testRejectsUnknownValuesToSafeDefaults(): void
    {
        $out = Settings::sanitizeSettings(['calendar' => 'byzantine', 'palette' => 'neon'], []);

        self::assertSame('universal', $out['calendar']);
        self::assertSame('illuminated', $out['palette']);
    }

    public function testPreservesFieldsAbsentFromThisTabSubmission(): void
    {
        $current = ['calendar' => 'sspx', 'palette' => 'slate', 'show_commemorations' => false];

        // Only the Appearance tab was submitted (palette present, others absent).
        $out = Settings::sanitizeSettings(['palette' => 'parchment'], $current);

        self::assertSame('parchment', $out['palette']);
        self::assertSame('sspx', $out['calendar']);
        self::assertFalse($out['show_commemorations']);
    }

    public function testCheckboxOffAndOn(): void
    {
        $on = Settings::sanitizeSettings(['show_commemorations' => '1'], []);
        self::assertTrue($on['show_commemorations']);

        // The hidden companion posts "0" when the box is unchecked.
        $off = Settings::sanitizeSettings(['show_commemorations' => '0'], ['show_commemorations' => true]);
        self::assertFalse($off['show_commemorations']);
    }

    public function testTabsAndActiveTabResolution(): void
    {
        self::assertSame(['calendar', 'appearance', 'display', 'about'], array_keys(Settings::tabs()));
        self::assertSame('appearance', Settings::activeTab('appearance'));
        self::assertSame('calendar', Settings::activeTab('nonsense'));
        self::assertSame('calendar', Settings::activeTab(''));
    }
}
