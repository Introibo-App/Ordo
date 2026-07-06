<?php

declare(strict_types=1);

namespace Directorium\Ordo\Surface;

use Directorium\Ordo\View\Skin;

/**
 * The rubric system, particular calendar and display preferences the site is
 * configured to follow — the "which calendar am I looking at, and how?" context
 * every surface carries.
 *
 * v0.1.0 ships a single rubric (the 1962 rubrics of 1960) and defaults to the
 * universal calendar; a site selects a particular calendar (e.g. SSPX), a skin and
 * whether commemorations are shown in the settings. The one stored settings array
 * both selects the engine overlay and labels the context chip, so the two can never
 * drift apart.
 */
final class Context
{
    /** The settings key holding the plugin's option array (mirrors Lifecycle). */
    private const OPTION = 'ordo_settings';

    /** Calendar key → display label, abbreviation-first per the approved mockup. */
    private const CALENDARS = [
        'universal' => 'Universal 1962',
        'sspx' => 'SSPX',
        'fssp' => 'FSSP',
        'icksp' => 'ICKSP',
    ];

    private string $calendarKey;
    private string $skinKey;
    private bool $showCommemorations;

    private function __construct(string $calendarKey, string $skinKey = Skin::DEFAULT, bool $showCommemorations = true)
    {
        $this->calendarKey = isset(self::CALENDARS[$calendarKey]) ? $calendarKey : 'universal';
        $this->skinKey = Skin::normalise($skinKey);
        $this->showCommemorations = $showCommemorations;
    }

    /** The context configured in the site's settings. */
    public static function current(): self
    {
        $settings = get_option(self::OPTION, []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $calendar = isset($settings['calendar']) ? (string) $settings['calendar'] : 'universal';
        $skin = isset($settings['palette']) ? (string) $settings['palette'] : Skin::DEFAULT;
        // Absent (a pre-0.1 install) means the shipped default of showing commemorations.
        $showCommemorations = !isset($settings['show_commemorations']) || (bool) $settings['show_commemorations'];

        return new self($calendar, $skin, $showCommemorations);
    }

    /** Construct explicitly for a calendar key (used by the render harness). */
    public static function forCalendar(string $calendarKey): self
    {
        return new self($calendarKey);
    }

    /** The calendar slug to resolve under, or null for the universal calendar. */
    public function engineCalendar(): ?string
    {
        return $this->calendarKey === 'universal' ? null : $this->calendarKey;
    }

    /** The active rubric system shown on the context chip (a single value in v0.1.0). */
    public function rubricLabel(): string
    {
        return '1962';
    }

    /** The configured calendar's display label ("Universal 1962", "SSPX", …). */
    public function calendarLabel(): string
    {
        return self::CALENDARS[$this->calendarKey];
    }

    /** The configured white-label skin key ("illuminated", "parchment", "slate"). */
    public function skin(): string
    {
        return $this->skinKey;
    }

    /** Whether the day view should list the day's commemorations (a display preference). */
    public function showCommemorations(): bool
    {
        return $this->showCommemorations;
    }
}
