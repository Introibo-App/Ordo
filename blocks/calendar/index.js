/**
 * Editor registration for the "Ordo — Month calendar" block.
 *
 * A dynamic (server-rendered) block sharing the [ordo_calendar] renderer, previewed
 * in the editor through wp.serverSideRender. Plain browser JavaScript, no build step;
 * metadata (title, category, attributes) comes from block.json, registered server-side.
 */
( function ( blocks, element, serverSideRender ) {
	'use strict';

	blocks.registerBlockType( 'ordo/calendar', {
		edit: function ( props ) {
			return element.createElement( serverSideRender, {
				block: 'ordo/calendar',
				attributes: props.attributes
			} );
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender );
