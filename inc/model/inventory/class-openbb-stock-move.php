<?php
/**
 * One signed entry in the stock ledger. quantity is in base units; unit_amount and
 * unit_type snapshot the product's selling unit for display, e.g. 1000 g as "20 × 50 g".
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Stock_Move extends OPENBB_Entity {

	const REASON_RECEIPT      = 'receipt';
	const REASON_ADJUSTMENT   = 'adjustment';
	const REASON_SPOILAGE     = 'spoilage';
	const REASON_DISTRIBUTION = 'distribution';

	/**
	 * Reasons staff may record by hand. Distribution can also be recorded by hand
	 * (e.g. stock handed out outside an order).
	 */
	const MANUAL_REASONS = array( self::REASON_RECEIPT, self::REASON_ADJUSTMENT, self::REASON_SPOILAGE, self::REASON_DISTRIBUTION );

	/**
	 * Translated reason labels. Only call from UI code.
	 */
	public static function reason_labels(): array {
		return array(
			self::REASON_RECEIPT      => __( 'Receipt', 'open-bulk-buying' ),
			self::REASON_ADJUSTMENT   => __( 'Adjustment', 'open-bulk-buying' ),
			self::REASON_SPOILAGE     => __( 'Spoilage', 'open-bulk-buying' ),
			self::REASON_DISTRIBUTION => __( 'Distribution', 'open-bulk-buying' ),
		);
	}

	public $id          = 0;
	public $product_id  = 0;
	public $quantity    = 0.0;
	public $unit_amount = OPENBB_UNIT_AMOUNT_DEFAULT;
	public $unit_type   = OPENBB_UNIT_TYPE_DEFAULT;
	public $reason      = '';
	public $cycle_id    = null;
	public $order_id    = null;
	public $user_id     = 0;
	public $request_key = null;
	public $note        = '';
	public $created_at  = null;

	protected function cast(): void {
		$this->id          = (int) $this->id;
		$this->product_id  = (int) $this->product_id;
		$this->quantity    = (float) $this->quantity;
		$this->unit_amount = openbb_sanitize_unit_amount( $this->unit_amount );
		$this->unit_type   = openbb_sanitize_unit_type( $this->unit_type );
		$this->cycle_id    = self::nullable_int( $this->cycle_id );
		$this->order_id    = self::nullable_int( $this->order_id );
		$this->user_id     = (int) $this->user_id;
		$this->note        = (string) $this->note;
	}

	/**
	 * Appends this move to the stock ledger. Existing moves are immutable.
	 */
	public function save(): bool {
		global $wpdb;

		if ( $this->id || ! $this->product_id || OPENBB_PRODUCT_POST_TYPE !== get_post_type( $this->product_id ) ) {
			return false;
		}

		$this->created_at = $this->created_at ?: current_time( 'mysql', true );

		return $this->save_row(
			$wpdb->openbb_stock_moves,
			array(
				'product_id'  => $this->product_id,
				'quantity'    => $this->quantity,
				'unit_amount' => $this->unit_amount,
				'unit_type'   => $this->unit_type,
				'reason'      => $this->reason,
				'cycle_id'    => $this->cycle_id,
				'order_id'    => $this->order_id,
				'user_id'     => $this->user_id,
				'request_key' => $this->request_key,
				'note'        => $this->note,
				'created_at'  => $this->created_at,
			),
			array( '%d', '%f', '%f', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * The quantity in selling units, e.g. 20 for 1000 g sold per 50 g.
	 */
	public function get_unit_quantity(): float {
		return round( $this->quantity / openbb_unit_to_base( $this->unit_amount, $this->unit_type ), 3 );
	}

	public function get_unit_label(): string {
		return openbb_format_unit( $this->unit_amount, $this->unit_type );
	}
}
