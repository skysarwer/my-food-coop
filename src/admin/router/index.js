/**
 * A small client-side router for the Co-op screens.
 *
 * URLs stay real wp-admin URLs (admin.php?page=openbb-orders&order=5), so reloads,
 * new tabs, bookmarks and the WordPress menu keep working; navigating between
 * screens just swaps the screen without a page load. The names mirror
 * @wordpress/route (Link, useNavigate, useSearch) so the screens can move to
 * @wordpress/boot routes once that API is stable for plugins.
 */
import { useEffect, useRef, useSyncExternalStore } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import useDiscardGuard from '../hooks/use-discard-guard';
import { hasUnsavedChanges } from '../hooks/use-unsaved-changes';
import { config } from '../utils/config';

// OPENBB_Admin::MENU_SLUG: WordPress gives the Co-op menu item this id.
const ADMIN_MENU_ID = 'toplevel_page_openbb';

const screensBySlug = new Map(
	config.screens.map( ( screen ) => [ screen.slug, screen ] )
);

const listeners = new Set();
let blocker = null;
let current = readLocation( 0 );

function readLocation( key ) {
	const url = new URL( window.location.href );
	const search = Object.fromEntries( url.searchParams );
	const screen = screensBySlug.get( search.page );

	return {
		key,
		href: url.href,
		screen: screen ? screen.name : config.screen,
		search,
	};
}

/**
 * The URL for `href` when the router can show it, otherwise null.
 *
 * @param {string} href
 * @return {URL|null} URL.
 */
export function resolve( href ) {
	let url;
	try {
		url = new URL( href, window.location.href );
	} catch ( e ) {
		return null;
	}
	const routable =
		url.origin === window.location.origin &&
		url.pathname === window.location.pathname &&
		screensBySlug.has( url.searchParams.get( 'page' ) );

	return routable ? url : null;
}

/**
 * Whether a click should be left to the browser (new tab, download, …).
 *
 * @param {MouseEvent} event
 * @return {boolean} Whether to leave it alone.
 */
export const isModifiedClick = ( event ) =>
	event.defaultPrevented ||
	event.button !== 0 ||
	event.metaKey ||
	event.ctrlKey ||
	event.shiftKey ||
	event.altKey;

function emit() {
	current = readLocation( current.key + 1 );
	listeners.forEach( ( listener ) => listener() );
}

function commit( url, replace ) {
	window.history[ replace ? 'replaceState' : 'pushState' ]( null, '', url.href );
	emit();
	window.scrollTo( 0, 0 );
}

// Runs `proceed` once unsaved edits have been dealt with.
function whenSafe( proceed ) {
	if ( blocker ) {
		blocker( proceed );
	} else if (
		! hasUnsavedChanges() ||
		// eslint-disable-next-line no-alert
		window.confirm( __( 'You have unsaved changes. Discard them?', 'open-bulk-buying' ) )
	) {
		proceed();
	}
}

/**
 * Goes to `href`: client-side for Co-op screens, a normal page load otherwise.
 * Asks first when there are unsaved edits.
 *
 * @param {string}  href
 * @param {Object}  options
 * @param {boolean} options.replace Replace the history entry instead of adding one.
 */
export function navigate( href, { replace = false } = {} ) {
	const url = resolve( href );
	if ( ! url ) {
		window.location.assign( href );
		return;
	}
	if ( url.href === window.location.href ) {
		return;
	}
	whenSafe( () => commit( url, replace ) );
}

function onPopState() {
	if ( ! hasUnsavedChanges() ) {
		emit();
		return;
	}
	// History can't be cancelled, so put the current URL back until the user decides.
	const target = new URL( window.location.href );
	window.history.pushState( null, '', current.href );
	whenSafe( () => commit( target, true ) );
}

function subscribe( listener ) {
	listeners.add( listener );
	return () => listeners.delete( listener );
}

const getLocation = () => current;

/**
 * The current location: { key, href, screen, search }. `key` changes on every
 * navigation, so it can be used to remount the screen.
 *
 * @return {Object} Location.
 */
export const useLocation = () => useSyncExternalStore( subscribe, getLocation );

export const useSearch = () => useLocation().search;

export const useNavigate = () => navigate;

/**
 * An anchor that navigates client-side when it can.
 *
 * @param {Object} props
 * @param {string} props.href
 */
export function Link( { href, onClick, target, children, ...props } ) {
	return (
		<a
			{ ...props }
			href={ href }
			target={ target }
			onClick={ ( event ) => {
				onClick?.( event );
				if ( target || isModifiedClick( event ) || ! resolve( href ) ) {
					return;
				}
				event.preventDefault();
				navigate( href );
			} }
		>
			{ children }
		</a>
	);
}

function syncAdminMenu( location ) {
	const menu = document.getElementById( ADMIN_MENU_ID );
	menu?.querySelectorAll( '.wp-submenu a' ).forEach( ( anchor ) => {
		const isCurrent =
			resolve( anchor.href )?.searchParams.get( 'page' ) ===
			location.search.page;
		anchor.classList.toggle( 'current', isCurrent );
		anchor.parentElement?.classList.toggle( 'current', isCurrent );
		if ( isCurrent ) {
			anchor.setAttribute( 'aria-current', 'page' );
		} else {
			anchor.removeAttribute( 'aria-current' );
		}
	} );

	const screen = config.screens.find(
		( item ) => item.name === location.screen
	);
	// WordPress titles admin pages "Title ‹ Site — WordPress".
	const separator = document.title.indexOf( ' \u2039 ' );
	if ( screen && separator > -1 ) {
		document.title = screen.title + document.title.slice( separator );
	}
}

function onDocumentClick( event ) {
	// Bubbling to the document runs after WordPress's own menu handlers, which
	// cancel the first tap on touch devices to open the submenu.
	const anchor = event.target.closest?.( `#${ ADMIN_MENU_ID } a[href]` );
	if (
		! anchor ||
		anchor.target ||
		isModifiedClick( event ) ||
		! resolve( anchor.href )
	) {
		return;
	}
	event.preventDefault();
	navigate( anchor.href );
}

/**
 * Wires the router to the browser and the WordPress admin menu, and shows the
 * discard prompt when leaving a screen with unsaved edits.
 *
 * @param {Object}  props
 * @param {Element} props.children
 */
export function RouterProvider( { children } ) {
	const [ guard, discardDialog ] = useDiscardGuard();
	const location = useLocation();
	const isFirstRender = useRef( true );

	useEffect( () => {
		blocker = guard;
		return () => {
			blocker = null;
		};
	}, [ guard ] );

	useEffect( () => {
		window.addEventListener( 'popstate', onPopState );
		document.addEventListener( 'click', onDocumentClick );
		return () => {
			window.removeEventListener( 'popstate', onPopState );
			document.removeEventListener( 'click', onDocumentClick );
		};
	}, [] );

	useEffect( () => {
		if ( isFirstRender.current ) {
			isFirstRender.current = false;
			return;
		}
		syncAdminMenu( location );
	}, [ location ] );

	return (
		<>
			{ children }
			{ discardDialog }
		</>
	);
}
