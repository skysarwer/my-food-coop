<?php
/**
 * Product units. A product's price covers a quantified unit: an amount paired with
 * a unit type, e.g. 50 + 'g' for "$40 per 50 g". Unit type codes are shared across
 * languages; their labels come from WordPress i18n.
 *
 * Inventory is counted in the base unit of the unit type's dimension (g, ml or
 * units), so changing a product from "per 50 g" to "per kg" doesn't change what
 * existing stock or reservations mean.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPENBB_UNIT_TYPE_DEFAULT   = 'unit';
const OPENBB_UNIT_AMOUNT_DEFAULT = 1.0;

/**
 * Unit type code => label, for pickers.
 */
function openbb_unit_types(): array {
	return array(
		'unit' => __( 'Units', 'open-bulk-buying' ),
		'g'    => __( 'Grams', 'open-bulk-buying' ),
		'kg'   => __( 'Kilograms', 'open-bulk-buying' ),
		'ml'   => __( 'Millilitres', 'open-bulk-buying' ),
		'l'    => __( 'Litres', 'open-bulk-buying' ),
	);
}

function openbb_is_unit_type( $type ): bool {
	return is_string( $type ) && array_key_exists( $type, openbb_unit_types() );
}

/**
 * Unit type code => array( base unit type, how many base units one of it holds ).
 * Unit types share a dimension when they share a base unit.
 */
function openbb_unit_conversions(): array {
	return array(
		'unit' => array( 'unit', 1 ),
		'g'    => array( 'g', 1 ),
		'kg'   => array( 'g', 1000 ),
		'ml'   => array( 'ml', 1 ),
		'l'    => array( 'ml', 1000 ),
	);
}

function openbb_base_unit_type( string $type ): string {
	return openbb_unit_conversions()[ openbb_sanitize_unit_type( $type ) ][0];
}

/**
 * Base units in $amount of $type, e.g. 1.5 'kg' => 1500 (g).
 */
function openbb_unit_to_base( float $amount, string $type ): float {
	return round( $amount * openbb_unit_conversions()[ openbb_sanitize_unit_type( $type ) ][1], 3 );
}

function openbb_units_share_dimension( string $type_a, string $type_b ): bool {
	return openbb_base_unit_type( $type_a ) === openbb_base_unit_type( $type_b );
}

function openbb_sanitize_unit_type( $type ): string {
	return openbb_is_unit_type( $type ) ? $type : OPENBB_UNIT_TYPE_DEFAULT;
}

/**
 * Positive amount with at most 3 decimals (matching the decimal(12,3) columns); anything else falls back to 1.
 */
function openbb_sanitize_unit_amount( $amount ): float {
	$amount = is_numeric( $amount ) ? round( (float) $amount, 3 ) : 0.0;

	return $amount > 0 ? $amount : OPENBB_UNIT_AMOUNT_DEFAULT;
}

/**
 * Localized number without trailing zeros, e.g. 50, 1.5, 0.125.
 */
function openbb_format_quantity( float $quantity ): string {
	$fraction = rtrim( substr( sprintf( '%.3F', $quantity ), -3 ), '0' );

	return number_format_i18n( $quantity, strlen( $fraction ) );
}

/**
 * Human-readable quantified unit, e.g. "50 g", "1.5 kg", "6 units".
 */
function openbb_format_unit( float $amount, string $type ): string {
	$number = openbb_format_quantity( $amount );

	switch ( $type ) {
		case 'g':
			/* translators: %s: amount in grams. */
			return sprintf( __( '%s g', 'open-bulk-buying' ), $number );
		case 'kg':
			/* translators: %s: amount in kilograms. */
			return sprintf( __( '%s kg', 'open-bulk-buying' ), $number );
		case 'ml':
			/* translators: %s: amount in millilitres. */
			return sprintf( __( '%s ml', 'open-bulk-buying' ), $number );
		case 'l':
			/* translators: %s: amount in litres. */
			return sprintf( __( '%s L', 'open-bulk-buying' ), $number );
		default:
			// Plural rules expect integers; fractional counts take the plural form.
			$count = floor( $amount ) === $amount ? (int) $amount : max( 2, (int) ceil( $amount ) );
			/* translators: %s: number of units. */
			return sprintf( _n( '%s unit', '%s units', $count, 'open-bulk-buying' ), $number );
	}
}
