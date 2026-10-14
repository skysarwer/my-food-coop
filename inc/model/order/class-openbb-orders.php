<?php
/**
 * Order and order line queries. Datetimes are UTC.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Orders {

	public static function schema(): array {
		global $wpdb;

		return array(
			$wpdb->openbb_orders      => "CREATE TABLE {$wpdb->openbb_orders} (
				id bigint(20) unsigned NOT NULL auto_increment,
				cycle_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL default 'draft',
				language varchar(20) NOT NULL default '',
				currency char(3) NOT NULL default '',
				subtotal bigint(20) NOT NULL default 0,
				container_credit bigint(20) NOT NULL default 0,
				donation bigint(20) NOT NULL default 0,
				aid_status varchar(20) NOT NULL default 'none',
				aid_reason text NULL,
				aid_requested bigint(20) NOT NULL default 0,
				aid_approved bigint(20) NOT NULL default 0,
				aid_decided_by bigint(20) unsigned NULL default NULL,
				aid_decided_at datetime NULL default NULL,
				total bigint(20) NOT NULL default 0,
				notes text NULL,
				submitted_at datetime NULL default NULL,
				collected_at datetime NULL default NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY cycle_user (cycle_id,user_id),
				KEY user_id (user_id),
				KEY cycle_status (cycle_id,status),
				KEY aid_status (aid_status)
			) {$wpdb->get_charset_collate()};",

			$wpdb->openbb_order_lines => "CREATE TABLE {$wpdb->openbb_order_lines} (
				id bigint(20) unsigned NOT NULL auto_increment,
				order_id bigint(20) unsigned NOT NULL,
				product_id bigint(20) unsigned NOT NULL,
				source varchar(20) NOT NULL default 'member',
				product_name varchar(255) NOT NULL default '',
				brand varchar(255) NOT NULL default '',
				unit_amount decimal(12,3) NOT NULL default 1,
				unit_type varchar(20) NOT NULL default 'unit',
				base_per_unit decimal(12,3) NOT NULL default 1,
				unit_price bigint(20) NOT NULL default 0,
				container_charge bigint(20) NOT NULL default 0,
				own_container tinyint(1) NOT NULL default 0,
				qty_requested decimal(12,3) NOT NULL default 0,
				qty_prepared decimal(12,3) NULL default NULL,
				qty_final decimal(12,3) NULL default NULL,
				line_total bigint(20) NOT NULL default 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY order_id (order_id),
				KEY product_id (product_id)
			) {$wpdb->get_charset_collate()};",
		);
	}

	public static function get( int $id ): ?OPENBB_Order {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->openbb_orders} WHERE id = %d", $id ), ARRAY_A );

		return $row ? new OPENBB_Order( $row ) : null;
	}

	public static function get_for_member( int $cycle_id, int $user_id ): ?OPENBB_Order {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->openbb_orders} WHERE cycle_id = %d AND user_id = %d", $cycle_id, $user_id ), ARRAY_A );

		return $row ? new OPENBB_Order( $row ) : null;
	}

	/**
	 * @return OPENBB_Order_Line[]
	 */
	public static function get_lines( int $order_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->openbb_order_lines} WHERE order_id = %d ORDER BY id", $order_id ), ARRAY_A );

		return array_map(
			static function ( array $row ): OPENBB_Order_Line {
				return new OPENBB_Order_Line( $row );
			},
			(array) $rows
		);
	}
}
