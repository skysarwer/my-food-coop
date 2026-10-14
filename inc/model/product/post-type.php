<?php
/**
 * Product post type, category and supplier taxonomies, and meta registration.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPENBB_PRODUCT_POST_TYPE = 'openbb_product';
const OPENBB_PRODUCT_TAXONOMY  = 'openbb_product_category';
const OPENBB_SUPPLIER_TAXONOMY = 'openbb_supplier';

function openbb_register_product_post_type(): void {
	register_post_type(
		OPENBB_PRODUCT_POST_TYPE,
		array(
			'labels'       => array(
				'name'          => __( 'Products', 'open-bulk-buying' ),
				'singular_name' => __( 'Product', 'open-bulk-buying' ),
			),
			'public'       => false,
			'show_ui'      => false,
			'show_in_rest' => false,
			'rewrite'      => false,
			'query_var'    => false,
			'supports'     => array( 'title', 'thumbnail', 'custom-fields' ),
		)
	);

	register_taxonomy(
		OPENBB_PRODUCT_TAXONOMY,
		OPENBB_PRODUCT_POST_TYPE,
		array(
			'labels'       => array(
				'name'          => __( 'Product Categories', 'open-bulk-buying' ),
				'singular_name' => __( 'Product Category', 'open-bulk-buying' ),
			),
			'public'       => false,
			'hierarchical' => true,
			'show_ui'      => false,
			'show_in_rest' => false,
			'rewrite'      => false,
			'query_var'    => false,
		)
	);

	register_taxonomy(
		OPENBB_SUPPLIER_TAXONOMY,
		OPENBB_PRODUCT_POST_TYPE,
		array(
			'labels'             => array(
				'name'          => __( 'Suppliers', 'open-bulk-buying' ),
				'singular_name' => __( 'Supplier', 'open-bulk-buying' ),
			),
			'public'             => false,
			'hierarchical'       => false,
			'show_ui'            => false,
			'show_in_rest'       => false,
			'rewrite'            => false,
			'query_var'          => false,
			'openbb_single_term' => true,
		)
	);

	foreach ( openbb_product_meta_fields() as $field => $args ) {
		register_post_meta(
			OPENBB_PRODUCT_POST_TYPE,
			OPENBB_META_PREFIX . $field,
			array_merge(
				array(
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => 'openbb_can_edit_product_meta',
				),
				$args
			)
		);
	}
}

/**
 * Field => register_post_meta() args. Stored under OPENBB_META_PREFIX . $field.
 *
 * The post title mirrors the default name for WordPress search. Prices and charges
 * are integer minor units (e.g. cents). The price covers unit_amount of unit_type
 * (see units.php), e.g. 4000 per 50 'g'.
 */
function openbb_product_meta_fields(): array {
	$localized_text = array(
		'type'              => 'array',
		'default'           => array(),
		'sanitize_callback' => 'openbb_sanitize_localized_text',
	);

	return array(
		'name'             => $localized_text,
		'description'      => array_merge( $localized_text, array( 'sanitize_callback' => 'openbb_sanitize_localized_html' ) ),
		'brand'            => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'unit_amount'      => array(
			'type'              => 'number',
			'default'           => OPENBB_UNIT_AMOUNT_DEFAULT,
			'sanitize_callback' => 'openbb_sanitize_unit_amount',
		),
		'unit_type'        => array(
			'type'              => 'string',
			'default'           => OPENBB_UNIT_TYPE_DEFAULT,
			'sanitize_callback' => 'openbb_sanitize_unit_type',
		),
		'price'            => array(
			'type'              => 'integer',
			'default'           => 0,
			'sanitize_callback' => 'absint',
		),
		'container_charge' => array(
			'type'              => 'integer',
			'default'           => 0,
			'sanitize_callback' => 'absint',
		),
		'active'           => array(
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
		),
	);
}

function openbb_can_edit_product_meta( $allowed, $meta_key, $post_id ): bool {
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Blocks unit meta writes that OPENBB_Product::validate_unit_change() rejects, so
 * every write path follows the rule. Save routines should call the validator first
 * to show its error, and write unit meta before the active flag.
 *
 * Hooked to 'add_post_metadata', 'update_post_metadata' and 'delete_post_metadata';
 * returning non-null short-circuits the write.
 */
function openbb_guard_product_unit_meta( $check, $object_id, $meta_key, $meta_value = null ) {
	$fields = array(
		OPENBB_META_PREFIX . 'unit_amount' => 'unit_amount',
		OPENBB_META_PREFIX . 'unit_type'   => 'unit_type',
	);
	if ( null !== $check || ! isset( $fields[ $meta_key ] ) || ! $object_id || OPENBB_PRODUCT_POST_TYPE !== get_post_type( $object_id ) ) {
		return $check;
	}

	$product = OPENBB_Product::get( (int) $object_id );
	if ( ! $product ) {
		return $check;
	}

	// Deleting the meta falls back to the registered default.
	$deleting    = 'delete_post_metadata' === current_filter();
	$unit_amount = $product->unit_amount;
	$unit_type   = $product->unit_type;
	if ( 'unit_amount' === $fields[ $meta_key ] ) {
		$unit_amount = $deleting ? OPENBB_UNIT_AMOUNT_DEFAULT : openbb_sanitize_unit_amount( $meta_value );
	} else {
		$unit_type = $deleting ? OPENBB_UNIT_TYPE_DEFAULT : openbb_sanitize_unit_type( $meta_value );
	}

	return is_wp_error( $product->validate_unit_change( $unit_amount, $unit_type ) ) ? false : $check;
}

/**
 * Enforces the custom 'openbb_single_term' register_taxonomy() arg: an object keeps at
 * most one term, and when a write would leave several, the last term requested wins.
 *
 * Hooked to 'set_object_terms', which every term write (wp_set_object_terms(),
 * wp_add_object_terms(), wp_set_post_terms(), tax_input) passes through.
 */
function openbb_enforce_single_term( $object_id, $terms, $tt_ids, $taxonomy ): void {
	$taxonomy_object = get_taxonomy( $taxonomy );
	if ( ! $taxonomy_object || empty( $taxonomy_object->openbb_single_term ) || ! $tt_ids ) {
		return;
	}

	$keep    = (int) end( $tt_ids );
	$current = wp_get_object_terms( $object_id, $taxonomy, array( 'update_term_meta_cache' => false ) );
	if ( is_wp_error( $current ) ) {
		return;
	}

	$extra = array();
	foreach ( $current as $term ) {
		if ( (int) $term->term_taxonomy_id !== $keep ) {
			$extra[] = (int) $term->term_id;
		}
	}

	if ( $extra ) {
		wp_remove_object_terms( $object_id, $extra, $taxonomy );
	}
}
