import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

export const NAMESPACE = '/openbb/v1';

/**
 * Normalizes a rejected apiFetch call (a parsed error body, or a raw Response
 * when parse is false) into { message, code, data }.
 *
 * @param {*} error
 * @return {Promise<{message: string, code: string, data: Object}>} Error.
 */
export async function toError( error ) {
	let body = error;
	if ( typeof Response !== 'undefined' && error instanceof Response ) {
		try {
			body = await error.json();
		} catch ( e ) {
			body = {};
		}
	}
	return {
		message:
			body?.message ||
			__( 'Something went wrong. Please try again.', 'open-bulk-buying' ),
		code: body?.code || 'unknown',
		data: body?.data || {},
	};
}

/**
 * Requests the API, throwing a normalized error on failure.
 *
 * @param {string} path    Path relative to the namespace, e.g. '/products/1'.
 * @param {Object} options apiFetch options (method, data) plus query args.
 * @return {Promise<*>} Parsed response.
 */
export async function request( path, { query, ...options } = {} ) {
	try {
		return await apiFetch( {
			path: addQueryArgs( NAMESPACE + path, query || {} ),
			...options,
		} );
	} catch ( error ) {
		throw await toError( error );
	}
}

/**
 * Fetches a paginated collection with its X-WP-Total headers.
 *
 * @param {string} path
 * @param {Object} query
 * @return {Promise<{items: Array, totalItems: number, totalPages: number}>} Page.
 */
export async function requestList( path, query ) {
	try {
		const response = await apiFetch( {
			path: addQueryArgs( NAMESPACE + path, query ),
			parse: false,
		} );
		const items = await response.json();
		return {
			items,
			totalItems: Number( response.headers.get( 'X-WP-Total' ) ) || 0,
			totalPages: Number( response.headers.get( 'X-WP-TotalPages' ) ) || 1,
		};
	} catch ( error ) {
		throw await toError( error );
	}
}

/**
 * Builds list query args from a DataViews view.
 *
 * @param {Object} view
 * @param {Object} options
 * @param {Object} options.orderby Map of field id => API orderby value.
 * @param {Object} options.filters Map of field id => function( filter ) returning query args.
 * @return {Object} Query args.
 */
export function viewToQuery( view, { orderby = {}, filters = {} } = {} ) {
	const query = {
		page: view.page || 1,
		per_page: view.perPage || 20,
	};
	if ( view.search ) {
		query.search = view.search;
	}
	if ( view.sort?.field && orderby[ view.sort.field ] ) {
		query.orderby = orderby[ view.sort.field ];
		query.order = view.sort.direction === 'asc' ? 'asc' : 'desc';
	}
	for ( const filter of view.filters || [] ) {
		const toArgs = filters[ filter.field ];
		const empty =
			filter.value === undefined ||
			filter.value === null ||
			filter.value === '' ||
			( Array.isArray( filter.value ) && ! filter.value.length );
		if ( toArgs && ! empty ) {
			Object.assign( query, toArgs( filter ) );
		}
	}
	return query;
}

/**
 * A key for idempotent writes, so a retried request is not applied twice.
 */
export const requestKey = () =>
	window.crypto?.randomUUID
		? window.crypto.randomUUID()
		: `${ Date.now() }-${ Math.random().toString( 36 ).slice( 2, 12 ) }`;
