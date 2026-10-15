<?php
/**
 * PHPUnit bootstrap. WP_TESTS_DIR points to the WordPress PHPUnit test library,
 * configured with a disposable test database.
 *
 * @package Open_Bulk_Buying
 */

$openbb_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $openbb_tests_dir || ! file_exists( $openbb_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Set WP_TESTS_DIR to the WordPress PHPUnit test library directory (containing includes/functions.php). Configure it with a disposable test database, never your normal WordPress database.\n" );
	exit( 1 );
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

require_once $openbb_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/open-bulk-buying.php';
	}
);

require $openbb_tests_dir . '/includes/bootstrap.php';
require __DIR__ . '/class-openbb-testcase.php';
