import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

export default function useNotify() {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	return {
		success: ( message ) =>
			createSuccessNotice( message, { type: 'snackbar' } ),
		error: ( message ) =>
			createErrorNotice( message, { type: 'snackbar', explicitDismiss: true } ),
	};
}
