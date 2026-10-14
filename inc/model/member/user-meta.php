<?php
/**
 * Member user meta registration. WordPress owns identity (email, names, password);
 * co-op fields live in prefixed user meta.
 *
 * @package My_Food_Coop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field => sanitize callback. Stored under MFCOOP_META_PREFIX . $field.
 */
function mfcoop_member_meta_fields(): array {
	return array(
		'membership_status' => 'sanitize_key',
		'joined_at'         => 'sanitize_text_field',
		'phone'             => 'sanitize_text_field',
		'language'          => 'sanitize_key',
		'rules_version'     => 'absint',
		'rules_accepted_at' => 'sanitize_text_field',
	);
}

function mfcoop_register_member_meta(): void {
	foreach ( mfcoop_member_meta_fields() as $field => $sanitize_callback ) {
		register_meta(
			'user',
			MFCOOP_META_PREFIX . $field,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => false,
				'sanitize_callback' => $sanitize_callback,
				'auth_callback'     => 'mfcoop_can_edit_member_meta',
			)
		);
	}
}

function mfcoop_can_edit_member_meta( $allowed, $meta_key, $user_id ): bool {
	return current_user_can( 'edit_user', $user_id );
}
