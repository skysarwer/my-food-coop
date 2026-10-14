<?php
/**
 * Order line. Product details and prices are snapshots taken when the line was
 * written, so later catalogue edits don't change past orders. product_name is in
 * the order's language. Amounts are integer minor units; unit_price covers
 * unit_amount of unit_type, and quantities count those units as decimals so weighed
 * items remain possible. base_per_unit snapshots how many inventory base units one
 * of those units holds (see units.php).
 *
 * The container charge is added to the line total without being shown separately;
 * members who bring their own container get it back as a visible per-unit credit.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Order_Line extends OPENBB_Entity {

	const SOURCE_MEMBER  = 'member';
	const SOURCE_WALK_UP = 'walk_up';

	public $id               = 0;
	public $order_id         = 0;
	public $product_id       = 0;
	public $source           = self::SOURCE_MEMBER;
	public $product_name     = '';
	public $brand            = '';
	public $unit_amount      = OPENBB_UNIT_AMOUNT_DEFAULT;
	public $unit_type        = OPENBB_UNIT_TYPE_DEFAULT;
	public $base_per_unit    = OPENBB_UNIT_AMOUNT_DEFAULT;
	public $unit_price       = 0;
	public $container_charge = 0;
	public $own_container    = false;
	public $qty_requested    = 0.0;
	public $qty_prepared     = null;
	public $qty_final        = null;
	public $line_total       = 0;
	public $created_at       = null;
	public $updated_at       = null;

	protected function cast(): void {
		$this->id               = (int) $this->id;
		$this->order_id         = (int) $this->order_id;
		$this->product_id       = (int) $this->product_id;
		$this->product_name     = (string) $this->product_name;
		$this->brand            = (string) $this->brand;
		$this->unit_amount      = (float) $this->unit_amount;
		$this->unit_type        = (string) $this->unit_type;
		$this->base_per_unit    = (float) $this->base_per_unit;
		$this->unit_price       = (int) $this->unit_price;
		$this->container_charge = (int) $this->container_charge;
		$this->own_container    = (bool) (int) $this->own_container;
		$this->qty_requested    = (float) $this->qty_requested;
		$this->qty_prepared     = self::nullable_float( $this->qty_prepared );
		$this->qty_final        = self::nullable_float( $this->qty_final );
		$this->line_total       = (int) $this->line_total;
	}

	public function get_unit_label(): string {
		return openbb_format_unit( $this->unit_amount, $this->unit_type );
	}

	/**
	 * The latest known quantity: final, else prepared, else requested.
	 */
	public function get_quantity(): float {
		return $this->qty_final ?? $this->qty_prepared ?? $this->qty_requested;
	}

	public function get_base_quantity(): float {
		return round( $this->get_quantity() * $this->base_per_unit, 3 );
	}

	/**
	 * Price plus container charge, per unit, times the latest quantity.
	 */
	public function calculate_total(): int {
		return (int) round( $this->get_quantity() * ( $this->unit_price + $this->container_charge ) );
	}

	public function calculate_container_credit(): int {
		return $this->own_container ? (int) round( $this->get_quantity() * $this->container_charge ) : 0;
	}
}
