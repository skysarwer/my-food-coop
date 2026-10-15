<?php
/**
 * A WordPress user with a co-op role on this site. Datetimes are UTC.
 *
 * For permissions, check capabilities rather than coop_role (see OPENBB_Access).
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Member extends OPENBB_Entity {

	public $user_id           = 0;
	public $email             = '';
	public $first_name        = '';
	public $last_name         = '';
	public $display_name      = '';
	public $phone             = '';
	public $language          = '';
	public $coop_role         = '';
	public $joined_at         = null;
	public $rules_version     = 0;
	public $rules_accepted_at = null;

	/**
	 * Null when the user doesn't exist or has no co-op role on this site.
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
		foreach ( array_keys( openbb_member_meta_fields() ) as $field ) {
			$data[ $field ] = openbb_get_member_field( $user->ID, $field );
		}
		$data['coop_role'] = OPENBB_Access::sanitize_role( $data['coop_role'] );

		return '' === $data['coop_role'] ? null : new self( $data );
	}

	protected function cast(): void {
		$this->user_id           = (int) $this->user_id;
		$this->joined_at         = '' === $this->joined_at ? null : $this->joined_at;
		$this->rules_version     = (int) $this->rules_version;
		$this->rules_accepted_at = '' === $this->rules_accepted_at ? null : $this->rules_accepted_at;
	}

	/**
	 * Member, coordinator or manager: someone who can order.
	 */
	public function is_active(): bool {
		return in_array( $this->coop_role, OPENBB_Access::ACTIVE_ROLES, true );
	}

	/**
	 * Signed up and awaiting approval.
	 */
	public function is_applicant(): bool {
		return OPENBB_Access::ROLE_APPLICANT === $this->coop_role;
	}

	/**
	 * An applicant who was turned down: inactive without ever having joined.
	 */
	public function is_declined(): bool {
		return OPENBB_Access::ROLE_INACTIVE === $this->coop_role && null === $this->joined_at;
	}

	/**
	 * $required_version_id comes from OPENBB_Rules::get_required_version_id(); 0 means no rules are published yet.
	 */
	public function has_accepted_rules( int $required_version_id ): bool {
		return $required_version_id > 0 && $this->rules_version >= $required_version_id;
	}
}
