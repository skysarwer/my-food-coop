import { __ } from '@wordpress/i18n';

import { Link } from '../router';
import { config } from '../utils/config';

/**
 * Tabs for the Co-op screens, matching the Co-op admin menu.
 *
 * @param {Object} props
 * @param {string} props.current Current screen name.
 */
export default function AppNav( { current } ) {
	if ( config.screens.length < 2 ) {
		return null;
	}

	return (
		<nav
			className="openbb-app-nav"
			aria-label={ __( 'Co-op sections', 'open-bulk-buying' ) }
		>
			<ul className="openbb-app-nav__list">
				{ config.screens.map( ( screen ) => {
					const isCurrent = screen.name === current;
					return (
						<li key={ screen.name }>
							<Link
								href={ screen.url }
								className={
									'openbb-app-nav__item' +
									( isCurrent ? ' is-active' : '' )
								}
								aria-current={ isCurrent ? 'page' : undefined }
							>
								{ screen.title }
							</Link>
						</li>
					);
				} ) }
			</ul>
		</nav>
	);
}
