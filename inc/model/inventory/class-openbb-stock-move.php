<?php
/**
 * One signed entry in the stock ledger.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFCOOP_Stock_Move extends MFCOOP_Entity {

	const REASON_RECEIPT      = 'receipt';
	const REASON_ADJUSTMENT   = 'adjustment';
	const REASON_SPOILAGE     = 'spoilage';
	const REASON_DISTRIBUTION = 'distribution';

	public $id          = 0;
	public $product_id  = 0;
	public $quantity    = 0.0;
	public $reason      = '';
	public $cycle_id    = null;
	public $order_id    = null;
	public $user_id     = 0;
	public $request_key = null;
	public $note        = '';
	public $created_at  = null;

	protected function cast(): void {
		$this->id         = (int) $this->id;
		$this->product_id = (int) $this->product_id;
		$this->quantity   = (float) $this->quantity;
		$this->cycle_id   = self::nullable_int( $this->cycle_id );
		$this->order_id   = self::nullable_int( $this->order_id );
		$this->user_id    = (int) $this->user_id;
		$this->note       = (string) $this->note;
	}
}
