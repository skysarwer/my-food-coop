<?php
/**
 * Co-op access fields on the core user profile screen.
 *
 * @package Open_Bulk_Buying
 */

class Test_OPENBB_User_Profile extends OPENBB_TestCase {

	public function test_invalid_nonce_reports_an_error_without_changing_access(): void {
		$user_id = $this->make_user( 'member' );
		wp_set_current_user( $this->make_user( '', 'administrator' ) );
		$previous = $_POST;
		$_POST    = array(
			'openbb_access_nonce' => 'invalid',
			'openbb_coop_role'    => 'manager',
		);

		try {
			$errors = new WP_Error();
			openbb_save_user_access_fields( $errors, true, get_userdata( $user_id ) );
			$this->assertSame( 'openbb_invalid_nonce', $errors->get_error_code() );
			$this->assertSame( 'member', OPENBB_Access::get_coop_role( $user_id ) );
		} finally {
			$_POST = $previous;
		}
	}

	public function test_core_validation_errors_prevent_access_changes(): void {
		$user_id = $this->make_user( 'member' );
		wp_set_current_user( $this->make_user( '', 'administrator' ) );
		$previous = $_POST;
		$_POST    = array(
			'openbb_access_nonce' => wp_create_nonce( 'openbb_user_access_' . $user_id ),
			'openbb_coop_role'    => 'manager',
		);

		try {
			$errors = new WP_Error( 'invalid_email', 'Invalid email.' );
			openbb_save_user_access_fields( $errors, true, get_userdata( $user_id ) );
			$this->assertSame( 'member', OPENBB_Access::get_coop_role( $user_id ) );
		} finally {
			$_POST = $previous;
		}
	}

	public function test_invalid_grant_prevents_role_changes(): void {
		$user_id = $this->make_user( 'member' );
		wp_set_current_user( $this->make_user( '', 'administrator' ) );
		$previous = $_POST;
		$_POST    = array(
			'openbb_access_nonce' => wp_create_nonce( 'openbb_user_access_' . $user_id ),
			'openbb_coop_role'    => 'manager',
			'openbb_grants'       => array( array( 'invalid' ) ),
		);

		try {
			$errors = new WP_Error();
			openbb_save_user_access_fields( $errors, true, get_userdata( $user_id ) );
			$this->assertSame( 'openbb_invalid_access', $errors->get_error_code() );
			$this->assertSame( 'member', OPENBB_Access::get_coop_role( $user_id ) );
		} finally {
			$_POST = $previous;
		}
	}
}
