<?php 
/**
 * Plugin Name: My Food Coop
 * Description: Inventory management, order cycles and order forms for Food Coops
 * Version: 1.0
 * Requires PHP: 7.4
 * Author: Evan Buckiewicz
 * Author URI: https://evan.works
 * License: GPL2
 * Text Domain: my-food-coop
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('MFCOOP_VERSION', '1.0');
define('MFCOOP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MFCOOP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MFCOOP_META_PREFIX', 'mfcoop_');

// Listing tables in $wpdb->tables makes switch_to_blog() re-prefix them on multisite.
global $wpdb;
foreach ( array( 'mfcoop_cycles', 'mfcoop_orders', 'mfcoop_order_lines', 'mfcoop_stock_moves', 'mfcoop_rules_versions' ) as $mfcoop_table ) {
	$wpdb->tables[]      = $mfcoop_table;
	$wpdb->$mfcoop_table = $wpdb->prefix . $mfcoop_table;
}

require_once MFCOOP_PLUGIN_DIR . 'inc/i18n/localized-text.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/class-mfcoop-entity.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/member/user-meta.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/member/class-mfcoop-member.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/product/units.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/product/post-type.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/product/class-mfcoop-product.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/cycle/class-mfcoop-cycle.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/cycle/class-mfcoop-cycles.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/order/class-mfcoop-order.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/order/class-mfcoop-order-line.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/order/class-mfcoop-orders.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/inventory/class-mfcoop-stock-move.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/inventory/class-mfcoop-inventory.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/rules/class-mfcoop-rules-version.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/model/rules/class-mfcoop-rules.php';
require_once MFCOOP_PLUGIN_DIR . 'inc/class-mfcoop-installer.php';

register_activation_hook( __FILE__, array( 'MFCOOP_Installer', 'install' ) );
add_action( 'plugins_loaded', array( 'MFCOOP_Installer', 'maybe_upgrade' ) );
add_action( 'init', 'mfcoop_register_product_post_type' );
add_action( 'init', 'mfcoop_register_member_meta' );
add_action( 'set_object_terms', 'mfcoop_enforce_single_term', 10, 4 );

