<?php
/**
 * Product post type, category and supplier taxonomies, and meta registration.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MFCOOP_PRODUCT_POST_TYPE = 'mfcoop_product';
const MFCOOP_PRODUCT_TAXONOMY  = 'mfcoop_product_category';
const MFCOOP_SUPPLIER_TAXONOMY = 'mfcoop_supplier';

function mfcoop_register_product_post_type(): void {
	register_post_type(
		MFCOOP_PRODUCT_POST_TYPE,
		array(
			'labels'       => array(
				'name'          => __( 'Products', 'my-food-coop' ),
				'singular_name' => __( 'Product', 'my-food-coop' ),
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
		MFCOOP_PRODUCT_TAXONOMY,
		MFCOOP_PRODUCT_POST_TYPE,
		array(
			'labels'       => array(
				'name'          => __( 'Product Categories', 'my-food-coop' ),
				'singular_name' => __( 'Product Category', 'my-food-coop' ),
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
		MFCOOP_SUPPLIER_TAXONOMY,
		MFCOOP_PRODUCT_POST_TYPE,
		array(
			'labels'             => array(
				'name'          => __( 'Suppliers', 'my-food-coop' ),
				'singular_name' => __( 'Supplier', 'my-food-coop' ),
			),
			'public'             => false,
			'hierarchical'       => false,
			'show_ui'            => false,
			'show_in_rest'       => false,
			'rewrite'            => false,
			'query_var'          => false,
			'mfcoop_single_term' => true,
		)
	);

	foreach ( mfcoop_product_meta_fields() as $field => $args ) {
		register_post_meta(
			MFCOOP_PRODUCT_POST_TYPE,
			MFCOOP_META_PREFIX . $field,
			array_merge(
				array(
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => 'mfcoop_can_edit_product_meta',
				),
				$args
			)
		);
	}
}

/**
 * Field => register_post_meta() args. Stored under MFCOOP_META_PREFIX . $field.
 *
 * The post title mirrors the default name for WordPress search. Prices and charges
 * are integer minor units (e.g. cents). The price covers unit_amount of unit_type
 * (see units.php), e.g. 4000 per 50 'g'.
 */
function mfcoop_product_meta_fields(): array {
	$localized_text = array(
		'type'              => 'array',
		'default'           => array(),
		'sanitize_callback' => 'mfcoop_sanitize_localized_text',
	);

	return array(
		'name'             => $localized_text,
		'description'      => array_merge( $localized_text, array( 'sanitize_callback' => 'mfcoop_sanitize_localized_html' ) ),
		'unit_amount'      => array(
			'type'              => 'number',
			'default'           => MFCOOP_UNIT_AMOUNT_DEFAULT,
			'sanitize_callback' => 'mfcoop_sanitize_unit_amount',
		),
		'unit_type'        => array(
			'type'              => 'string',
			'default'           => MFCOOP_UNIT_TYPE_DEFAULT,
			'sanitize_callback' => 'mfcoop_sanitize_unit_type',
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

function mfcoop_can_edit_product_meta( $allowed, $meta_key, $post_id ): bool {
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Enforces the custom 'mfcoop_single_term' register_taxonomy() arg: an object keeps at
 * most one term, and when a write would leave several, the last term requested wins.
 *
 * Hooked to 'set_object_terms', which every term write (wp_set_object_terms(),
 * wp_add_object_terms(), wp_set_post_terms(), tax_input) passes through.
 */
function mfcoop_enforce_single_term( $object_id, $terms, $tt_ids, $taxonomy ): void {
	$taxonomy_object = get_taxonomy( $taxonomy );
	if ( ! $taxonomy_object || empty( $taxonomy_object->mfcoop_single_term ) || ! $tt_ids ) {
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
