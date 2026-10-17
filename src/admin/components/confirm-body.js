import {
	Button,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import ErrorNotice from './error-notice';

/**
 * Confirmation body for a DataViews action modal (RenderModal).
 *
 * @param {Object} props
 */
export default function ConfirmBody( {
	message,
	confirmLabel,
	isDestructive = false,
	onConfirm,
	closeModal,
} ) {
	const [ isBusy, setIsBusy ] = useState( false );
	const [ error, setError ] = useState( null );

	const confirm = async () => {
		setIsBusy( true );
		setError( null );
		try {
			await onConfirm();
			closeModal();
		} catch ( err ) {
			setError( err );
			setIsBusy( false );
		}
	};

	return (
		<VStack spacing={ 4 }>
			<ErrorNotice error={ error } />
			<p>{ message }</p>
			<HStack justify="flex-end">
				<Button
					__next40pxDefaultSize
					variant="tertiary"
					onClick={ closeModal }
					disabled={ isBusy }
				>
					{ __( 'Cancel', 'open-bulk-buying' ) }
				</Button>
				<Button
					__next40pxDefaultSize
					variant="primary"
					isDestructive={ isDestructive }
					isBusy={ isBusy }
					disabled={ isBusy }
					onClick={ confirm }
				>
					{ confirmLabel }
				</Button>
			</HStack>
		</VStack>
	);
}
