<?php

/**
 * Uninstall cleanup (issue #5).
 *
 * Runs only when a site administrator deletes the plugin through WordPress, which
 * defines WP_UNINSTALL_PLUGIN before including this file. It removes every option
 * and transient the plugin created — matched by the `ordo_` prefix — so no rows
 * with that prefix remain. On multisite it cleans every site plus the network's
 * site-transients. Deactivation never runs this; only deletion does.
 *
 * Note: transients held in an external (non-database) object cache cannot be
 * enumerated by prefix and are left for the cache's own expiry.
 *
 * @package Introibo\Ordo
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Not a genuine uninstall request.
}

global $wpdb;

// Must match ORDO_OPTION_PREFIX in ordo.php. Kept literal because uninstall.php runs
// standalone, without the bootstrap that defines that constant.
$ordoPrefix = 'ordo_';

$ordoDeleteFromOptions = static function () use ($wpdb, $ordoPrefix): void {
    $patterns = array(
        $wpdb->esc_like($ordoPrefix) . '%',
        $wpdb->esc_like('_transient_' . $ordoPrefix) . '%',
        $wpdb->esc_like('_transient_timeout_' . $ordoPrefix) . '%',
        $wpdb->esc_like('_site_transient_' . $ordoPrefix) . '%',
        $wpdb->esc_like('_site_transient_timeout_' . $ordoPrefix) . '%',
    );
    $conditions = implode(' OR ', array_fill(0, count($patterns), 'option_name LIKE %s'));
    $wpdb->query(
        $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE {$conditions}", $patterns)
    );
};

if (is_multisite()) {
    $blogIds = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    foreach ($blogIds as $blogId) {
        switch_to_blog((int) $blogId);
        $ordoDeleteFromOptions();
        restore_current_blog();
    }

    // Network-level site transients live in sitemeta on multisite.
    $networkPatterns = array(
        $wpdb->esc_like('_site_transient_' . $ordoPrefix) . '%',
        $wpdb->esc_like('_site_transient_timeout_' . $ordoPrefix) . '%',
    );
    $networkConditions = implode(' OR ', array_fill(0, count($networkPatterns), 'meta_key LIKE %s'));
    $wpdb->query(
        $wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE {$networkConditions}", $networkPatterns)
    );
} else {
    $ordoDeleteFromOptions();
}
