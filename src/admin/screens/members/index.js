import {
	Button,
	ComboboxControl,
	SelectControl,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';
import { pencil, plus, seen } from '@wordpress/icons';
import { DataViews } from '@wordpress/dataviews/wp';

import { EditForm, ErrorNotice, Page, SidePanel } from '../../components';
import {
	discardUnsavedChanges,
	useDebouncedValue,
	useDiscardGuard,
	useList,
	useNotify,
	useRecord,
	useUnsavedChanges,
} from '../../hooks';
import { request } from '../../utils/api';
import { can, config, languageLabel, queryArg, toElements } from '../../utils/config';
import { formatMoney } from '../../utils/format';

const LIST_MAPPING = {
	orderby: { display_name: 'name', email: 'email', joined_at: 'registered' },
	filters: {
		coop_role: ( filter ) => ( { coop_role: filter.value } ),
	},
};

const DEFAULT_VIEW = {
	type: 'table',
	search: '',
	page: 1,
	perPage: 20,
	sort: { field: 'display_name', direction: 'asc' },
	titleField: 'display_name',
	descriptionField: 'email',
	fields: [ 'coop_role', 'phone', 'orders_count', 'last_order_at', 'rules_accepted' ],
	filters: [],
};

const formatDate = ( value ) =>
	value ? dateI18n( getSettings().formats.date, value ) : '—';

const languageElements = config.languages.map( ( lang ) => ( {
	value: lang,
	label: languageLabel( lang ),
} ) );

const memberFields = [
	{
		id: 'display_name',
		label: __( 'Name', 'open-bulk-buying' ),
		type: 'text',
		enableHiding: false,
		filterBy: false,
	},
	{
		id: 'email',
		label: __( 'Email', 'open-bulk-buying' ),
		type: 'email',
		filterBy: false,
	},
	{
		id: 'first_name',
		label: __( 'First name', 'open-bulk-buying' ),
		type: 'text',
		enableSorting: false,
		filterBy: false,
	},
	{
		id: 'last_name',
		label: __( 'Last name', 'open-bulk-buying' ),
		type: 'text',
		enableSorting: false,
		filterBy: false,
	},
	{
		id: 'phone',
		label: __( 'Phone', 'open-bulk-buying' ),
		type: 'telephone',
		enableSorting: false,
		filterBy: false,
	},
	{
		id: 'language',
		label: __( 'Language', 'open-bulk-buying' ),
		type: 'text',
		...( languageElements.length
			? {
					Edit: 'select',
					elements: [
						{ value: '', label: __( 'Site default', 'open-bulk-buying' ) },
						...languageElements,
					],
			  }
			: {} ),
		render: ( { item } ) => ( item.language ? languageLabel( item.language ) : '' ),
		enableSorting: false,
		filterBy: false,
	},
	{
		id: 'coop_role',
		label: __( 'Role', 'open-bulk-buying' ),
		type: 'text',
		Edit: 'select',
		elements: toElements( config.coopRoles ),
		render: ( { item } ) => (
			<>
				<span className={ `openbb-badge openbb-badge--role-${ item.coop_role }` }>
					{ item.coop_role_label }
				</span>
				{ item.grant_labels?.length > 0 && (
					<span className="openbb-muted"> + { item.grant_labels.join( ', ' ) }</span>
				) }
			</>
		),
		filterBy: { operators: [ 'isAny' ], isPrimary: true },
		enableSorting: false,
	},
	{
		id: 'orders_count',
		label: __( 'Orders', 'open-bulk-buying' ),
		type: 'integer',
		readOnly: true,
		enableSorting: false,
		filterBy: false,
	},
	{
		id: 'last_order_at',
		label: __( 'Last order', 'open-bulk-buying' ),
		type: 'datetime',
		readOnly: true,
		render: ( { item } ) => formatDate( item.last_order_at ),
		enableSorting: false,
		filterBy: false,
	},
	{
		id: 'joined_at',
		label: __( 'Joined', 'open-bulk-buying' ),
		type: 'datetime',
		readOnly: true,
		render: ( { item } ) => formatDate( item.joined_at ),
		filterBy: false,
	},
	{
		id: 'rules_accepted',
		label: __( 'Rules', 'open-bulk-buying' ),
		type: 'boolean',
		readOnly: true,
		render: ( { item } ) =>
			item.rules_accepted
				? __( 'Accepted', 'open-bulk-buying' )
				: __( 'Not accepted', 'open-bulk-buying' ),
		enableSorting: false,
		filterBy: false,
	},
];

const memberForm = {
	layout: { type: 'regular', labelPosition: 'top' },
	fields: [
		{
			id: 'names',
			label: __( 'Name', 'open-bulk-buying' ),
			layout: { type: 'row' },
			children: [ 'first_name', 'last_name' ],
		},
		'phone',
		'language',
		'coop_role',
	],
};

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
							<a href={ `${ config.urls.orders }&order=${ order.id }` }>
								#{ order.id }
							</a>
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

function MemberPanel( { memberId, onClose, onSaved } ) {
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

/**
 * Roles a new member may be given. Staff roles need openbb_manage_access; the
 * server checks again.
 */
const addableRoles = () =>
	toElements( config.coopRoles ).filter(
		( role ) =>
			can( 'openbb_manage_access' ) || ! config.staffRoles.includes( role.value )
	);

function AddMemberPanel( { onClose, onAdded } ) {
	const [ search, setSearch ] = useState( '' );
	const [ options, setOptions ] = useState( [] );
	const [ userId, setUserId ] = useState( null );
	const [ role, setRole ] = useState( 'member' );
	const [ isBusy, setIsBusy ] = useState( false );
	const [ error, setError ] = useState( null );
	const debounced = useDebouncedValue( search );
	useUnsavedChanges( !! userId );

	useEffect( () => {
		let current = true;
		request( '/members/candidates', { query: { search: debounced } } )
			.then( ( users ) => {
				if ( ! current ) {
					return;
				}
				setOptions( ( prev ) => {
					// Keep the chosen user listed so the combobox can show it.
					const selected = prev.find( ( option ) => option.value === userId );
					const next = users.map( ( user ) => ( {
						value: user.id,
						label: `${ user.name } (${ user.email })`,
					} ) );
					return selected && ! next.some( ( o ) => o.value === userId )
						? [ selected, ...next ]
						: next;
				} );
			} )
			.catch( ( err ) => current && setError( err ) );
		return () => {
			current = false;
		};
	}, [ debounced ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const submit = async ( event ) => {
		event.preventDefault();
		setIsBusy( true );
		setError( null );
		try {
			onAdded(
				await request( '/members', {
					method: 'POST',
					data: { user_id: userId, coop_role: role },
				} )
			);
		} catch ( err ) {
			setError( err );
			setIsBusy( false );
		}
	};

	return (
		<SidePanel title={ __( 'Add member', 'open-bulk-buying' ) } onClose={ onClose }>
			<form onSubmit={ submit } className="openbb-edit-form">
				<VStack spacing={ 4 }>
					<ErrorNotice error={ error } onDismiss={ () => setError( null ) } />
					<ComboboxControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'User', 'open-bulk-buying' ) }
						help={ __(
							'Search the site’s users who are not members yet, by name or email.',
							'open-bulk-buying'
						) }
						value={ userId }
						options={ options }
						onChange={ setUserId }
						onFilterValueChange={ setSearch }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Role', 'open-bulk-buying' ) }
						value={ role }
						options={ addableRoles() }
						onChange={ setRole }
					/>
					<HStack justify="flex-end" className="openbb-form-actions">
						<Button
							__next40pxDefaultSize
							variant="tertiary"
							onClick={ () => {
								discardUnsavedChanges();
								onClose();
							} }
							disabled={ isBusy }
						>
							{ __( 'Cancel', 'open-bulk-buying' ) }
						</Button>
						<Button
							__next40pxDefaultSize
							variant="primary"
							type="submit"
							isBusy={ isBusy }
							disabled={ isBusy || ! userId }
							accessibleWhenDisabled
						>
							{ __( 'Add member', 'open-bulk-buying' ) }
						</Button>
					</HStack>
				</VStack>
			</form>
		</SidePanel>
	);
}

export default function MembersScreen() {
	const [ view, setView ] = useState( DEFAULT_VIEW );
	// A member id, or 0 to add a member.
	const [ panel, setPanel ] = useState( () => {
		const id = Number( queryArg( 'member' ) );
		return id || null;
	} );
	const list = useList( '/members', view, LIST_MAPPING );
	const notify = useNotify();
	const canEdit = can( 'openbb_edit_members' );
	const [ guard, discardDialog ] = useDiscardGuard();

	const openPanel = useCallback(
		( id ) => {
			if ( id !== panel ) {
				guard( () => setPanel( id ) );
			}
		},
		[ panel, guard ]
	);
	const closePanel = () => guard( () => setPanel( null ) );

	const actions = useMemo(
		() => [
			{
				id: 'open',
				label: canEdit ? __( 'Edit', 'open-bulk-buying' ) : __( 'View', 'open-bulk-buying' ),
				icon: canEdit ? pencil : seen,
				isPrimary: true,
				callback: ( [ item ] ) => openPanel( item.id ),
			},
		],
		[ canEdit, openPanel ]
	);

	let sidebar = null;
	if ( panel === 0 ) {
		sidebar = (
			<AddMemberPanel
				onClose={ closePanel }
				onAdded={ ( item ) => {
					setPanel( item.id );
					list.reload();
					notify.success(
						/* translators: %s: member name. */
						sprintf( __( '%s is now a member.', 'open-bulk-buying' ), item.display_name )
					);
				} }
			/>
		);
	} else if ( panel ) {
		sidebar = (
			<MemberPanel
				key={ panel }
				memberId={ panel }
				onClose={ closePanel }
				onSaved={ ( item ) => {
					if ( item.removed ) {
						setPanel( null );
						list.reload();
					} else {
						list.replaceItem( item );
					}
					notify.success( __( 'Member saved.', 'open-bulk-buying' ) );
				} }
			/>
		);
	}

	return (
		<Page
			title={ __( 'Members', 'open-bulk-buying' ) }
			sidebar={ sidebar }
			actions={
				canEdit && (
					<Button variant="primary" icon={ plus } onClick={ () => openPanel( 0 ) }>
						{ __( 'Add member', 'open-bulk-buying' ) }
					</Button>
				)
			}
		>
			<ErrorNotice error={ list.error } />
			{ discardDialog }
			<DataViews
				data={ list.items }
				fields={ memberFields }
				view={ view }
				onChangeView={ setView }
				actions={ actions }
				paginationInfo={ list.paginationInfo }
				isLoading={ list.isLoading }
				defaultLayouts={ { table: {}, list: {} } }
				getItemId={ ( item ) => String( item.id ) }
				onClickItem={ ( item ) => openPanel( item.id ) }
				isItemClickable={ () => true }
				searchLabel={ __( 'Search members', 'open-bulk-buying' ) }
			/>
		</Page>
	);
}
