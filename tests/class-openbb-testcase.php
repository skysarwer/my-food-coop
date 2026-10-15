<?php
/**
 * Shared fixtures for co-op users, cycles and orders.
 *
 * @package Open_Bulk_Buying
 */

abstract class OPENBB_TestCase extends WP_UnitTestCase {

	/**
	 * Creates a user with a WordPress role and, written directly as the signup flow
	 * would, a co-op role and grants.
	 */
	protected function make_user( string $coop_role = '', string $wp_role = 'subscriber', array $grants = array() ): int {
		$user_id = self::factory()->user->create( array( 'role' => $wp_role ) );
		if ( '' !== $coop_role ) {
			openbb_update_member_field( $user_id, 'coop_role', $coop_role );
		}
		if ( $grants ) {
			openbb_update_member_field( $user_id, 'grants', $grants );
		}

		return $user_id;
	}

	protected function make_cycle( string $status ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$wpdb->openbb_cycles,
			array(
				'name'       => 'Test cycle',
				'status'     => $status,
				'created_at' => $now,
				'updated_at' => $now,
			)
		);

		return (int) $wpdb->insert_id;
	}

	protected function make_order( int $cycle_id, int $user_id ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$wpdb->openbb_orders,
			array(
				'cycle_id'   => $cycle_id,
				'user_id'    => $user_id,
				'status'     => OPENBB_Order::STATUS_SUBMITTED,
				'created_at' => $now,
				'updated_at' => $now,
			)
		);

		return (int) $wpdb->insert_id;
	}
}
