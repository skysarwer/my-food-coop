<?php
/**
 * One member's order for one cycle. Amounts are integer minor units (e.g. cents).
 * language is the member's language when the order was submitted; line snapshots use it.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Order extends OPENBB_Entity {

	const STATUS_DRAFT     = 'draft';
	const STATUS_SUBMITTED = 'submitted';
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_COMPLETED = 'completed';

	const AID_NONE     = 'none';
	const AID_PENDING  = 'pending';
	const AID_APPROVED = 'approved';
	const AID_DECLINED = 'declined';

	public $id               = 0;
	public $cycle_id         = 0;
	public $user_id          = 0;
	public $status           = self::STATUS_DRAFT;
	public $language         = '';
	public $currency         = '';
	public $subtotal         = 0;
	public $container_credit = 0;
	public $donation         = 0;
	public $aid_status       = self::AID_NONE;
	public $aid_reason       = '';
	public $aid_requested    = 0;
	public $aid_approved     = 0;
	public $aid_decided_by   = null;
	public $aid_decided_at   = null;
	public $total            = 0;
	public $notes            = '';
	public $submitted_at     = null;
	public $collected_at     = null;
	public $created_at       = null;
	public $updated_at       = null;

	private $lines = null;

	protected function cast(): void {
		$this->id               = (int) $this->id;
		$this->cycle_id         = (int) $this->cycle_id;
		$this->user_id          = (int) $this->user_id;
		$this->language         = (string) $this->language;
		$this->subtotal         = (int) $this->subtotal;
		$this->container_credit = (int) $this->container_credit;
		$this->donation         = (int) $this->donation;
		$this->aid_reason       = (string) $this->aid_reason;
		$this->aid_requested    = (int) $this->aid_requested;
		$this->aid_approved     = (int) $this->aid_approved;
		$this->aid_decided_by   = self::nullable_int( $this->aid_decided_by );
		$this->total            = (int) $this->total;
		$this->notes            = (string) $this->notes;
	}

	/**
	 * @return OPENBB_Order_Line[]
	 */
	public function get_lines(): array {
		if ( null === $this->lines ) {
			$this->lines = $this->id ? OPENBB_Orders::get_lines( $this->id ) : array();
		}
		return $this->lines;
	}

	public function is_collected(): bool {
		return null !== $this->collected_at;
	}

	/**
	 * Recomputes subtotal, container_credit and total from the lines; call before
	 * every save so the stored amounts never drift from the lines. Approved aid can
	 * reduce the amount owed for goods to zero but not below, and never offsets the donation.
	 *
	 * @param OPENBB_Order_Line[]|null $lines Defaults to the saved lines.
	 */
	public function recalculate_totals( ?array $lines = null ): void {
		$subtotal         = 0;
		$container_credit = 0;
		foreach ( $lines ?? $this->get_lines() as $line ) {
			$subtotal         += $line->calculate_total();
			$container_credit += $line->calculate_container_credit();
		}

		$this->subtotal         = $subtotal;
		$this->container_credit = $container_credit;
		$this->total            = max( 0, $subtotal - $container_credit - $this->aid_approved ) + $this->donation;
	}
}
