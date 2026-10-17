/**
 * Settles a request per item and reports which succeeded and which failed.
 *
 * @param {Array}    items
 * @param {Function} run   Async function( item ).
 * @return {Promise<{done: Array, failed: Array}>} Results.
 */
export default async function runForEach( items, run ) {
	const results = await Promise.allSettled( items.map( run ) );
	const done = [];
	const failed = [];
	results.forEach( ( result, index ) =>
		( result.status === 'fulfilled' ? done : failed ).push( {
			item: items[ index ],
			value: result.value,
			error: result.reason,
		} )
	);
	return { done, failed };
}
