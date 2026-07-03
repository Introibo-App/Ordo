/**
 * Editor registration for the "Ordo — Today" block.
 *
 * A dynamic (server-rendered) block: the editor shows exactly what the front end
 * will render via wp.serverSideRender, and there is no saved markup. Written in
 * plain browser JavaScript against the APIs WordPress already ships, so no build
 * step is required. The block's title, category and attributes come from its
 * block.json, registered server-side.
 */
( function ( blocks, element, serverSideRender ) {
	'use strict';

	blocks.registerBlockType( 'ordo/today', {
		edit: function ( props ) {
			return element.createElement( serverSideRender, {
				block: 'ordo/today',
				attributes: props.attributes
			} );
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender );
