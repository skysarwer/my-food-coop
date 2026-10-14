<?php
/**
 * One member's order for one cycle. Amounts are integer minor units (e.g. cents).
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFCOOP_Order extends MFCOOP_Entity {

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
	 * @return MFCOOP_Order_Line[]
	 */
	public function get_lines(): array {
		if ( null === $this->lines ) {
			$this->lines = $this->id ? MFCOOP_Orders::get_lines( $this->id ) : array();
		}
		return $this->lines;
	}

	public function is_collected(): bool {
		return null !== $this->collected_at;
	}
}
