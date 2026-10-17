<?php
/**
 * Plugin settings and site-local maintenance actions for the Settings screen.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_REST_Settings extends OPENBB_REST_Controller {

	const DELETE_CONFIRMATION = 'DELETE ALL DATA';

	public function register_routes(): void {
		$can_manage = static function () {
			return self::authorize( 'openbb_manage_settings' );
		};

		register_rest_route(
			self::API_NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => $can_manage,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => $can_manage,
					'args'                => array(
						'test_mode' => array( 'type' => 'boolean' ),
					),
				),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/settings/demo-data',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'populate_demo_data' ),
				'permission_callback' => $can_manage,
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/settings/delete-all-data',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'delete_all_data' ),
				'permission_callback' => static function () {
					return self::authorize( 'manage_options' );
				},
				'args'                => array(
					'confirmation' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	public function get_settings(): WP_REST_Response {
		return rest_ensure_response( self::prepare_settings() );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ) {
		if ( $request->has_param( 'test_mode' ) ) {
			$enabled = (bool) $request->get_param( 'test_mode' );
			$saved   = OPENBB_Maintenance::is_test_mode() === $enabled
				|| update_option( OPENBB_Maintenance::TEST_MODE_OPTION, $enabled, false );
			if ( ! $saved ) {
				return new WP_Error( 'openbb_settings_save_failed', __( 'The settings could not be saved.', 'open-bulk-buying' ), array( 'status' => 500 ) );
			}
		}

		return rest_ensure_response( self::prepare_settings() );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function populate_demo_data() {
		$result = OPENBB_Maintenance::populate_test_data();
		if ( is_wp_error( $result ) ) {
			return self::with_status( $result, 'openbb_test_mode_disabled' === $result->get_error_code() || 'openbb_demo_product_conflict' === $result->get_error_code() ? 409 : 500 );
		}

		return rest_ensure_response( array( 'populated' => true ) );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_all_data( WP_REST_Request $request ) {
		if ( self::DELETE_CONFIRMATION !== $request->get_param( 'confirmation' ) ) {
			return self::invalid(
				/* translators: %s: the confirmation phrase. */
				sprintf( __( 'Type %s to confirm.', 'open-bulk-buying' ), self::DELETE_CONFIRMATION ),
				'openbb_delete_unconfirmed'
			);
		}

		$result = OPENBB_Maintenance::delete_all_site_data();
		if ( is_wp_error( $result ) ) {
			return self::with_status( $result, 500 );
		}

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	private static function prepare_settings(): array {
		return array(
			'test_mode' => OPENBB_Maintenance::is_test_mode(),
		);
	}

	private static function with_status( WP_Error $error, int $status ): WP_Error {
		$data = $error->get_error_data();
		if ( ! is_array( $data ) || ! isset( $data['status'] ) ) {
			$error->add_data( array_merge( is_array( $data ) ? $data : array(), array( 'status' => $status ) ) );
		}

		return $error;
	}
}
