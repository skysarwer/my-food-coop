import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { discardUnsavedChanges, hasUnsavedChanges } from './use-unsaved-changes';

/**
 * Asks before an action that would throw away unsaved edits (opening another
 * record, closing the panel). Render `dialog` somewhere in the screen.
 *
 * @return {Array} [ guard( fn ), dialog ]
 */
export default function useDiscardGuard() {
	const [ pending, setPending ] = useState( null );

	const guard = useCallback( ( fn ) => {
		if ( hasUnsavedChanges() ) {
			setPending( () => fn );
		} else {
			fn();
		}
	}, [] );

	const dialog = pending && (
		<ConfirmDialog
			isOpen
			confirmButtonText={ __( 'Discard changes', 'open-bulk-buying' ) }
			cancelButtonText={ __( 'Keep editing', 'open-bulk-buying' ) }
			onConfirm={ () => {
				setPending( null );
				discardUnsavedChanges();
				pending();
			} }
			onCancel={ () => setPending( null ) }
		>
			{ __( 'You have unsaved changes. Discard them?', 'open-bulk-buying' ) }
		</ConfirmDialog>
	);

	return [ guard, dialog ];
}
