<?php
/**
 * Cycle queries. Datetimes are UTC.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Cycles {

	public static function schema(): array {
		global $wpdb;

		return array(
			$wpdb->openbb_cycles => "CREATE TABLE {$wpdb->openbb_cycles} (
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

	public static function get( int $id ): ?OPENBB_Cycle {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->openbb_cycles} WHERE id = %d", $id ), ARRAY_A );

		return $row ? new OPENBB_Cycle( $row ) : null;
	}

	/**
	 * Validates and saves a cycle, then announces a status change.
	 *
	 * Use this for staff edits; OPENBB_Cycle::save() is the unchecked primitive.
	 *
	 * @param string $old_status Status before the edit; '' for a new cycle.
	 * @return true|WP_Error
	 */
	public static function store( OPENBB_Cycle $cycle, string $old_status, int $actor_id ) {
		$valid = $cycle->validate();
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		if ( ! $cycle->save() ) {
			return new WP_Error( 'openbb_cycle_save_failed', __( 'The cycle could not be saved.', 'open-bulk-buying' ), array( 'status' => 500 ) );
		}

		if ( $old_status !== $cycle->status ) {
			/**
			 * Fires after a cycle is created or its status changes.
			 *
			 * @param OPENBB_Cycle $cycle      The saved cycle.
			 * @param string       $old_status Previous status; '' for a new cycle.
			 * @param int          $actor_id   User who made the change.
			 */
			do_action( 'openbb_cycle_status_changed', $cycle, $old_status, $actor_id );
		}

		return true;
	}

	/**
	 * Cycles whose submitted orders currently reserve stock.
	 *
	 * @return OPENBB_Cycle[]
	 */
	public static function get_active(): array {
		global $wpdb;

		$statuses     = OPENBB_Cycle::ACTIVE_STATUSES;
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->openbb_cycles} WHERE status IN ({$placeholders}) ORDER BY id", ...$statuses ), ARRAY_A );

		return array_map(
			static function ( array $row ): OPENBB_Cycle {
				return new OPENBB_Cycle( $row );
			},
			(array) $rows
		);
	}
}
