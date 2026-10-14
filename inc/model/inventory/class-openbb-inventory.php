<?php
/**
 * Stock ledger queries. One shared inventory across cycles.
 *
 * On-hand stock is the sum of moves; reserved stock is derived from pending order lines.
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFCOOP_Inventory {

	public static function schema(): array {
		global $wpdb;

		return array(
			$wpdb->mfcoop_stock_moves => "CREATE TABLE {$wpdb->mfcoop_stock_moves} (
				id bigint(20) unsigned NOT NULL auto_increment,
				product_id bigint(20) unsigned NOT NULL,
				quantity decimal(12,3) NOT NULL,
				reason varchar(30) NOT NULL,
				cycle_id bigint(20) unsigned NULL default NULL,
				order_id bigint(20) unsigned NULL default NULL,
				user_id bigint(20) unsigned NOT NULL default 0,
				request_key varchar(64) NULL default NULL,
				note text NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY request_key (request_key),
				KEY product_id (product_id),
				KEY cycle_id (cycle_id),
				KEY order_id (order_id)
			) {$wpdb->get_charset_collate()};",
		);
	}

	public static function get_on_hand( int $product_id ): float {
		global $wpdb;

		return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(quantity), 0) FROM {$wpdb->mfcoop_stock_moves} WHERE product_id = %d", $product_id ) );
	}

	/**
	 * @return MFCOOP_Stock_Move[] Newest first.
	 */
	public static function get_moves( int $product_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->mfcoop_stock_moves} WHERE product_id = %d ORDER BY created_at DESC, id DESC", $product_id ), ARRAY_A );

		return array_map(
			static function ( array $row ): MFCOOP_Stock_Move {
				return new MFCOOP_Stock_Move( $row );
			},
			(array) $rows
		);
	}
}
