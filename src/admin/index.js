import { createRoot } from '@wordpress/element';

import './style.scss';
import ProductsScreen from './screens/products';
import CyclesScreen from './screens/cycles';
import MembersScreen from './screens/members';
import OrdersScreen from './screens/orders';

const screens = {
	products: ProductsScreen,
	cycles: CyclesScreen,
	members: MembersScreen,
	orders: OrdersScreen,
};

const root = document.getElementById( 'openbb-admin-root' );
const Screen = root && screens[ root.dataset.screen ];

if ( Screen ) {
	createRoot( root ).render( <Screen /> );
}
