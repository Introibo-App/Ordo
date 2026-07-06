<?php

declare(strict_types=1);

namespace Directorium\Ordo;

/**
 * Registers the block-editor counterparts of the shortcodes.
 *
 * Each block is server-rendered by the very same callback its shortcode uses, so
 * the editor (via `wp.serverSideRender`) and the front end always agree. No build
 * step is required: the editor scripts are plain browser JavaScript using the
 * block and element APIs WordPress already ships, and each block's metadata is
 * read from its bundled block.json.
 */
final class Blocks
{
    private Shortcodes $shortcodes;

    public function __construct(Shortcodes $shortcodes)
    {
        $this->shortcodes = $shortcodes;
    }

    public function register(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type(ORDO_DIR . 'blocks/today', [
            'render_callback' => [$this->shortcodes, 'renderToday'],
        ]);
        register_block_type(ORDO_DIR . 'blocks/calendar-strip', [
            'render_callback' => [$this->shortcodes, 'renderStrip'],
        ]);
        register_block_type(ORDO_DIR . 'blocks/calendar', [
            'render_callback' => [$this->shortcodes, 'renderCalendar'],
        ]);
    }
}
