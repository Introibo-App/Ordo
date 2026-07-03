<?php

declare(strict_types=1);

namespace Introibo\Ordo\Surface;

/**
 * The rubric system and particular calendar the site is configured to follow —
 * the "which calendar am I looking at?" context every surface carries.
 *
 * v0.1.0 ships a single rubric (the 1962 rubrics of 1960) and defaults to the
 * universal calendar; a site selects a particular calendar (e.g. SSPX) in the
 * settings. The one stored calendar key both selects the engine overlay and
 * labels the context chip, so the two can never drift apart.
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

    private function __construct(string $calendarKey)
    {
        $this->calendarKey = isset(self::CALENDARS[$calendarKey]) ? $calendarKey : 'universal';
    }

    /** The context configured in the site's settings. */
    public static function current(): self
    {
        $settings = get_option(self::OPTION, []);
        $calendar = is_array($settings) && isset($settings['calendar'])
            ? (string) $settings['calendar']
            : 'universal';

        return new self($calendar);
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
}
