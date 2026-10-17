import { __experimentalHStack as HStack } from '@wordpress/components';

import Snackbars from './snackbars';

/**
 * Screen frame: a header with actions, the main content and an optional side panel.
 *
 * @param {Object}           props
 * @param {string}           props.title
 * @param {Element}          props.actions
 * @param {Element}          props.sidebar     Usually a SidePanel.
 * @param {string}           props.sidebarSize 'default' or 'wide'.
 * @param {string}           props.className
 * @param {Element|Element[]} props.children
 */
export default function Page( {
	title,
	actions,
	children,
	sidebar,
	sidebarSize = 'default',
	className = '',
} ) {
	const classes = [
		'openbb-page',
		sidebar && 'has-sidebar',
		sidebar && sidebarSize === 'wide' && 'has-wide-sidebar',
		className,
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<div className={ classes }>
			<div className="openbb-page__main">
				<HStack className="openbb-page__header" justify="space-between">
					<h2 className="openbb-page__title">{ title }</h2>
					{ actions && <HStack expanded={ false }>{ actions }</HStack> }
				</HStack>
				<div className="openbb-page__content">{ children }</div>
			</div>
			{ sidebar && <aside className="openbb-page__sidebar">{ sidebar }</aside> }
			<Snackbars />
		</div>
	);
}
