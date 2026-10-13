<?php 
/**
 * Plugin Name: My Food Coop
 * Description: Inventory management, order cycles and order forms for Food Coops
 * Version: 1.0
 * Author: Evan Buckiewicz
 * Author URI: https://evan.works
 * License: GPL2
 * Text Domain: mfc 
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('MFC_VERSION', '1.0');
define('MFC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MFC_PLUGIN_URL', plugin_dir_url(__FILE__));

