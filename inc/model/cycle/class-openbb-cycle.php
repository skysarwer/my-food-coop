<?php
/**
 * Cycle record.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Cycle extends OPENBB_Entity {

	const STATUS_SETUP        = 'setup';
	const STATUS_ORDERING     = 'ordering';
	const STATUS_PROCESSING   = 'processing';
	const STATUS_DISTRIBUTION = 'distribution';
	const STATUS_COMPLETE     = 'complete';

	/** Statuses whose submitted orders still reserve stock. Several cycles (e.g. per location) may be active at once. */
	const ACTIVE_STATUSES = array( self::STATUS_ORDERING, self::STATUS_PROCESSING, self::STATUS_DISTRIBUTION );

	public $id                  = 0;
	public $name                = '';
	public $status              = self::STATUS_SETUP;
	public $opens_at            = null;
	public $closes_at           = null;
	public $pickup_opens_at     = null;
	public $pickup_closes_at    = null;
	public $pickup_location     = '';
	public $pickup_instructions = array();
	public $created_at          = null;
	public $updated_at          = null;

	protected function cast(): void {
		$this->id                  = (int) $this->id;
		$this->pickup_location     = (string) $this->pickup_location;
		$this->pickup_instructions = openbb_normalize_localized( self::decode_json( $this->pickup_instructions ) );
	}

	public function save(): bool {
		global $wpdb;

		$now = current_time( 'mysql', true );
		if ( ! $this->created_at ) {
			$this->created_at = $now;
		}
		$this->updated_at = $now;

		$instructions = wp_json_encode( $this->pickup_instructions );
		if ( false === $instructions ) {
			return false;
		}

		return $this->save_row(
			$wpdb->openbb_cycles,
			array(
				'name'                => $this->name,
				'status'              => $this->status,
				'opens_at'            => $this->opens_at,
				'closes_at'           => $this->closes_at,
				'pickup_opens_at'     => $this->pickup_opens_at,
				'pickup_closes_at'    => $this->pickup_closes_at,
				'pickup_location'     => $this->pickup_location,
				'pickup_instructions' => $instructions,
				'created_at'          => $this->created_at,
				'updated_at'          => $this->updated_at,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Cycles with orders are retained as history and cannot be hard-deleted.
	 *
	 * @return true|WP_Error
	 */
	public function delete() {
		global $wpdb;

		if ( ! $this->id || ! OPENBB_Cycles::get( (int) $this->id ) ) {
			return new WP_Error( 'openbb_cycle_not_found', __( 'This cycle no longer exists.', 'open-bulk-buying' ) );
		}
		if ( (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->openbb_orders} WHERE cycle_id = %d", $this->id ) ) > 0 ) {
			return new WP_Error( 'openbb_cycle_has_orders', __( 'A cycle with orders cannot be deleted.', 'open-bulk-buying' ) );
		}
		if ( ! $this->delete_row( $wpdb->openbb_cycles ) ) {
			return new WP_Error( 'openbb_cycle_delete_failed', __( 'The cycle could not be deleted.', 'open-bulk-buying' ) );
		}

		return true;
	}

	public function is_active(): bool {
		return in_array( $this->status, self::ACTIVE_STATUSES, true );
	}

	/**
	 * Whether members can submit or edit orders at $timestamp (default: now).
	 */
	public function accepts_orders( ?int $timestamp = null ): bool {
		if ( self::STATUS_ORDERING !== $this->status || ! $this->opens_at || ! $this->closes_at ) {
			return false;
		}

		$timestamp = $timestamp ?? time();

		return $timestamp >= strtotime( $this->opens_at . ' UTC' )
			&& $timestamp < strtotime( $this->closes_at . ' UTC' );
	}

	public function get_pickup_instructions( ?string $language = null ): string {
		return openbb_localized( $this->pickup_instructions, $language );
	}
}
