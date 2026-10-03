/**
 * Consent-gated embeds and sections: nothing from the third party is created
 * until consent.
 *
 * Consent comes from (a) a click on the placeholder, (b) the visitor's earlier
 * "always load" choice for that provider, stored in localStorage, or (c) the
 * WP Consent API, when a consent banner implementing it is active.
 *
 * An embed carries its iframe URL in data-bce-src; a section carries its
 * blocks in an inert <template>. Gates revealed by a section are initialised
 * then; one for the same provider loads at once, a different provider keeps
 * asking, because its consent is a separate one.
 */
const SELECTOR = '.bce-embed[data-bce-provider]:not(.is-loaded)';
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

const createIframe = ( el ) => {
	const iframe = document.createElement( 'iframe' );
	iframe.src = el.dataset.bceSrc;
	iframe.title = el.dataset.bceTitle;
	iframe.loading = 'lazy';
	iframe.allow =
		'accelerometer; autoplay; clipboard-write; encrypted-media; fullscreen; gyroscope; picture-in-picture';
	iframe.allowFullscreen = true;
	iframe.referrerPolicy = 'strict-origin-when-cross-origin';
	iframe.className = 'bce-embed__frame';
	return iframe;
};

const load = ( el ) => {
	if ( el.classList.contains( 'is-loaded' ) ) {
		return;
	}
	const template = el.querySelector( ':scope > template.bce-embed__content' );
	// importNode (not cloneNode) so <script> elements inside the section run.
	el.replaceChildren(
		template
			? document.importNode( template.content, true )
			: createIframe( el )
	);
	el.classList.add( 'is-loaded' );
	if ( template ) {
		el.querySelectorAll( SELECTOR ).forEach( ( inner ) =>
			inner.dataset.bceProvider === el.dataset.bceProvider
				? load( inner )
				: initGate( inner )
		);
	}
};

const loadAll = ( match ) =>
	document.querySelectorAll( SELECTOR ).forEach( ( el ) => {
		if ( match( el ) ) {
			load( el );
		}
	} );

const initGate = ( el ) => {
	const { bceProvider: provider, bceCategory: category } = el.dataset;
	if ( remembered( provider ) || consentApiAllows( category ) ) {
		load( el );
		return;
	}
	el.querySelector( ':scope > .bce-embed__notice .bce-embed__load' )?.addEventListener(
		'click',
		() => {
			const remember = el.querySelector(
				':scope > .bce-embed__notice .bce-embed__remember'
			);
			if ( ! remember?.checked ) {
				load( el );
				return;
			}
			try {
				window.localStorage.setItem( storageKey( provider ), '1' );
			} catch ( e ) {}
			loadAll( ( other ) => other.dataset.bceProvider === provider );
		}
	);
};

// WP Consent API: a banner granting a category later loads everything in it.
document.addEventListener( 'wp_listen_for_consent_change', ( event ) => {
	Object.entries( event.detail || {} ).forEach( ( [ category, value ] ) => {
		if ( value === 'allow' ) {
			loadAll( ( el ) => el.dataset.bceCategory === category );
		}
	} );
} );

const init = () => document.querySelectorAll( SELECTOR ).forEach( initGate );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
