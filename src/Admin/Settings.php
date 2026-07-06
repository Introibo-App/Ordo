<?php

declare(strict_types=1);

namespace Directorium\Ordo\Admin;

use Directorium\Ordo\View\Skin;

/**
 * The tabbed settings screen (Surface 5 of the approved mockup).
 *
 * One option — ordo_settings — holds the site's whole configuration: the particular
 * calendar, the white-label skin, and whether commemorations are shown. The screen
 * uses the WordPress Settings API for persistence and nonce protection but renders its
 * own elevated chrome (the gold cross header, the tabbed cards) to match the mockup.
 * Tabs are query-string links, so a tab is shareable and works without JavaScript; the
 * Settings-API redirect after a save preserves the tab via the referer it stores.
 *
 * v0.1.0 exposes a deliberately minimal, fully-wired option set — every control here
 * changes something a visitor sees. The rubric system is shown read-only because 1962
 * is the only system this release computes; per-surface show/hide/lock arrive later.
 */
final class Settings
{
    /** The admin page slug (under Settings) and the Settings-API option/group name. */
    public const PAGE = 'ordo';
    public const OPTION = 'ordo_settings';
    public const OPTION_GROUP = 'ordo_settings';

    /** The stylesheet handle for the admin-only chrome. */
    private const ADMIN_STYLE = 'ordo-admin';

    /** Calendar preset key → label and the society it layers over the universal calendar. */
    private const CALENDARS = [
        'universal' => ['label' => 'Universal 1962', 'note' => 'No particular overlay', 'default' => true],
        'sspx' => ['label' => 'SSPX', 'note' => 'Society of St Pius X', 'default' => false],
        'fssp' => ['label' => 'FSSP', 'note' => 'Priestly Fraternity of St Peter', 'default' => false],
        'icksp' => ['label' => 'ICKSP', 'note' => 'Institute of Christ the King', 'default' => false],
    ];

    /** Register the admin menu entry, the setting, and the page's own stylesheet. */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage']);
        add_action('admin_init', [$this, 'registerSetting']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyle']);
    }

    /** Add the "Ordo" page under the Settings menu, gated to administrators. */
    public function addPage(): void
    {
        add_options_page(
            __('Ordo Settings', 'ordo'),
            __('Ordo', 'ordo'),
            'manage_options',
            self::PAGE,
            [$this, 'render']
        );
    }

