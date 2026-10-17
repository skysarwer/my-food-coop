import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { EditForm, RecordLink, SidePanel } from '../../components';
import { useRecord } from '../../hooks';
import { request } from '../../utils/api';
import { can, config } from '../../utils/config';
import { formatMoney } from '../../utils/format';
import { formatDate, memberFields, memberForm } from './fields';

function MemberDetailsForm( { member, onSaved, onCancel } ) {
	// Only offer roles this user may assign to this member.
	const fields = useMemo(
		() =>
			memberFields.map( ( field ) =>
				field.id === 'coop_role'
					? {
							...field,
							elements: field.elements.filter( ( el ) =>
								member.assignable_roles.includes( el.value )
							),
					  }
					: field
			),
		[ member ]
	);

	return (
		<EditForm
			fields={ fields }
			form={ memberForm }
			initial={ member }
			requireChanges
			onCancel={ onCancel }
			onSubmit={ async ( data, edits ) => {
				const keys = [ 'first_name', 'last_name', 'phone', 'language', 'coop_role' ];
				const payload = Object.fromEntries(
					keys.filter( ( key ) => key in edits ).map( ( key ) => [ key, data[ key ] ?? '' ] )
				);
				onSaved(
					await request( `/members/${ member.id }`, { method: 'PUT', data: payload } )
				);
			} }
		>
			<MemberFacts member={ member } />
		</EditForm>
	);
}

function MemberFacts( { member } ) {
	return (
		<dl className="openbb-details">
			<dt>{ __( 'Email', 'open-bulk-buying' ) }</dt>
			<dd>
				<a href={ `mailto:${ member.email }` }>{ member.email }</a>
			</dd>
			<dt>{ __( 'Joined', 'open-bulk-buying' ) }</dt>
			<dd>{ formatDate( member.joined_at ) }</dd>
			<dt>{ __( 'Rules', 'open-bulk-buying' ) }</dt>
			<dd>
				{ member.rules_accepted
					? __( 'Accepted', 'open-bulk-buying' )
					: __( 'Not accepted', 'open-bulk-buying' ) }
			</dd>
			{ member.grant_labels?.length > 0 && (
				<>
					<dt>{ __( 'Grants', 'open-bulk-buying' ) }</dt>
					<dd>{ member.grant_labels.join( ', ' ) }</dd>
				</>
			) }
			{ member.profile_url && (
				<>
					<dt>{ __( 'Profile', 'open-bulk-buying' ) }</dt>
					<dd>
						<a href={ member.profile_url }>
							{ __( 'Edit WordPress profile', 'open-bulk-buying' ) }
						</a>
					</dd>
				</>
			) }
		</dl>
	);
}

function MemberReadOnly( { member } ) {
	return (
		<>
			<MemberFacts member={ member } />
			<dl className="openbb-details">
				<dt>{ __( 'Phone', 'open-bulk-buying' ) }</dt>
				<dd>{ member.phone || '—' }</dd>
				<dt>{ __( 'Role', 'open-bulk-buying' ) }</dt>
				<dd>{ member.coop_role_label }</dd>
			</dl>
		</>
	);
}

function MemberOrders( { orders } ) {
	if ( ! orders.length ) {
		return <p>{ __( 'No orders yet.', 'open-bulk-buying' ) }</p>;
	}
	return (
		<table className="widefat striped openbb-compact-table">
			<thead>
				<tr>
					<th>{ __( 'Order', 'open-bulk-buying' ) }</th>
					<th>{ __( 'Cycle', 'open-bulk-buying' ) }</th>
					<th>{ __( 'Status', 'open-bulk-buying' ) }</th>
					<th>{ __( 'Total', 'open-bulk-buying' ) }</th>
				</tr>
			</thead>
			<tbody>
				{ orders.map( ( order ) => (
					<tr key={ order.id }>
						<td>
							<RecordLink
								type="order"
								id={ order.id }
								href={ `${ config.urls.orders }&order=${ order.id }` }
							>
								#{ order.id }
							</RecordLink>
						</td>
						<td>{ order.cycle_name }</td>
						<td>{ order.status_label }</td>
						<td>{ formatMoney( order.total ) }</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}

/**
 * A member's details (editable when allowed) and order history.
 *
 * @param {Object} props
 */
export default function MemberPanel( { memberId, onClose, onSaved } ) {
	const { record: member, setRecord, error, isLoading } = useRecord(
		`/members/${ memberId }`
	);

	const tabs = [
		{ name: 'details', title: __( 'Details', 'open-bulk-buying' ) },
		...( can( 'openbb_view_orders' )
			? [ { name: 'orders', title: __( 'Orders', 'open-bulk-buying' ) } ]
			: [] ),
	];

	return (
		<SidePanel
			title={ member?.display_name || __( 'Member', 'open-bulk-buying' ) }
			onClose={ onClose }
			error={ error }
			isLoading={ isLoading }
			tabs={ tabs }
		>
			{ ( tab ) => {
				if ( tab.name === 'orders' ) {
					return <MemberOrders orders={ member.orders || [] } />;
				}
				if ( ! can( 'openbb_edit_members' ) || ! member.can_edit ) {
					return <MemberReadOnly member={ member } />;
				}
				return (
					<MemberDetailsForm
						member={ member }
						onCancel={ onClose }
						onSaved={ ( saved ) => {
							// The update response has no order history; keep the loaded one.
							setRecord( ( prev ) => ( { ...prev, ...saved } ) );
							onSaved( saved );
						} }
					/>
				);
			} }
		</SidePanel>
	);
}
