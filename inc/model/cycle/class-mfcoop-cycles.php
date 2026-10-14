<?php
/**
 * Cycle queries. Datetimes are UTC.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFCOOP_Cycles {

	public static function schema(): array {
		global $wpdb;

		return array(
			$wpdb->mfcoop_cycles => "CREATE TABLE {$wpdb->mfcoop_cycles} (
				id bigint(20) unsigned NOT NULL auto_increment,
				name varchar(191) NOT NULL default '',
				status varchar(20) NOT NULL default 'setup',
				opens_at datetime NULL default NULL,
				closes_at datetime NULL default NULL,
				pickup_opens_at datetime NULL default NULL,
				pickup_closes_at datetime NULL default NULL,
				pickup_location text NULL,
				pickup_instructions longtext NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY status (status),
				KEY ordering_window (opens_at,closes_at)
			) {$wpdb->get_charset_collate()};",
		);
	}

	public static function get( int $id ): ?MFCOOP_Cycle {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->mfcoop_cycles} WHERE id = %d", $id ), ARRAY_A );

		return $row ? new MFCOOP_Cycle( $row ) : null;
	}

	/**
	 * Cycles whose submitted orders currently reserve stock.
	 *
	 * @return MFCOOP_Cycle[]
	 */
	public static function get_active(): array {
		global $wpdb;

		$statuses     = MFCOOP_Cycle::ACTIVE_STATUSES;
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->mfcoop_cycles} WHERE status IN ({$placeholders}) ORDER BY id", ...$statuses ), ARRAY_A );

		return array_map(
			static function ( array $row ): MFCOOP_Cycle {
				return new MFCOOP_Cycle( $row );
			},
			(array) $rows
		);
	}
}
