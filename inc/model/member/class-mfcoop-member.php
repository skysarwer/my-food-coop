<?php
/**
 * A WordPress user with co-op membership meta. Datetimes are UTC.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFCOOP_Member extends MFCOOP_Entity {

	const STATUS_ACTIVE   = 'active';
	const STATUS_INACTIVE = 'inactive';

	public $user_id           = 0;
	public $email             = '';
	public $first_name        = '';
	public $last_name         = '';
	public $display_name      = '';
	public $phone             = '';
	public $language          = '';
	public $membership_status = '';
	public $joined_at         = null;
	public $rules_version     = 0;
	public $rules_accepted_at = null;

	/**
	 * Null when the user doesn't exist or has no membership.
	 */
	public static function get( int $user_id ): ?self {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}

		$data = array(
			'user_id'      => $user->ID,
			'email'        => $user->user_email,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'display_name' => $user->display_name,
		);
		foreach ( array_keys( mfcoop_member_meta_fields() ) as $field ) {
			$data[ $field ] = get_user_meta( $user->ID, MFCOOP_META_PREFIX . $field, true );
		}

		return '' === $data['membership_status'] ? null : new self( $data );
	}

	protected function cast(): void {
		$this->user_id           = (int) $this->user_id;
		$this->joined_at         = '' === $this->joined_at ? null : $this->joined_at;
		$this->rules_version     = (int) $this->rules_version;
		$this->rules_accepted_at = '' === $this->rules_accepted_at ? null : $this->rules_accepted_at;
	}

	public function is_active(): bool {
		return self::STATUS_ACTIVE === $this->membership_status;
	}

	/**
	 * $required_version_id comes from MFCOOP_Rules::get_required_version_id(); 0 means no rules are published yet.
	 */
	public function has_accepted_rules( int $required_version_id ): bool {
		return $required_version_id > 0 && $this->rules_version >= $required_version_id;
	}
}
