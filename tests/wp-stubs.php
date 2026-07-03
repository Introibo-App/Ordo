<?php

/**
 * Minimal WordPress function stubs for the render harness.
 *
 * Loaded by the test bootstrap so the surface renderers — which call WordPress's
 * escaping, translation and option helpers — can run and be asserted on outside a
 * WordPress install. Each stub is guarded by function_exists, so a real WordPress
 * environment is never overridden. They implement just enough behaviour to render:
 * escaping actually escapes, translation is a pass-through, and options are read
 * from a test-controlled global.
 *
 * @package Introibo\Ordo\Tests
 */

declare(strict_types=1);

if (!function_exists('esc_html')) {
    function esc_html($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url): string
    {
        return htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default'): string
    {
        return (string) $text;
    }
}

if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default'): string
    {
        return (string) $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default'): string
    {
        return esc_html($text);
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default'): string
    {
        return esc_attr($text);
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null): string
    {
        return 'https://example.test' . (string) $path;
    }
}

if (!function_exists('date_i18n')) {
    /**
     * @param int|false $timestamp
     */
    function date_i18n(string $format, $timestamp = false): string
    {
        return gmdate($format, $timestamp === false ? time() : (int) $timestamp);
    }
}

if (!function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        return $GLOBALS['__ordo_options'][$name] ?? $default;
    }
}

if (!function_exists('get_permalink')) {
    /**
     * @param int|object $post
     * @return string
     */
    function get_permalink($post = 0)
    {
        return 'https://example.test/calendar/';
    }
}

if (!function_exists('add_query_arg')) {
    /**
     * A minimal stand-in for the array form the plugin uses: add_query_arg($args, $url).
     *
     * @param array<string, int|string>|string $args
     * @param string                           $url
     */
    function add_query_arg($args, $url = ''): string
    {
        if (!is_array($args)) {
            return (string) $url;
        }
        $separator = strpos((string) $url, '?') !== false ? '&' : '?';

        return (string) $url . $separator . http_build_query($args);
    }
}

if (!function_exists('shortcode_atts')) {
    /**
     * @param array<string, mixed> $defaults
     * @param mixed                $atts
     * @return array<string, mixed>
     */
    function shortcode_atts(array $defaults, $atts, string $shortcode = ''): array
    {
        $atts = is_array($atts) ? $atts : [];
        $out = [];
        foreach ($defaults as $key => $value) {
            $out[$key] = array_key_exists($key, $atts) ? $atts[$key] : $value;
        }

        return $out;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback): void
    {
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all'): void
    {
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false): void
    {
    }
}

if (!function_exists('wp_localize_script')) {
    /**
     * @param array<string, mixed> $l10n
     */
    function wp_localize_script($handle, $object_name, $l10n): bool
    {
        return true;
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = ''): string
    {
        return 'https://example.test/wp-json/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url): string
    {
        return (string) $url;
    }
}

if (!function_exists('add_action')) {
    /**
     * @param string $hook
     * @param mixed  $callback
     */
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1): bool
    {
        return true;
    }
}
