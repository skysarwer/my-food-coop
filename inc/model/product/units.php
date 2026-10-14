<?php
/**
 * Product units. A product's price covers a quantified unit: an amount paired with
 * a unit type, e.g. 50 + 'g' for "$40 per 50 g". Unit type codes are shared across
 * languages; their labels come from WordPress i18n.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MFCOOP_UNIT_TYPE_DEFAULT   = 'unit';
const MFCOOP_UNIT_AMOUNT_DEFAULT = 1.0;

/**
 * Unit type code => label, for pickers.
 */
function mfcoop_unit_types(): array {
	return array(
		'unit' => __( 'Units', 'my-food-coop' ),
		'g'    => __( 'Grams', 'my-food-coop' ),
		'kg'   => __( 'Kilograms', 'my-food-coop' ),
		'ml'   => __( 'Millilitres', 'my-food-coop' ),
		'l'    => __( 'Litres', 'my-food-coop' ),
	);
}

function mfcoop_is_unit_type( $type ): bool {
	return is_string( $type ) && array_key_exists( $type, mfcoop_unit_types() );
}

function mfcoop_sanitize_unit_type( $type ): string {
	return mfcoop_is_unit_type( $type ) ? $type : MFCOOP_UNIT_TYPE_DEFAULT;
}

/**
 * Positive amount with at most 3 decimals (matching the decimal(12,3) columns); anything else falls back to 1.
 */
function mfcoop_sanitize_unit_amount( $amount ): float {
	$amount = is_numeric( $amount ) ? round( (float) $amount, 3 ) : 0.0;

	return $amount > 0 ? $amount : MFCOOP_UNIT_AMOUNT_DEFAULT;
}

/**
 * Localized number without trailing zeros, e.g. 50, 1.5, 0.125.
 */
function mfcoop_format_quantity( float $quantity ): string {
	$fraction = rtrim( substr( sprintf( '%.3F', $quantity ), -3 ), '0' );

	return number_format_i18n( $quantity, strlen( $fraction ) );
}

/**
 * Human-readable quantified unit, e.g. "50 g", "1.5 kg", "6 units".
 */
function mfcoop_format_unit( float $amount, string $type ): string {
	$number = mfcoop_format_quantity( $amount );

	switch ( $type ) {
		case 'g':
			/* translators: %s: amount in grams. */
			return sprintf( __( '%s g', 'my-food-coop' ), $number );
		case 'kg':
			/* translators: %s: amount in kilograms. */
			return sprintf( __( '%s kg', 'my-food-coop' ), $number );
		case 'ml':
			/* translators: %s: amount in millilitres. */
			return sprintf( __( '%s ml', 'my-food-coop' ), $number );
		case 'l':
			/* translators: %s: amount in litres. */
			return sprintf( __( '%s L', 'my-food-coop' ), $number );
		default:
			// Plural rules expect integers; fractional counts take the plural form.
			$count = floor( $amount ) === $amount ? (int) $amount : max( 2, (int) ceil( $amount ) );
			/* translators: %s: number of units. */
			return sprintf( _n( '%s unit', '%s units', $count, 'my-food-coop' ), $number );
	}
}
