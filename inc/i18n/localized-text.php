<?php
/**
 * Localized text is stored as a language => text map with a required 'default'
 * entry, e.g. array( 'default' => 'Olive oil', 'fr' => 'Huile d’olive' ).
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mfcoop_languages(): array {
	return function_exists( 'pll_languages_list' ) ? (array) pll_languages_list( array( 'fields' => 'slug' ) ) : array();
}

function mfcoop_current_language(): string {
	return function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
}

function mfcoop_normalize_localized( $value ): array {
	if ( is_string( $value ) ) {
		return array( 'default' => $value );
	}

	$normalized = array( 'default' => '' );
	foreach ( (array) $value as $language => $text ) {
		if ( is_string( $language ) && is_scalar( $text ) ) {
			$normalized[ sanitize_key( $language ) ] = (string) $text;
		}
	}

	return $normalized;
}

/**
 * Returns the translation for $language (default: current language), falling back to the default text.
 */
function mfcoop_localized( $value, ?string $language = null ): string {
	$value    = mfcoop_normalize_localized( $value );
	$language = $language ?? mfcoop_current_language();

	return '' !== ( $value[ $language ] ?? '' ) ? $value[ $language ] : $value['default'];
}

function mfcoop_sanitize_localized_text( $value ): array {
	return array_map( 'sanitize_text_field', mfcoop_normalize_localized( $value ) );
}

function mfcoop_sanitize_localized_html( $value ): array {
	return array_map( 'wp_kses_post', mfcoop_normalize_localized( $value ) );
}
