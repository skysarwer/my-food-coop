<?php
/**
 * Stock ledger queries. One shared inventory across cycles.
 *
 * Quantities are in the product's base unit (g, ml or units; see units.php). Each
 * move also records the product's selling unit at the time, for display only.
 * On-hand stock is the sum of moves; reserved stock is derived from pending order lines.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Inventory {

	public static function schema(): array {
		global $wpdb;

		return array(
			$wpdb->openbb_stock_moves => "CREATE TABLE {$wpdb->openbb_stock_moves} (
				id bigint(20) unsigned NOT NULL auto_increment,
				product_id bigint(20) unsigned NOT NULL,
				quantity decimal(12,3) NOT NULL,
				unit_amount decimal(12,3) NOT NULL default 1,
				unit_type varchar(20) NOT NULL default 'unit',
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

		return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(quantity), 0) FROM {$wpdb->openbb_stock_moves} WHERE product_id = %d", $product_id ) );
	}

	/**
	 * Base units held by submitted, uncollected orders in active cycles. Uses each
	 * line's latest quantity (final, else prepared, else requested).
	 */
	public static function get_reserved( int $product_id ): float {
		global $wpdb;

		$statuses     = OPENBB_Cycle::ACTIVE_STATUSES;
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		return (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(COALESCE(l.qty_final, l.qty_prepared, l.qty_requested) * l.base_per_unit), 0)
				FROM {$wpdb->openbb_order_lines} l
				INNER JOIN {$wpdb->openbb_orders} o ON o.id = l.order_id
				INNER JOIN {$wpdb->openbb_cycles} c ON c.id = o.cycle_id
				WHERE l.product_id = %d AND o.status = %s AND o.collected_at IS NULL AND c.status IN ({$placeholders})",
				$product_id,
				OPENBB_Order::STATUS_SUBMITTED,
				...$statuses
			)
		);
	}

	/**
	 * @return OPENBB_Stock_Move[] Newest first.
	 */
	public static function get_moves( int $product_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->openbb_stock_moves} WHERE product_id = %d ORDER BY created_at DESC, id DESC", $product_id ), ARRAY_A );

		return array_map(
			static function ( array $row ): OPENBB_Stock_Move {
				return new OPENBB_Stock_Move( $row );
			},
			(array) $rows
		);
	}
}
