/**
 * Consent-gated embeds: the iframe is created only after consent.
 *
 * Consent comes from (a) a click on the placeholder, (b) the visitor's earlier
 * "always load" choice for that provider, stored in localStorage, or (c) the
 * WP Consent API, when a consent banner implementing it is active.
 */
const SELECTOR = '.bce-embed[data-bce-src]:not(.is-loaded)';
const storageKey = ( provider ) => `bce-consent:${ provider }`;

const remembered = ( provider ) => {
	try {
		return window.localStorage.getItem( storageKey( provider ) ) === '1';
	} catch ( e ) {
		return false;
	}
};

const consentApiAllows = ( category ) =>
	typeof window.wp_has_consent === 'function' &&
	window.wp_has_consent( category );

const load = ( el ) => {
	const iframe = document.createElement( 'iframe' );
	iframe.src = el.dataset.bceSrc;
	iframe.title = el.dataset.bceTitle;
	iframe.loading = 'lazy';
	iframe.allow =
		'accelerometer; autoplay; clipboard-write; encrypted-media; fullscreen; gyroscope; picture-in-picture';
	iframe.allowFullscreen = true;
	iframe.referrerPolicy = 'strict-origin-when-cross-origin';
	iframe.className = 'bce-embed__frame';
	el.replaceChildren( iframe );
	el.classList.add( 'is-loaded' );
};

const loadAll = ( match ) =>
	document.querySelectorAll( SELECTOR ).forEach( ( el ) => {
		if ( match( el ) ) {
			load( el );
		}
	} );

const init = () => {
	document.querySelectorAll( SELECTOR ).forEach( ( el ) => {
		const { bceProvider: provider, bceCategory: category } = el.dataset;
		if ( remembered( provider ) || consentApiAllows( category ) ) {
			load( el );
			return;
		}
		el.querySelector( '.bce-embed__load' )?.addEventListener(
			'click',
			() => {
				if ( ! el.querySelector( '.bce-embed__remember' )?.checked ) {
					load( el );
					return;
				}
				try {
					window.localStorage.setItem( storageKey( provider ), '1' );
				} catch ( e ) {}
				loadAll( ( other ) => other.dataset.bceProvider === provider );
			}
		);
	} );
};

// WP Consent API: a banner granting a category later loads everything in it.
document.addEventListener( 'wp_listen_for_consent_change', ( event ) => {
	Object.entries( event.detail || {} ).forEach( ( [ category, value ] ) => {
		if ( value === 'allow' ) {
			loadAll( ( el ) => el.dataset.bceCategory === category );
		}
	} );
} );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
