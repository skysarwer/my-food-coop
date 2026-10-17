import { useEffect, useState } from '@wordpress/element';

import { request } from '../utils/api';

/**
 * Loads one record for a side panel. With no path (a new record) it holds
 * `fallback` instead. Stale responses are ignored.
 *
 * @param {string|null} path     e.g. '/products/12'.
 * @param {Object|null} fallback Record to use when there is no path.
 * @return {Object} { record, setRecord, error, isLoading }
 */
export default function useRecord( path, fallback = null ) {
	const [ record, setRecord ] = useState( path ? null : fallback );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		setError( null );
		if ( ! path ) {
			setRecord( fallback );
			return;
		}
		let current = true;
		setRecord( null );
		request( path )
			.then( ( item ) => current && setRecord( item ) )
			.catch( ( err ) => current && setError( err ) );
		return () => {
			current = false;
		};
	}, [ path ] ); // eslint-disable-line react-hooks/exhaustive-deps

	return {
		record,
		setRecord,
		error,
		isLoading: !! path && ! record && ! error,
	};
}
