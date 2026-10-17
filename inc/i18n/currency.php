<?php
/**
 * Currency. Amounts are stored as integer minor units (e.g. cents).
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPENBB_CURRENCY_OPTION = 'openbb_currency';

/**
 * ISO 4217 code for new orders. Until the Settings page (012) manages it, set the
 * openbb_currency option or filter 'openbb_currency'.
 */
function openbb_currency(): string {
	$currency = strtoupper( (string) apply_filters( 'openbb_currency', get_option( OPENBB_CURRENCY_OPTION, 'CAD' ) ) );

	return preg_match( '/^[A-Z]{3}$/', $currency ) ? $currency : 'CAD';
}
