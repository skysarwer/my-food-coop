import { Modal } from '@wordpress/components';

import EditForm from './edit-form';

/**
 * An EditForm in a modal, for short task dialogs (e.g. recording stock).
 * Records themselves are edited in a SidePanel.
 *
 * @param {Object} props title, size and onClose, plus the EditForm props.
 */
export default function FormModal( { title, size = 'medium', onClose, ...props } ) {
	return (
		<Modal
			title={ title }
			onRequestClose={ onClose }
			size={ size }
			className="openbb-form-modal"
		>
			<EditForm onCancel={ onClose } { ...props } />
		</Modal>
	);
}
