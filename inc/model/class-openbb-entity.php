<?php
/**
 * Base class for records: hydrates public fields from a database row, then casts them.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class OPENBB_Entity {

	public function __construct( array $data = array() ) {
		// Subclass private state (e.g. lazy-loaded relations) isn't visible here, so it's never hydrated.
		$fields = get_object_vars( $this );
		foreach ( $data as $key => $value ) {
			if ( array_key_exists( $key, $fields ) ) {
				$this->$key = $value;
			}
		}
		$this->cast();
	}

	protected function cast(): void {}

	public function to_array(): array {
		return get_object_vars( $this );
	}

	protected static function nullable_int( $value ): ?int {
		return null === $value || '' === $value ? null : (int) $value;
	}

	protected static function nullable_float( $value ): ?float {
		return null === $value || '' === $value ? null : (float) $value;
	}

	protected static function decode_json( $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}
		$decoded = is_string( $value ) ? json_decode( $value, true ) : null;
		return is_array( $decoded ) ? $decoded : array();
	}
}
