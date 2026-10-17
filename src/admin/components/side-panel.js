import {
	Button,
	TabPanel,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';

import ErrorNotice from './error-notice';
import Loading from './loading';

/**
 * The panel a screen shows beside its list (pass it to Page's `sidebar`) to
 * view, edit or create one record.
 *
 * With more than one tab, `children` is a function( tab ) rendering the
 * selected tab. Give the panel a `key` that changes with the record so
 * its tabs and forms reset.
 *
 * @param {Object}            props
 * @param {string}            props.title
 * @param {Function}          props.onClose
 * @param {Error|string}      props.error          Shown instead of the content.
 * @param {boolean}           props.isLoading
 * @param {Array}             props.tabs           TabPanel tabs: { name, title }.
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
	let content = null;
	if ( isLoading ) {
		content = <Loading />;
	} else if ( error ) {
		content = null;
	} else if ( tabs.length > 1 ) {
		content = (
			<TabPanel
				className="openbb-panel__tabs"
				tabs={ tabs }
				initialTabName={
					tabs.some( ( tab ) => tab.name === initialTabName )
						? initialTabName
						: tabs[ 0 ].name
				}
			>
				{ ( tab ) => <div className="openbb-panel__body">{ children( tab ) }</div> }
			</TabPanel>
		);
	} else {
		content = (
			<div className="openbb-panel__body">
				{ typeof children === 'function' ? children( tabs[ 0 ] || {} ) : children }
			</div>
		);
	}

	return (
		<div className="openbb-panel">
			<HStack className="openbb-panel__header">
				<h2 className="openbb-panel__title">{ title }</h2>
				<Button
					icon={ closeSmall }
					label={ __( 'Close', 'open-bulk-buying' ) }
					onClick={ onClose }
					size="compact"
				/>
			</HStack>
			<ErrorNotice error={ error } />
			{ content }
		</div>
	);
}
