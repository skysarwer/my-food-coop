import { createRoot, useEffect, useRef } from '@wordpress/element';

import './style.scss';
import AppNav from './components/app-nav';
import { RouterProvider, useLocation } from './router';
import { config } from './utils/config';
import OverviewScreen from './screens/overview';
import ProductsScreen from './screens/products';
import CyclesScreen from './screens/cycles';
import MembersScreen from './screens/members';
import OrdersScreen from './screens/orders';
import SettingsScreen from './screens/settings';

const screens = {
	overview: OverviewScreen,
	products: ProductsScreen,
	cycles: CyclesScreen,
	members: MembersScreen,
	orders: OrdersScreen,
	settings: SettingsScreen,
};

function App() {
	const location = useLocation();
	const heading = useRef();
	const Screen = screens[ location.screen ];
	const title = config.screens.find(
		( screen ) => screen.name === location.screen
	)?.title;

	// After client-side navigation, start screen readers at the new screen.
	useEffect( () => {
		if ( location.key > 0 ) {
			heading.current?.focus();
		}
	}, [ location.key ] );

	return (
		<RouterProvider>
			<h1 className="screen-reader-text" tabIndex={ -1 } ref={ heading }>
				{ title }
			</h1>
			<AppNav current={ location.screen } />
			{ Screen && <Screen key={ location.key } /> }
		</RouterProvider>
	);
}

const root = document.getElementById( 'openbb-admin-root' );

if ( root && screens[ root.dataset.screen ] ) {
	createRoot( root ).render( <App /> );
}
