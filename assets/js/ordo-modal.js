/*!
 * Ordo — day-view modal.
 *
 * Progressive enhancement over the server-rendered surfaces. Every day link carries
 * data-ordo-day and a real href to its /ordo/YYYY-MM-DD/ page; without JavaScript
 * (or if the fetch fails) that link is followed. With JavaScript this intercepts the
 * click, fetches the day from the REST route, and shows it in an accessible modal —
 * focus trapped, dismissable by Escape or the backdrop, focus restored on close — and
 * offers a day-by-day navigator in the footer. No dependencies.
 */
( function () {
	'use strict';

	var cfg = window.ordoModal || {};
	if ( ! cfg.rest || typeof window.fetch !== 'function' ) {
		return; // No endpoint or no fetch: leave the links as the plain fallback.
	}

	var COLOURS = [ 'white', 'red', 'green', 'violet', 'black', 'rose' ];

	var backdrop, dialog, barLabel, title, body, navTrack;
	var lastFocus = null;
	var fallbackHref = '';
	var currentIso = '';
	var keydownBound = null;

	function litVar( colour ) {
		return COLOURS.indexOf( colour ) === -1 ? '--ordo-lit-white' : '--ordo-lit-' + colour;
	}

	function shiftIso( iso, delta ) {
		var parts = iso.split( '-' );
		var d = new Date( Date.UTC( +parts[ 0 ], +parts[ 1 ] - 1, +parts[ 2 ] ) );
		d.setUTCDate( d.getUTCDate() + delta );
		var m = ( '0' + ( d.getUTCMonth() + 1 ) ).slice( -2 );
		var day = ( '0' + d.getUTCDate() ).slice( -2 );
		return d.getUTCFullYear() + '-' + m + '-' + day;
	}

	function focusable() {
		return Array.prototype.slice.call(
			dialog.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)
		);
	}

	function onKeydown( event ) {
		if ( event.key === 'Escape' || event.keyCode === 27 ) {
			event.preventDefault();
			close();
			return;
		}
		if ( event.key !== 'Tab' && event.keyCode !== 9 ) {
			return;
		}
		var items = focusable();
		if ( items.length === 0 ) {
			event.preventDefault();
			dialog.focus();
			return;
		}
		var first = items[ 0 ];
		var last = items[ items.length - 1 ];
		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function open( trigger ) {
		lastFocus = trigger;
		fallbackHref = trigger.getAttribute( 'href' ) || '';
		backdrop.hidden = false;
		document.documentElement.classList.add( 'ordo-modal-open' );
		keydownBound = onKeydown;
		document.addEventListener( 'keydown', keydownBound );
		dialog.focus();
	}

	function close() {
		backdrop.hidden = true;
		document.documentElement.classList.remove( 'ordo-modal-open' );
		if ( keydownBound ) {
			document.removeEventListener( 'keydown', keydownBound );
			keydownBound = null;
		}
		if ( lastFocus && typeof lastFocus.focus === 'function' ) {
			lastFocus.focus();
		}
	}

	function buildNav( entries ) {
		var html = '';
		for ( var i = 0; i < entries.length; i++ ) {
			var d = entries[ i ];
			var current = d.iso === currentIso ? ' ordo-is-current' : '';
			html += '<button type="button" class="ordo-strip__day' + current + '" data-ordo-navday="' + d.iso + '">'
				+ '<span class="ordo-strip__wd">' + d.wd + '</span>'
				+ '<span class="ordo-strip__dd">' + d.dd + '</span>'
				+ '<span class="ordo-strip__dot" style="background:var(' + litVar( d.colour ) + ')"></span>'
				+ '</button>';
		}
		navTrack.innerHTML = html;
	}

	function render( payload ) {
		currentIso = payload.iso;
		barLabel.textContent = payload.barLabel || '';
		title.textContent = payload.title || '';
		body.innerHTML = payload.html || '';
		buildNav( payload.nav || [] );
	}

	function load( iso ) {
		window
			.fetch( cfg.rest + iso, { headers: { Accept: 'application/json' } } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'ordo: ' + response.status );
				}
				return response.json();
			} )
			.then( render )
			.catch( function () {
				// Fetch failed — fall back to the shareable page for the day.
				if ( fallbackHref ) {
					window.location.href = fallbackHref;
				} else {
					close();
				}
			} );
	}

	function init() {
		backdrop = document.querySelector( '[data-ordo-modal]' );
		if ( ! backdrop ) {
			return;
		}
		dialog = backdrop.querySelector( '.ordo-modal' );
		barLabel = backdrop.querySelector( '[data-ordo-barlabel]' );
		title = backdrop.querySelector( '[data-ordo-title]' );
		body = backdrop.querySelector( '[data-ordo-body]' );
		navTrack = backdrop.querySelector( '[data-ordo-navtrack]' );

		// Delegate: any day trigger opens the modal instead of navigating.
		document.addEventListener( 'click', function ( event ) {
			var trigger = event.target.closest ? event.target.closest( '[data-ordo-day]' ) : null;
			if ( ! trigger ) {
				return;
			}
			event.preventDefault();
			open( trigger );
			load( trigger.getAttribute( 'data-ordo-day' ) );
		} );

		backdrop.addEventListener( 'click', function ( event ) {
			if ( event.target === backdrop ) {
				close();
			}
		} );
		backdrop.querySelector( '[data-ordo-close]' ).addEventListener( 'click', close );
		backdrop.querySelector( '[data-ordo-prev]' ).addEventListener( 'click', function () {
			load( shiftIso( currentIso, -1 ) );
		} );
		backdrop.querySelector( '[data-ordo-next]' ).addEventListener( 'click', function () {
			load( shiftIso( currentIso, 1 ) );
		} );
		navTrack.addEventListener( 'click', function ( event ) {
			var cell = event.target.closest ? event.target.closest( '[data-ordo-navday]' ) : null;
			if ( cell ) {
				load( cell.getAttribute( 'data-ordo-navday' ) );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
