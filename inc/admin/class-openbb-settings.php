<?php
/**
 * Open Bulk Buying settings and site-local maintenance actions.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Settings {

	const PAGE_SLUG = 'open-bulk-buying';

	public static function register_hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_openbb_save_test_mode', array( __CLASS__, 'save_test_mode' ) );
		add_action( 'admin_post_openbb_populate_test_data', array( __CLASS__, 'populate_test_data' ) );
		add_action( 'admin_post_openbb_delete_all_data', array( __CLASS__, 'delete_all_data' ) );
	}

	public static function add_menu(): void {
		add_options_page(
			__( 'Open Bulk Buying Settings', 'open-bulk-buying' ),
			__( 'Open Bulk Buying', 'open-bulk-buying' ),
			'openbb_manage_settings',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'openbb_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Open Bulk Buying settings.', 'open-bulk-buying' ) );
		}

		$test_mode = OPENBB_Maintenance::is_test_mode();
		$notice    = isset( $_GET['openbb_notice'] ) && is_string( $_GET['openbb_notice'] )
			? sanitize_key( wp_unslash( $_GET['openbb_notice'] ) )
			: '';
		$error_message = 'action_failed' === $notice ? get_transient( self::error_transient_key() ) : false;
		if ( false !== $error_message ) {
			delete_transient( self::error_transient_key() );
		}
		$messages  = array(
			'test_mode_saved' => __( 'Test mode setting saved.', 'open-bulk-buying' ),
			'demo_populated'  => __( 'Demo data is ready. Populating again will not duplicate the demo records.', 'open-bulk-buying' ),
			'data_deleted'    => __( "This site's Open Bulk Buying data has been deleted.", 'open-bulk-buying' ),
			'action_failed'   => __( 'The requested action failed. No success was reported; check the site database and try again.', 'open-bulk-buying' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Open Bulk Buying Settings', 'open-bulk-buying' ); ?></h1>
			<?php if ( isset( $messages[ $notice ] ) ) : ?>
				<div class="notice <?php echo 'action_failed' === $notice ? 'notice-error' : 'notice-success'; ?> is-dismissible">
					<p><?php echo esc_html( is_string( $error_message ) ? $error_message : $messages[ $notice ] ); ?></p>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Test mode', 'open-bulk-buying' ); ?></h2>
			<p><?php esc_html_e( 'Test mode enables a repeatable demo-data action. Demo records are marked and use the current site only.', 'open-bulk-buying' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="openbb_save_test_mode">
				<?php wp_nonce_field( 'openbb_save_test_mode' ); ?>
				<label for="openbb_test_mode">
					<input type="checkbox" id="openbb_test_mode" name="test_mode" value="1" <?php checked( $test_mode ); ?>>
					<?php esc_html_e( 'Enable test mode', 'open-bulk-buying' ); ?>
				</label>
				<?php submit_button( __( 'Save test mode', 'open-bulk-buying' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( $test_mode ) : ?>
				<h2><?php esc_html_e( 'Demo data', 'open-bulk-buying' ); ?></h2>
				<p><?php esc_html_e( 'Adds one clearly marked demo product, setup cycle, draft order and stock receipt. It does not create WordPress users or publish demo rules.', 'open-bulk-buying' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="openbb_populate_test_data">
					<?php wp_nonce_field( 'openbb_populate_test_data' ); ?>
					<?php submit_button( __( 'Populate demo data', 'open-bulk-buying' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>

			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<hr>
				<h2><?php esc_html_e( 'Danger zone', 'open-bulk-buying' ); ?></h2>
				<p><?php esc_html_e( "Permanently deletes this site's Open Bulk Buying records, products and terms, co-op user metadata, and plugin settings. WordPress users and roles, other plugins' data, and other sites on this network are not changed. The database schema is retained.", 'open-bulk-buying' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="openbb_delete_all_data">
					<?php wp_nonce_field( 'openbb_delete_all_data' ); ?>
					<p>
						<label for="openbb_delete_confirmation">
							<?php esc_html_e( 'Type DELETE ALL DATA to confirm:', 'open-bulk-buying' ); ?>
						</label>
						<input type="text" id="openbb_delete_confirmation" name="confirmation" required pattern="DELETE ALL DATA" autocomplete="off">
					</p>
					<?php submit_button( __( 'Delete all Open Bulk Buying data', 'open-bulk-buying' ), 'delete', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function save_test_mode(): void {
		if ( ! current_user_can( 'openbb_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Open Bulk Buying settings.', 'open-bulk-buying' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'openbb_save_test_mode' );

		$enabled = isset( $_POST['test_mode'] ) && '1' === wp_unslash( $_POST['test_mode'] );
		$saved   = OPENBB_Maintenance::is_test_mode() === $enabled
			|| update_option( OPENBB_Maintenance::TEST_MODE_OPTION, $enabled, false );

		self::redirect( $saved ? 'test_mode_saved' : 'action_failed' );
	}

	public static function populate_test_data(): void {
		if ( ! current_user_can( 'openbb_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Open Bulk Buying settings.', 'open-bulk-buying' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'openbb_populate_test_data' );

		$result = OPENBB_Maintenance::populate_test_data();
		if ( is_wp_error( $result ) ) {
			self::redirect_with_error( $result );
		}
		self::redirect( true === $result ? 'demo_populated' : 'action_failed' );
	}

	public static function delete_all_data(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only a WordPress Administrator can delete all Open Bulk Buying data.', 'open-bulk-buying' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'openbb_delete_all_data' );

		$confirmation = isset( $_POST['confirmation'] ) && is_string( $_POST['confirmation'] )
			? wp_unslash( $_POST['confirmation'] )
			: '';
		if ( 'DELETE ALL DATA' !== $confirmation ) {
			self::redirect( 'action_failed' );
		}

		$result = OPENBB_Maintenance::delete_all_site_data();
		if ( is_wp_error( $result ) ) {
			self::redirect_with_error( $result );
		}
		self::redirect( true === $result ? 'data_deleted' : 'action_failed' );
	}

	private static function redirect_with_error( WP_Error $error ): void {
		set_transient( self::error_transient_key(), $error->get_error_message(), MINUTE_IN_SECONDS );
		self::redirect( 'action_failed' );
	}

	private static function error_transient_key(): string {
		return 'openbb_settings_error_' . get_current_user_id();
	}

	private static function redirect( string $notice ): void {
		$url = add_query_arg(
			array(
				'page'          => self::PAGE_SLUG,
				'openbb_notice' => $notice,
			),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
