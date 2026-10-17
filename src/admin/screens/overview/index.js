import {
	Card,
	CardBody,
	__experimentalText as Text,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { Page } from '../../components';
import { Link } from '../../router';
import { config } from '../../utils/config';

const DESCRIPTIONS = {
	products: __( 'Catalogue, prices and stock levels.', 'open-bulk-buying' ),
	cycles: __( 'Ordering windows, dates and pickup details.', 'open-bulk-buying' ),
	orders: __( "Members' orders for each cycle.", 'open-bulk-buying' ),
	members: __( 'Co-op members, roles and order history.', 'open-bulk-buying' ),
	settings: __( 'Test mode, demo data and data maintenance.', 'open-bulk-buying' ),
};

export default function OverviewScreen() {
	const sections = config.screens.filter(
		( screen ) => screen.name !== 'overview'
	);

	return (
		<Page title={ __( 'Overview', 'open-bulk-buying' ) }>
			<div className="openbb-overview">
				{ sections.length ? (
					sections.map( ( screen ) => (
						<Card key={ screen.name } className="openbb-overview__card">
							<CardBody>
								<h3 className="openbb-overview__title">
									<Link href={ screen.url }>{ screen.title }</Link>
								</h3>
								<Text variant="muted">
									{ DESCRIPTIONS[ screen.name ] }
								</Text>
							</CardBody>
						</Card>
					) )
				) : (
					<p>
						{ __(
							'There is nothing here for you to manage yet.',
							'open-bulk-buying'
						) }
					</p>
				) }
			</div>
		</Page>
	);
}
