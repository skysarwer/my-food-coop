import {
	Button,
	NavigableMenu,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import { __, isRTL, sprintf } from '@wordpress/i18n';
import { chevronLeft, chevronRight, closeSmall } from '@wordpress/icons';

import ErrorNotice from './error-notice';
import Loading from './loading';
import { PanelContext, usePanelStackContext } from './panel-stack';
import { useDiscardGuard } from '../hooks';

/**
 * The panel a screen shows beside its list (pass it to Page's `sidebar`) to
 * view, edit or create one record.
 *
 * With more than one tab, `children` is a function( tab ) rendering the
 * selected tab. Switching tabs asks before discarding unsaved edits. Give the
 * panel a `key` that changes with the record so its tabs and forms reset.
 *
 * Inside a panel stack (usePanelStack) that has opened this panel from another
 * record, the header shows a back button.
 *
 * @param {Object}            props
 * @param {string}            props.title
 * @param {Function}          props.onClose
 * @param {Error|string}      props.error          Shown instead of the content.
 * @param {boolean}           props.isLoading
 * @param {Array}             props.tabs           { name, title }.
 * @param {string}            props.initialTabName
 * @param {Function|Element}  props.children
 */
export default function SidePanel( {
	title,
	onClose,
	error,
	isLoading = false,
	tabs = [],
	initialTabName,
	children,
} ) {
	const instanceId = useInstanceId( SidePanel, 'openbb-panel' );
	const stack = usePanelStackContext();
	const [ guard, discardDialog ] = useDiscardGuard();
	const [ selected, setSelected ] = useState( () => {
		const wanted = stack?.restoreTab || initialTabName;
		return tabs.some( ( tab ) => tab.name === wanted ) ? wanted : tabs[ 0 ]?.name;
	} );
	const current = tabs.find( ( tab ) => tab.name === selected ) || tabs[ 0 ] || {};

	const render = () =>
		typeof children === 'function' ? children( current ) : children;

	let content = null;
	if ( isLoading ) {
		content = <Loading />;
	} else if ( error ) {
		content = null;
	} else if ( tabs.length > 1 ) {
		// TabPanel can't veto a switch, so this is a minimal tab list using its styles.
		content = (
			<>
				<NavigableMenu
					role="tablist"
					orientation="horizontal"
					className="components-tab-panel__tabs openbb-panel__tabs"
				>
					{ tabs.map( ( tab ) => {
						const isActive = tab.name === current.name;
						return (
							<Button
								key={ tab.name }
								role="tab"
								id={ `${ instanceId }-tab-${ tab.name }` }
								aria-controls={ `${ instanceId }-body` }
								aria-selected={ isActive }
								tabIndex={ isActive ? undefined : -1 }
								className={ `components-tab-panel__tabs-item${ isActive ? ' is-active' : '' }` }
								onClick={ () => {
									if ( ! isActive ) {
										guard( () => setSelected( tab.name ) );
									}
								} }
							>
								{ tab.title }
							</Button>
						);
					} ) }
				</NavigableMenu>
				<div
					role="tabpanel"
					id={ `${ instanceId }-body` }
					aria-labelledby={ `${ instanceId }-tab-${ current.name }` }
					className="openbb-panel__body"
				>
					{ render() }
				</div>
			</>
		);
	} else {
		content = <div className="openbb-panel__body">{ render() }</div>;
	}

	return (
		<PanelContext.Provider value={ { title, tab: current.name } }>
			<div className="openbb-panel">
				<HStack className="openbb-panel__header">
					<HStack justify="flex-start" spacing={ 1 } className="openbb-panel__heading">
						{ stack?.canGoBack && (
							<Button
								icon={ isRTL() ? chevronRight : chevronLeft }
								label={
									stack.backTitle
										? /* translators: %s: title of the previous panel. */
										  sprintf( __( 'Back to %s', 'open-bulk-buying' ), stack.backTitle )
										: __( 'Back', 'open-bulk-buying' )
								}
								onClick={ stack.back }
								size="compact"
							/>
						) }
						<h2 className="openbb-panel__title">{ title }</h2>
					</HStack>
					<Button
						icon={ closeSmall }
						label={ __( 'Close', 'open-bulk-buying' ) }
						onClick={ onClose }
						size="compact"
					/>
				</HStack>
				<ErrorNotice error={ error } />
				{ content }
				{ discardDialog }
			</div>
		</PanelContext.Provider>
	);
}
