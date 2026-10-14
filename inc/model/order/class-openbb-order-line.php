<?php
/**
 * Order line. Product details and prices are snapshots taken when the line was
 * written, so later catalogue edits don't change past orders. Amounts are integer
 * minor units; unit_price covers unit_amount of unit_type, and quantities count those
 * units as decimals so weighed items remain possible.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFCOOP_Order_Line extends MFCOOP_Entity {

	const SOURCE_MEMBER  = 'member';
	const SOURCE_WALK_UP = 'walk_up';

	public $id               = 0;
	public $order_id         = 0;
	public $product_id       = 0;
	public $source           = self::SOURCE_MEMBER;
	public $product_name     = '';
	public $unit_amount      = MFCOOP_UNIT_AMOUNT_DEFAULT;
	public $unit_type        = MFCOOP_UNIT_TYPE_DEFAULT;
	public $unit_price        = 0;
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
		$this->unit_amount      = (float) $this->unit_amount;
		$this->unit_type        = (string) $this->unit_type;
		$this->unit_price        = (int) $this->unit_price;
		$this->container_charge = (int) $this->container_charge;
		$this->own_container    = (bool) (int) $this->own_container;
		$this->qty_requested    = (float) $this->qty_requested;
		$this->qty_prepared     = self::nullable_float( $this->qty_prepared );
		$this->qty_final        = self::nullable_float( $this->qty_final );
		$this->line_total       = (int) $this->line_total;
	}

	public function get_unit_label(): string {
		return mfcoop_format_unit( $this->unit_amount, $this->unit_type );
	}
}
