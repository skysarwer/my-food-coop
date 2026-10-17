import { createContext, useCallback, useContext, useMemo, useState } from '@wordpress/element';

import { useNotify } from '../hooks';
import { isModifiedClick, Link } from '../router';

/**
 * Lets a side panel open a related record (e.g. an order from a member's order
 * history) in its place, with a back button to return.
 *
 * A screen's own panel is the bottom of the stack; related records are pushed on
 * top. Only the top panel is mounted, so going back re-fetches the record below
 * and reopens the tab it was left on.
 */
const PanelStackContext = createContext( null );

/** The enclosing SidePanel's title and selected tab, recorded when leaving it. */
export const PanelContext = createContext( {} );

export const usePanelStackContext = () => useContext( PanelStackContext );

const isSame = ( a, b ) => !! a && !! b && a.type === b.type && a.id === b.id;

/**
 * @param {Object}   options
 * @param {Object}   options.panels   Registry: { [type]: { Panel, size?, savedMessage? } }.
 *                                    Panel receives { id, onClose, onSaved }.
 * @param {Function} options.guard    From useDiscardGuard.
 * @param {Object}   options.base     { type, id } of the screen's own open record, if any.
 * @param {Function} options.onClose  Closes the whole side panel (should reset()).
 * @param {Function} options.onSaved  Called with ( entry, item ) after a related panel saves.
 * @return {Object} { top, size, reset, render( baseSidebar ) }
 */
export function usePanelStack( { panels, guard, base = null, onClose, onSaved } ) {
	const [ stack, setStack ] = useState( [] );
	// Tab to reopen on the panel revealed by going back.
	const [ restoreTab, setRestoreTab ] = useState();
	const notify = useNotify();
	const top = stack[ stack.length - 1 ];

	const push = useCallback(
		( entry ) => {
			const levels = [ base, ...stack ];
			const index = levels.findIndex( ( level ) => isSame( level, entry ) );
			if ( index === levels.length - 1 ) {
				return;
			}
			guard( () => {
				if ( index >= 0 ) {
					// Already open further down: return to it rather than looping.
					setRestoreTab( stack[ index ]?.from?.tab );
					setStack( stack.slice( 0, index ) );
				} else {
					setRestoreTab( undefined );
					setStack( [ ...stack, entry ] );
				}
			} );
		},
		[ base, stack, guard ]
	);

	const pop = useCallback( () => {
		setRestoreTab( top?.from?.tab );
		setStack( stack.slice( 0, -1 ) );
	}, [ stack, top ] );

	const back = useCallback( () => guard( pop ), [ guard, pop ] );

	const reset = useCallback( () => {
		setStack( [] );
		setRestoreTab( undefined );
	}, [] );

	const value = useMemo(
		() => ( {
			push,
			back,
			canGoBack: stack.length > 0,
			backTitle: top?.from?.title || '',
			restoreTab,
			supports: ( type ) => !! panels[ type ],
		} ),
		[ push, back, stack.length, top, restoreTab, panels ]
	);

	const render = ( baseSidebar ) => {
		let content = baseSidebar;
		if ( top ) {
			const { Panel, savedMessage } = panels[ top.type ];
			content = (
				<Panel
					key={ `${ stack.length }:${ top.type }:${ top.id }` }
					id={ top.id }
					onClose={ onClose }
					onSaved={ ( item ) => {
						if ( item?.removed ) {
							pop();
						}
						if ( savedMessage ) {
							notify.success( savedMessage );
						}
						onSaved?.( top, item );
					} }
				/>
			);
		}

		return content ? (
			<PanelStackContext.Provider value={ value }>{ content }</PanelStackContext.Provider>
		) : null;
	};

	return {
		top,
		size: top ? panels[ top.type ].size || 'default' : null,
		reset,
		render,
	};
}

/**
 * A link to a related record. Inside a panel stack that can show `type`, a plain
 * click opens the record in the side panel; modified clicks (new tab) and links
 * outside a stack follow `href`.
 *
 * @param {Object}  props
 * @param {string}  props.type
 * @param {number}  props.id
 * @param {string}  props.href     Fallback URL.
 * @param {Element} props.children
 */
export function RecordLink( { type, id, href, children } ) {
	const stack = useContext( PanelStackContext );
	const panel = useContext( PanelContext );

	if ( ! stack?.supports( type ) ) {
		return href ? <Link href={ href }>{ children }</Link> : children;
	}

	return (
		<a
			href={ href || '#' }
			onClick={ ( event ) => {
				if ( isModifiedClick( event ) ) {
					return;
				}
				event.preventDefault();
				stack.push( { type, id, from: panel } );
			} }
		>
			{ children }
		</a>
	);
}
