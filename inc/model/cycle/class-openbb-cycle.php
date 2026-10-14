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