    /** Register the single option with its whitelisting sanitiser and defaults. */
    public function registerSetting(): void
    {
        register_setting(self::OPTION_GROUP, self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => self::defaults(),
        ]);
    }

    /** Enqueue the admin stylesheet only on the Ordo settings page. */
    public function enqueueAdminStyle(string $hookSuffix): void
    {
        if ($hookSuffix !== 'settings_page_' . self::PAGE) {
            return;
        }

        $path = ORDO_DIR . 'assets/css/ordo-admin.css';
        $version = file_exists($path) ? (string) filemtime($path) : ORDO_VERSION;
        wp_enqueue_style(self::ADMIN_STYLE, ORDO_URL . 'assets/css/ordo-admin.css', [], $version);
    }

    /**
     * The Settings-API sanitise callback: merge the submitted fields over the current
     * stored settings, keeping only known keys and coercing each to a valid value. Only
     * the fields on the submitted tab are present, so unlisted keys keep their value.
     *
     * @param mixed $input
     * @return array{calendar: string, palette: string, show_commemorations: bool}
     */
    public function sanitize($input): array
    {
        $current = get_option(self::OPTION, []);

        return self::sanitizeSettings(
            is_array($input) ? $input : [],
            is_array($current) ? $current : []
        );
    }

    /**
     * The pure sanitising rule, free of WordPress so it can be tested directly: start
     * from the defaults overlaid with the current settings, then apply any present,
     * valid submitted field. Checkboxes post a hidden "0" companion, so an unchecked
     * box arrives as a present "0" rather than a preserved old value.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $current
     * @return array{calendar: string, palette: string, show_commemorations: bool}
     */
    public static function sanitizeSettings(array $input, array $current): array
    {
        $out = self::defaults();

        if (isset($current['calendar']) && isset(self::CALENDARS[(string) $current['calendar']])) {
            $out['calendar'] = (string) $current['calendar'];
        }
        if (isset($current['palette']) && Skin::has((string) $current['palette'])) {
            $out['palette'] = (string) $current['palette'];
        }
        if (isset($current['show_commemorations'])) {
            $out['show_commemorations'] = (bool) $current['show_commemorations'];
        }

        if (isset($input['calendar']) && isset(self::CALENDARS[(string) $input['calendar']])) {
            $out['calendar'] = (string) $input['calendar'];
        }
        if (isset($input['palette'])) {
            $out['palette'] = Skin::normalise((string) $input['palette']);
        }
        if (isset($input['show_commemorations'])) {
            $out['show_commemorations'] = self::truthy($input['show_commemorations']);
        }

        return $out;
    }

    /**
     * The shipped defaults: the universal calendar, the illuminated skin, commemorations
     * shown. Mirrors the seed in {@see \Directorium\Ordo\Lifecycle}.
     *
     * @return array{calendar: string, palette: string, show_commemorations: bool}
     */
    public static function defaults(): array
    {
        return [
            'calendar' => 'universal',
            'palette' => Skin::DEFAULT,
            'show_commemorations' => true,
        ];
    }

    /**
     * The tab keys in display order, each with its label — the labels are translated at
     * render time so this stays a plain registry.
     *
     * @return array<string, string>
     */
    public static function tabs(): array
    {
        return [
            'calendar' => 'Calendar',
            'appearance' => 'Appearance',
            'display' => 'Display',
            'about' => 'About',
        ];
    }

    /** Resolve a requested tab to a known one, defaulting to the first (Calendar). */
    public static function activeTab(string $requested): string
    {
        return isset(self::tabs()[$requested]) ? $requested : 'calendar';
    }

    /** Render the settings page. Echoes directly, as an admin page callback does. */
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $requested = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : '';
        $tab = self::activeTab($requested);
        $settings = self::sanitizeSettings([], (array) get_option(self::OPTION, []));

        echo '<div class="wrap ordo-admin">';
        settings_errors();
        $this->renderHeader();
        $this->renderTabNav($tab);

        if ($tab === 'about') {
            $this->renderAbout();
        } else {
            echo '<form method="post" action="options.php" class="ordo-admin__form">';
            settings_fields(self::OPTION_GROUP);
            echo '<div class="ordo-admin__body">';
            switch ($tab) {
                case 'appearance':
                    $this->renderAppearanceTab($settings);
                    break;
                case 'display':
                    $this->renderDisplayTab($settings);
                    break;
                case 'calendar':
                default:
                    $this->renderCalendarTab($settings);
                    break;
            }
            echo '</div>';
            submit_button(__('Save changes', 'ordo'));
            echo '</form>';
        }

        echo '</div>';
    }

    private function renderHeader(): void
    {
        echo '<div class="ordo-admin__top">';
        echo '<span class="ordo-admin__cross" aria-hidden="true">&#10013;</span>';
        echo '<h1 class="ordo-admin__title">' . esc_html__('Ordo Settings', 'ordo') . '</h1>';
        echo '<span class="ordo-admin__ver">v' . esc_html(ORDO_VERSION) . '</span>';
        echo '</div>';
    }

    private function renderTabNav(string $active): void
    {
        $base = admin_url('options-general.php?page=' . self::PAGE);

        echo '<nav class="ordo-admin__tabs">';
        foreach (self::tabs() as $key => $label) {
            $url = add_query_arg(['tab' => $key], $base);
            $classes = 'ordo-admin__tab' . ($key === $active ? ' is-active' : '');
            $current = $key === $active ? ' aria-current="page"' : '';
            echo '<a class="' . esc_attr($classes) . '" href="' . esc_url($url) . '"' . $current . '>'
                . esc_html($this->tabLabel($key, $label)) . '</a>';
        }
        echo '</nav>';
    }

    /** Translate a tab label by key (the registry keeps source strings for the .pot). */
    private function tabLabel(string $key, string $fallback): string
    {
        switch ($key) {
            case 'calendar':
                return __('Calendar', 'ordo');
            case 'appearance':
                return __('Appearance', 'ordo');
            case 'display':
                return __('Display', 'ordo');
            case 'about':
                return __('About', 'ordo');
            default:
                return $fallback;
        }
    }

    /**
     * @param array{calendar: string, palette: string, show_commemorations: bool} $settings
     */
    private function renderCalendarTab(array $settings): void
    {
        echo '<div class="ordo-card">';
        echo '<h2 class="ordo-card__h">' . esc_html__('Calendar preset', 'ordo') . '</h2>';
        echo '<p class="ordo-card__note">'
            . esc_html__(
                'The calendar this site follows. Presets layer a society’s feasts over the universal 1962 calendar.',
                'ordo'
            )
            . '</p>';

        $name = esc_attr(self::OPTION . '[calendar]');
        foreach (self::CALENDARS as $key => $preset) {
            $checked = $settings['calendar'] === $key;
            echo '<label class="ordo-opt">';
            echo '<input type="radio" name="' . $name . '" value="' . esc_attr($key) . '"'
                . checked($checked, true, false) . '>';
            echo '<span class="ordo-opt__text"><span class="ordo-opt__label">'
                . esc_html($this->calendarLabel($key)) . '</span>';
            echo '<span class="ordo-opt__note">' . esc_html($this->calendarNote($key)) . '</span></span>';
            if ($preset['default']) {
                echo '<span class="ordo-opt__tag">' . esc_html__('Default', 'ordo') . '</span>';
            }
            echo '</label>';
        }
        echo '</div>';

        echo '<div class="ordo-card">';
        echo '<h2 class="ordo-card__h">' . esc_html__('Rubric system', 'ordo') . '</h2>';
        echo '<p class="ordo-card__note">'
            . esc_html__('How the office is computed. v0.1 implements one rubric system.', 'ordo') . '</p>';
        echo '<div class="ordo-opt ordo-opt--locked">';
        echo '<span class="ordo-opt__text"><span class="ordo-opt__label">'
            . esc_html__('Rubrics of 1960', 'ordo') . '</span>';
        echo '<span class="ordo-opt__note">' . esc_html__('The 1962 rubrical system', 'ordo') . '</span></span>';
        echo '<span class="ordo-opt__tag">' . esc_html__('Active', 'ordo') . '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array{calendar: string, palette: string, show_commemorations: bool} $settings
     */
    private function renderAppearanceTab(array $settings): void
    {
        echo '<div class="ordo-card">';
        echo '<h2 class="ordo-card__h">' . esc_html__('Palette', 'ordo') . '</h2>';
        echo '<p class="ordo-card__note">'
            . esc_html__('The colour scheme applied to every Ordo surface on the site.', 'ordo') . '</p>';
        echo '<div class="ordo-palettes">';

        $name = esc_attr(self::OPTION . '[palette]');
        foreach (Skin::choices() as $key => $skin) {
            $checked = $settings['palette'] === $key;
            $classes = 'ordo-palette' . ($checked ? ' is-active' : '');
            echo '<label class="' . esc_attr($classes) . '">';
            echo '<input type="radio" name="' . $name . '" value="' . esc_attr($key) . '"'
                . checked($checked, true, false) . ' class="ordo-palette__input">';
            echo '<span class="ordo-palette__bar">' . $this->skinSwatch($key) . '</span>';
            echo '<span class="ordo-palette__label">' . esc_html($this->skinLabel($key, $skin['label'])) . '</span>';
            echo '<span class="ordo-palette__note">' . esc_html($this->skinNote($key, $skin['note'])) . '</span>';
            echo '</label>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array{calendar: string, palette: string, show_commemorations: bool} $settings
     */
    private function renderDisplayTab(array $settings): void
    {
        echo '<div class="ordo-card">';
        echo '<h2 class="ordo-card__h">' . esc_html__('Day view', 'ordo') . '</h2>';
        echo '<p class="ordo-card__note">'
            . esc_html__('What the calendar day view shows alongside the day’s office.', 'ordo') . '</p>';

        // Hidden companion so an unchecked box posts a definite "0" (see sanitizeSettings).
        echo '<input type="hidden" name="' . esc_attr(self::OPTION . '[show_commemorations]') . '" value="0">';
        echo '<label class="ordo-opt">';
        echo '<input type="checkbox" name="' . esc_attr(self::OPTION . '[show_commemorations]') . '" value="1"'
            . checked($settings['show_commemorations'], true, false) . '>';
        echo '<span class="ordo-opt__text"><span class="ordo-opt__label">'
            . esc_html__('Show commemorations', 'ordo') . '</span>';
        echo '<span class="ordo-opt__note">'
            . esc_html__('List the second office and oration on the day view', 'ordo') . '</span></span>';
        echo '</label>';

        echo '<p class="ordo-card__foot">'
            . esc_html__('More display controls arrive with the Missal and reader milestones.', 'ordo') . '</p>';
        echo '</div>';
    }

    private function renderAbout(): void
    {
        echo '<div class="ordo-admin__body ordo-admin__body--single">';
        echo '<div class="ordo-card">';
        echo '<h2 class="ordo-card__h">' . esc_html__('About Ordo', 'ordo') . '</h2>';
        echo '<p class="ordo-card__note">'
            . esc_html__(
                'Ordo presents the traditional Roman calendar (1962), resolved on this server — no external service.',
                'ordo'
            )
            . '</p>';

        echo '<dl class="ordo-about">';
        $this->aboutRow(__('Version', 'ordo'), 'v' . ORDO_VERSION);
        $this->aboutRow(__('Rubrics', 'ordo'), __('Rubrics of 1960 (the 1962 system)', 'ordo'));
        $this->aboutRow(
            __('Licence', 'ordo'),
            __('GPL-2.0-or-later — bundles Directorium Core (AGPL-3.0-or-later); calendar data is CC0.', 'ordo')
        );
        echo '</dl>';

        echo '<p class="ordo-card__foot">';
        echo '<a href="https://github.com/Directorium/Ordo" target="_blank" rel="noopener noreferrer">'
            . esc_html__('Documentation & source', 'ordo') . '</a>';
        echo '</p>';
        echo '</div>';
        echo '</div>';
    }

    private function aboutRow(string $term, string $value): void
    {
        echo '<div class="ordo-about__row"><dt>' . esc_html($term) . '</dt><dd>' . esc_html($value) . '</dd></div>';
    }

    /** The three-band colour preview for a skin, built from the same tokens it applies. */
    private function skinSwatch(string $key): string
    {
        $bars = [
            'illuminated' => ['#0b1d4a', '#a97f2e', '#f4ecdc'],
            'parchment' => ['#5b4a36', '#a98a55', '#f4efe2'],
            'slate' => ['#2b3138', '#8a9bb0', '#eef1f4'],
        ];
        $colours = $bars[$key] ?? $bars['illuminated'];

        $html = '';
        foreach ($colours as $colour) {
            $html .= '<span style="background:' . esc_attr($colour) . '"></span>';
        }

        return $html;
    }

    private function calendarLabel(string $key): string
    {
        switch ($key) {
            case 'sspx':
                return __('SSPX', 'ordo');
            case 'fssp':
                return __('FSSP', 'ordo');
            case 'icksp':
                return __('ICKSP', 'ordo');
            case 'universal':
            default:
                return __('Universal 1962', 'ordo');
        }
    }

    private function calendarNote(string $key): string
    {
        switch ($key) {
            case 'sspx':
                return __('Society of St Pius X', 'ordo');
            case 'fssp':
                return __('Priestly Fraternity of St Peter', 'ordo');
            case 'icksp':
                return __('Institute of Christ the King', 'ordo');
            case 'universal':
            default:
                return __('No particular overlay', 'ordo');
        }
    }

    private function skinLabel(string $key, string $fallback): string
    {
        switch ($key) {
            case 'parchment':
                return __('Parchment', 'ordo');
            case 'slate':
                return __('Slate', 'ordo');
            case 'illuminated':
                return __('Illuminated', 'ordo');
            default:
                return $fallback;
        }
    }

    private function skinNote(string $key, string $fallback): string
    {
        switch ($key) {
            case 'parchment':
                return __('Warm sepia on cream', 'ordo');
            case 'slate':
                return __('Cool charcoal & steel', 'ordo');
            case 'illuminated':
                return __('Navy & gold — the shipped identity', 'ordo');
            default:
                return $fallback;
        }
    }

    /**
     * @param mixed $value
     */
    private static function truthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
