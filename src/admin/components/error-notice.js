import { Notice } from '@wordpress/components';

export default function ErrorNotice( { error, onDismiss } ) {
	if ( ! error ) {
		return null;
	}
	return (
		<Notice
			status="error"
			isDismissible={ !! onDismiss }
			onRemove={ onDismiss }
			className="openbb-error"
		>
			{ typeof error === 'string' ? error : error.message }
		</Notice>
	);
}
