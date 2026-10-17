<?php
/**
 * Co-op admin screens: a "Co-op" menu whose pages mount the React admin app
 * (src/admin, built to build/admin) and the block editor stylesheet.
 *
 * Every screen talks to the openbb/v1 REST API, which enforces permissions; the
 * menu capabilities here only decide what is shown.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OPENBB_Admin {

	const MENU_SLUG = 'openbb';

	/** Screen => [ page slug, capability ]. */
	const SCREENS = array(
		'products' => array( 'openbb-products', 'openbb_manage_products' ),
		'cycles'   => array( 'openbb-cycles', 'openbb_manage_cycles' ),
		'orders'   => array( 'openbb-orders', 'openbb_view_orders' ),
		'members'  => array( 'openbb-members', 'openbb_view_members' ),
	);

	/** @var array<string, string> Hook suffix => screen. */
	private static $hooks = array();

	public static function register_hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_block_editor_styles' ) );
	}

	public static function add_menu(): void {
		add_menu_page(
			__( 'Co-op', 'open-bulk-buying' ),
			__( 'Co-op', 'open-bulk-buying' ),
			'openbb_access_admin',
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-carrot',
			26
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Co-op overview', 'open-bulk-buying' ),
			__( 'Overview', 'open-bulk-buying' ),
			'openbb_access_admin',
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard' )
		);

		foreach ( self::screen_titles() as $screen => $title ) {
			list( $slug, $cap ) = self::SCREENS[ $screen ];

			$hook = add_submenu_page(
				self::MENU_SLUG,
				$title,
				$title,
				$cap,
				$slug,
				static function () use ( $screen, $cap ): void {
					self::render_screen( $screen, $cap );
				}
			);
			if ( $hook ) {
				self::$hooks[ $hook ] = $screen;
			}
		}
	}

	private static function screen_titles(): array {
		return array(
			'products' => __( 'Products', 'open-bulk-buying' ),
			'cycles'   => __( 'Cycles', 'open-bulk-buying' ),
			'orders'   => __( 'Orders', 'open-bulk-buying' ),
			'members'  => __( 'Members', 'open-bulk-buying' ),
		);
	}

	public static function render_dashboard(): void {
		if ( ! current_user_can( 'openbb_access_admin' ) ) {
			wp_die( esc_html__( 'You are not allowed to access the co-op admin.', 'open-bulk-buying' ) );
		}

		$links = array();
		foreach ( self::screen_titles() as $screen => $title ) {
			list( $slug, $cap ) = self::SCREENS[ $screen ];
			if ( current_user_can( $cap ) ) {
				$links[ $title ] = admin_url( 'admin.php?page=' . $slug );
			}
		}
		if ( current_user_can( 'openbb_manage_settings' ) ) {
			$links[ __( 'Settings', 'open-bulk-buying' ) ] = OPENBB_Settings::page_url();
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Co-op', 'open-bulk-buying' ); ?></h1>
			<?php if ( $links ) : ?>
				<ul class="ul-disc">
					<?php foreach ( $links as $title => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php esc_html_e( 'There is nothing here for you to manage yet.', 'open-bulk-buying' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_screen( string $screen, string $cap ): void {
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'open-bulk-buying' ) );
		}
		?>
		<div class="wrap openbb-admin">
			<h1 class="screen-reader-text"><?php echo esc_html( self::screen_titles()[ $screen ] ); ?></h1>
			<div id="openbb-admin-root" data-screen="<?php echo esc_attr( $screen ); ?>">
				<?php if ( ! file_exists( OPENBB_PLUGIN_DIR . 'build/admin/index.asset.php' ) ) : ?>
					<div class="notice notice-error inline">
						<p><?php esc_html_e( 'The admin scripts have not been built. Run "npm install" and "npm run build" in the plugin folder.', 'open-bulk-buying' ); ?></p>
					</div>
				<?php else : ?>
					<p><?php esc_html_e( 'Loading…', 'open-bulk-buying' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public static function enqueue( string $hook_suffix ): void {
		if ( ! isset( self::$hooks[ $hook_suffix ] ) ) {
			return;
		}

		$asset_file = OPENBB_PLUGIN_DIR . 'build/admin/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset  = require $asset_file;
		$screen = self::$hooks[ $hook_suffix ];

		if ( 'products' === $screen ) {
			wp_enqueue_media();
		}

		wp_enqueue_script( 'openbb-admin', OPENBB_PLUGIN_URL . 'build/admin/index.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'openbb-admin', 'open-bulk-buying' );
		wp_add_inline_script( 'openbb-admin', 'window.openbbAdmin = ' . wp_json_encode( self::boot_data( $screen ) ) . ';', 'before' );

		if ( file_exists( OPENBB_PLUGIN_DIR . 'build/admin/style-index.css' ) ) {
			wp_enqueue_style( 'openbb-admin', OPENBB_PLUGIN_URL . 'build/admin/style-index.css', array( 'wp-components' ), $asset['version'] );
			wp_style_add_data( 'openbb-admin', 'rtl', 'replace' );
			self::enqueue_admin_ui_style( array( 'openbb-admin' ) );
		} else {
			wp_enqueue_style( 'wp-components' );
			self::enqueue_admin_ui_style( array( 'wp-components' ) );
		}
	}

	/**
	 * Labels and settings the screens need. Permissions are re-checked by the API.
	 */
	private static function boot_data( string $screen ): array {
		$caps = array();
		foreach (
			array(
				'openbb_manage_products',
				'openbb_manage_inventory',
				'openbb_manage_cycles',
				'openbb_view_orders',
				'openbb_edit_orders',
				'openbb_view_members',
				'openbb_edit_members',
				'openbb_view_aid',
				'openbb_manage_access',
				'upload_files',
			) as $cap
		) {
			$caps[ $cap ] = current_user_can( $cap );
		}

		$urls = array();
		foreach ( self::SCREENS as $name => $screen_info ) {
			$urls[ $name ] = admin_url( 'admin.php?page=' . $screen_info[0] );
		}

		$languages = array();
		foreach ( openbb_languages() as $language ) {
			$languages[] = $language;
		}

		return array(
			'screen'               => $screen,
			'currency'             => openbb_currency(),
			'locale'               => str_replace( '_', '-', get_user_locale() ),
			'unitTypes'            => openbb_unit_types(),
			'unitConversions'      => openbb_unit_conversions(),
			'languages'            => $languages,
			'translationLanguages' => OPENBB_REST_Controller::translation_languages(),
			'coopRoles'            => OPENBB_Access::role_labels(),
			'staffRoles'           => OPENBB_Access::STAFF_ROLES,
			'cycleStatuses'        => OPENBB_Cycle::status_labels(),
			'orderStatuses'        => OPENBB_Order::status_labels(),
			'stockReasons'         => OPENBB_Stock_Move::reason_labels(),
			'manualStockReasons'   => OPENBB_Stock_Move::MANUAL_REASONS,
			'caps'                 => $caps,
			'urls'                 => $urls,
		);
	}

	/**
	 * Editor-only styles for the plugin's blocks, mirroring Subtle Blocks: loaded in
	 * the editor (and its iframe) but not on the front end.
	 */
	public static function enqueue_block_editor_styles(): void {
		if ( ! is_admin() ) {
			return;
		}

		self::enqueue_admin_ui_style( array( 'wp-edit-blocks' ) );
	}

	/**
	 * The hand-written admin stylesheet (src/sass/admin.scss), shared by the Co-op
	 * screens and the block editor. Loaded after `$deps` so its rules win.
	 */
	private static function enqueue_admin_ui_style( array $deps ): void {
		$file = 'build/sass/admin.css';
		if ( ! file_exists( OPENBB_PLUGIN_DIR . $file ) ) {
			return;
		}

		// Not the .asset.php version: for a CSS-only entry it hashes the empty JS file.
		wp_enqueue_style( 'openbb-admin-ui', OPENBB_PLUGIN_URL . $file, $deps, (string) filemtime( OPENBB_PLUGIN_DIR . $file ) );
		wp_style_add_data( 'openbb-admin-ui', 'rtl', 'replace' );
	}
}
