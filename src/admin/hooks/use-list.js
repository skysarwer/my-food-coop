import { useCallback, useEffect, useState } from '@wordpress/element';

import { requestList, viewToQuery } from '../utils/api';

/**
 * Loads one page of a collection for a DataViews view; reloads when the view
 * (or the reload token) changes. Stale responses are ignored.
 *
 * @param {string} path    Collection path, e.g. '/products'.
 * @param {Object} view    DataViews view.
 * @param {Object} mapping Passed to viewToQuery (orderby/filters maps).
 * @param {Object} extra   Extra query args.
 * @return {Object} { items, paginationInfo, isLoading, error, reload, replaceItem }
 */
export default function useList( path, view, mapping, extra = {} ) {
	const [ state, setState ] = useState( {
		items: [],
		totalItems: 0,
		totalPages: 0,
	} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ token, setToken ] = useState( 0 );

	const query = JSON.stringify( { ...viewToQuery( view, mapping ), ...extra } );

	useEffect( () => {
		let current = true;
		setIsLoading( true );
		requestList( path, JSON.parse( query ) )
			.then( ( result ) => {
				if ( current ) {
					setState( result );
					setError( null );
				}
			} )
			.catch( ( err ) => {
				if ( current ) {
					setError( err );
					setState( { items: [], totalItems: 0, totalPages: 0 } );
				}
			} )
			.finally( () => current && setIsLoading( false ) );
		return () => {
			current = false;
		};
	}, [ path, query, token ] );

	const reload = useCallback( () => setToken( ( value ) => value + 1 ), [] );

	// Swaps in a saved item without refetching.
	const replaceItem = useCallback(
		( item ) =>
			setState( ( prev ) => ( {
				...prev,
				items: prev.items.map( ( existing ) =>
					existing.id === item.id ? { ...existing, ...item } : existing
				),
			} ) ),
		[]
	);

	return {
		items: state.items,
		paginationInfo: {
			totalItems: state.totalItems,
			totalPages: state.totalPages,
		},
		isLoading,
		error,
		reload,
		replaceItem,
	};
}
