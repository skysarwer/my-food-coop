<?php
/**
 * Localized text is stored as a language => text map with a required 'default'
 * entry, e.g. array( 'default' => 'Olive oil', 'fr' => 'Huile d’olive' ).
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Language codes available on the site. Uses the WPML language API, which is
 * also implemented by Polylang; other providers can hook `openbb_languages`.
 */
function openbb_languages(): array {
	$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
	$languages = is_array( $languages ) ? array_keys( $languages ) : array();

	return array_values( array_filter( array_map( 'sanitize_key', (array) apply_filters( 'openbb_languages', $languages ) ) ) );
}

/**
 * Current language code, via the WPML language API (also implemented by Polylang),
 * falling back to the language part of the current locale (e.g. 'fr' for 'fr_FR').
 */
function openbb_current_language(): string {
	$language = apply_filters( 'wpml_current_language', null );
	if ( ! is_string( $language ) || '' === $language || 'all' === $language ) {
		$language = strtok( determine_locale(), '_' );
	}

	return sanitize_key( (string) apply_filters( 'openbb_current_language', $language ) );
}

function openbb_normalize_localized( $value ): array {
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
function openbb_localized( $value, ?string $language = null ): string {
	$value    = openbb_normalize_localized( $value );
	$language = $language ?? openbb_current_language();

	return '' !== ( $value[ $language ] ?? '' ) ? $value[ $language ] : $value['default'];
}

function openbb_sanitize_localized_text( $value ): array {
	return array_map( 'sanitize_text_field', openbb_normalize_localized( $value ) );
}

function openbb_sanitize_localized_html( $value ): array {
	return array_map( 'wp_kses_post', openbb_normalize_localized( $value ) );
}
