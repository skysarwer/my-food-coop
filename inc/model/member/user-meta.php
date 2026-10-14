<?php
/**
 * Member user meta registration. WordPress owns identity (email, names, password);
 * co-op fields live in prefixed user meta.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field => sanitize callback. Stored under OPENBB_META_PREFIX . $field.
 */
function openbb_member_meta_fields(): array {
	return array(
		'membership_status' => 'sanitize_key',
		'joined_at'         => 'sanitize_text_field',
		'phone'             => 'sanitize_text_field',
		'language'          => 'sanitize_key',
		'rules_version'     => 'absint',
		'rules_accepted_at' => 'sanitize_text_field',
	);
}

function openbb_register_member_meta(): void {
	foreach ( openbb_member_meta_fields() as $field => $sanitize_callback ) {
		register_meta(
			'user',
			OPENBB_META_PREFIX . $field,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => false,
				'sanitize_callback' => $sanitize_callback,
				'auth_callback'     => 'openbb_can_edit_member_meta',
			)
		);
	}
}

function openbb_can_edit_member_meta( $allowed, $meta_key, $user_id ): bool {
	return current_user_can( 'edit_user', $user_id );
}
