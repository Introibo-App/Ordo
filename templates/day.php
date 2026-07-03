<?php

/**
 * Template for the /ordo/YYYY-MM-DD/ pretty page.
 *
 * Loaded by {@see \Introibo\Ordo\DayRoute::template()} only when the request carries
 * a valid date query var. It leans on the active theme for the surrounding chrome
 * (header, footer) and renders the day view into the content area, so the page looks
 * native on any theme. All markup comes back already escaped from the day view.
 *
 * @package Introibo\Ordo
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$ordo_day_html = \Introibo\Ordo\DayRoute::renderCurrentPage();

echo '<main class="ordo-daypage-main" style="max-width:760px;margin:0 auto;padding:32px 20px;">';
echo $ordo_day_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fully escaped in the day view
echo '</main>';

get_footer();
