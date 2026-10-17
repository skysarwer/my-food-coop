import { useEffect } from '@wordpress/element';

// One token per mounted form that has unsaved edits.
const dirty = new Set();

const warnOnUnload = ( event ) => {
	event.preventDefault();
	// Older browsers need returnValue set to show their prompt.
	event.returnValue = '';
};

export const hasUnsavedChanges = () => dirty.size > 0;

/**
 * Forgets all unsaved edits, for when the user has chosen to throw them away
 * (e.g. pressed Cancel) and the form is about to close.
 */
export function discardUnsavedChanges() {
	dirty.clear();
	window.removeEventListener( 'beforeunload', warnOnUnload );
}

/**
 * Marks the calling form as having unsaved edits while `isDirty` is true, so
 * useDiscardGuard asks before replacing it and the browser warns before leaving
 * the page.
 *
 * @param {boolean} isDirty
 */
export default function useUnsavedChanges( isDirty ) {
	useEffect( () => {
		if ( ! isDirty ) {
			return;
		}
		const token = {};
		dirty.add( token );
		window.addEventListener( 'beforeunload', warnOnUnload );
		return () => {
			dirty.delete( token );
			if ( ! dirty.size ) {
				window.removeEventListener( 'beforeunload', warnOnUnload );
			}
		};
	}, [ isDirty ] );
}
