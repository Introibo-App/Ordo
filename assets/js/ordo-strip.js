/*!
 * Ordo — masthead strip enhancement.
 *
 * The strip is server-rendered and fully usable without JavaScript: the window
 * scrolls and each day is a link. When this script runs it upgrades the strip to
 * slide one day at a time via the chevrons, sizing the days to a responsive count
 * so every day stays reachable on any width. No dependencies.
 */
( function () {
	'use strict';

	function enhance( strip ) {
		var track = strip.querySelector( '[data-ordo-track]' );
		var win = strip.querySelector( '[data-ordo-win]' );
		var left = strip.querySelector( '[data-ordo-left]' );
		var right = strip.querySelector( '[data-ordo-right]' );

		if ( ! track || ! win || track.children.length === 0 ) {
			return;
		}

		var days = track.children;
		var start = 0;

		function visible() {
			var width = win.clientWidth;
			if ( width < 440 ) {
				return 3;
			}
			if ( width < 680 ) {
				return 5;
			}
			return 7;
		}

		function clamp( value, count ) {
			return Math.max( 0, Math.min( value, days.length - count ) );
		}

		function layout() {
			var count = Math.min( visible(), days.length );
			var cellWidth = win.clientWidth / count;
			start = clamp( start, count );
			for ( var i = 0; i < days.length; i++ ) {
				days[ i ].style.flex = '0 0 ' + cellWidth + 'px';
				days[ i ].style.width = cellWidth + 'px';
			}
			track.style.transform = 'translateX(' + ( -start * cellWidth ) + 'px)';
		}

		var todayIndex = -1;
		for ( var i = 0; i < days.length; i++ ) {
			if ( days[ i ].getAttribute( 'data-ordo-today' ) === '1' ) {
				todayIndex = i;
				break;
			}
		}
		if ( todayIndex > 0 ) {
			start = todayIndex - 1;
		}

		if ( left ) {
			left.addEventListener( 'click', function () {
				start = Math.max( 0, start - 1 );
				layout();
			} );
		}
		if ( right ) {
			right.addEventListener( 'click', function () {
				start = clamp( start + 1, Math.min( visible(), days.length ) );
				layout();
			} );
		}

		var resizeTimer = null;
		window.addEventListener( 'resize', function () {
			if ( resizeTimer ) {
				clearTimeout( resizeTimer );
			}
			resizeTimer = setTimeout( layout, 120 );
		} );

		strip.classList.add( 'is-enhanced' );
		layout();
	}

	function init() {
		var strips = document.querySelectorAll( '[data-ordo-strip]' );
		for ( var i = 0; i < strips.length; i++ ) {
			enhance( strips[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
