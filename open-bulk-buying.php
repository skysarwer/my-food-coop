<?php 
/**
 * Plugin Name: Open Bulk Buying
 * Description: Inventory management, order cycles and order forms for Food Coops
 * Version: 1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Evan Buckiewicz
 * Author URI: https://evan.works
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: open-bulk-buying
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('OPENBB_VERSION', '1.0');
define('OPENBB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPENBB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPENBB_META_PREFIX', 'openbb_');

// Listing tables in $wpdb->tables makes switch_to_blog() re-prefix them on multisite.
global $wpdb;
foreach ( array( 'openbb_cycles', 'openbb_orders', 'openbb_order_lines', 'openbb_stock_moves', 'openbb_rules_versions' ) as $openbb_table ) {
	$wpdb->tables[]      = $openbb_table;
	$wpdb->$openbb_table = $wpdb->prefix . $openbb_table;
}

require_once OPENBB_PLUGIN_DIR . 'inc/i18n/localized-text.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/class-openbb-entity.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/member/user-meta.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/member/class-openbb-member.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/product/units.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/product/post-type.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/product/class-openbb-product.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/cycle/class-openbb-cycle.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/cycle/class-openbb-cycles.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/order/class-openbb-order.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/order/class-openbb-order-line.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/order/class-openbb-orders.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/inventory/class-openbb-stock-move.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/inventory/class-openbb-inventory.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/rules/class-openbb-rules-version.php';
require_once OPENBB_PLUGIN_DIR . 'inc/model/rules/class-openbb-rules.php';
require_once OPENBB_PLUGIN_DIR . 'inc/class-openbb-installer.php';

register_activation_hook( __FILE__, array( 'OPENBB_Installer', 'install' ) );
add_action( 'plugins_loaded', array( 'OPENBB_Installer', 'maybe_upgrade' ) );
add_action( 'init', 'openbb_register_product_post_type' );
add_action( 'init', 'openbb_register_member_meta' );
add_action( 'set_object_terms', 'openbb_enforce_single_term', 10, 4 );
add_filter( 'add_post_metadata', 'openbb_guard_product_unit_meta', 10, 4 );
add_filter( 'update_post_metadata', 'openbb_guard_product_unit_meta', 10, 4 );
add_filter( 'delete_post_metadata', 'openbb_guard_product_unit_meta', 10, 4 );

