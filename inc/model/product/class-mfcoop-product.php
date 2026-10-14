<?php
/**
 * Product record.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFCOOP_Product extends MFCOOP_Entity {

	public $id               = 0;
	public $name             = array();
	public $description      = array();
	public $unit_amount      = MFCOOP_UNIT_AMOUNT_DEFAULT;
	public $unit_type        = MFCOOP_UNIT_TYPE_DEFAULT;
	public $price            = 0;
	public $container_charge = 0;
	public $active           = false;
	public $category_ids     = array();
	public $supplier_id      = 0;
	public $image_id         = 0;

	public static function get( int $id ): ?self {
		$post = get_post( $id );
		if ( ! $post || MFCOOP_PRODUCT_POST_TYPE !== $post->post_type ) {
			return null;
		}

		$data = array( 'id' => $post->ID );
		foreach ( array_keys( mfcoop_product_meta_fields() ) as $field ) {
			$data[ $field ] = get_post_meta( $post->ID, MFCOOP_META_PREFIX . $field, true );
		}

		$data['name'] = mfcoop_normalize_localized( $data['name'] );
		if ( '' === $data['name']['default'] ) {
			$data['name']['default'] = $post->post_title;
		}

		$category_ids         = wp_get_post_terms( $post->ID, MFCOOP_PRODUCT_TAXONOMY, array( 'fields' => 'ids' ) );
		$data['category_ids'] = is_wp_error( $category_ids ) ? array() : $category_ids;
		$supplier_ids         = wp_get_post_terms( $post->ID, MFCOOP_SUPPLIER_TAXONOMY, array( 'fields' => 'ids' ) );
		$data['supplier_id']  = is_wp_error( $supplier_ids ) || ! $supplier_ids ? 0 : reset( $supplier_ids );
		$data['image_id']     = (int) get_post_thumbnail_id( $post );

		return new self( $data );
	}

	protected function cast(): void {
		$this->id               = (int) $this->id;
		$this->name             = mfcoop_normalize_localized( $this->name );
		$this->description      = mfcoop_normalize_localized( $this->description );
		$this->unit_amount      = mfcoop_sanitize_unit_amount( $this->unit_amount );
		$this->unit_type        = mfcoop_sanitize_unit_type( $this->unit_type );
		$this->price            = (int) $this->price;
		$this->container_charge = (int) $this->container_charge;
		$this->active           = (bool) $this->active;
		$this->category_ids     = array_map( 'intval', (array) $this->category_ids );
		$this->supplier_id      = (int) $this->supplier_id;
		$this->image_id         = (int) $this->image_id;
	}

	public function get_name( ?string $language = null ): string {
		return mfcoop_localized( $this->name, $language );
	}

	public function get_description( ?string $language = null ): string {
		return mfcoop_localized( $this->description, $language );
	}

	/**
	 * The quantity the price covers, e.g. "50 g".
	 */
	public function get_unit_label(): string {
		return mfcoop_format_unit( $this->unit_amount, $this->unit_type );
	}

	public function is_active(): bool {
		return $this->active;
	}
}
