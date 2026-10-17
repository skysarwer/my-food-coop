import { __ } from '@wordpress/i18n';

import MemberPanel from './members/panel';
import OrderEditor from './orders/editor';

/**
 * Records a side panel can open in place of the current one (see RecordLink and
 * usePanelStack). Each Panel receives { id, onClose, onSaved }.
 */
const relatedPanels = {
	member: {
		Panel: ( { id, ...props } ) => <MemberPanel memberId={ id } { ...props } />,
		savedMessage: __( 'Member saved.', 'open-bulk-buying' ),
	},
	order: {
		Panel: ( { id, ...props } ) => <OrderEditor orderId={ id } { ...props } />,
		size: 'wide',
		savedMessage: __( 'Order saved.', 'open-bulk-buying' ),
	},
};

export default relatedPanels;
