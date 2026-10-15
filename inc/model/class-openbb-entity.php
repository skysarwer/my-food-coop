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

	/**
	 * Inserts or updates a row for entities whose database representation is flat.
	 *
	 * Subclasses remain responsible for selecting columns and formats; JSON-backed
	 * fields and aggregate records need model-specific persistence.
	 */
	protected function save_row( string $table, array $data, array $formats = array() ): bool {
		global $wpdb;

		if ( empty( $this->id ) ) {
			if ( false === $wpdb->insert( $table, $data, $formats ?: null ) ) {
				return false;
			}

			$this->id = (int) $wpdb->insert_id;

			return true;
		}

		$id      = (int) $this->id;
		$updated = $wpdb->update( $table, $data, array( 'id' => $id ), $formats ?: null, array( '%d' ) );
		if ( false === $updated ) {
			return false;
		}

		return 0 !== $updated || null !== $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $id ) );
	}

	protected function delete_row( string $table ): bool {
		global $wpdb;

		return ! empty( $this->id ) && 1 === $wpdb->delete( $table, array( 'id' => (int) $this->id ), array( '%d' ) );
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
