<?php
/**
 * Interim co-op role and grant fields on the core Edit User screen, until the
 * Co-op → Access screen exists. Only Administrators reach other users' profiles
 * (that needs edit_users), and nobody can change their own co-op access.
 *
 * @package Open_Bulk_Buying
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooked to 'edit_user_profile', which only runs when editing someone else.
 */
function openbb_render_user_access_fields( WP_User $user ): void {
	if ( ! current_user_can( 'openbb_set_grants', $user->ID ) ) {
		return;
	}

	$role   = OPENBB_Access::get_coop_role( $user->ID );
	$grants = OPENBB_Access::get_grants( $user->ID );

	wp_nonce_field( 'openbb_user_access_' . $user->ID, 'openbb_access_nonce' );
	?>
	<h2><?php esc_html_e( 'Co-op access', 'open-bulk-buying' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="openbb_coop_role"><?php esc_html_e( 'Co-op role', 'open-bulk-buying' ); ?></label></th>
			<td>
				<select name="openbb_coop_role" id="openbb_coop_role">
					<?php if ( '' === $role ) : ?>
						<option value="" selected><?php esc_html_e( '— None —', 'open-bulk-buying' ); ?></option>
					<?php endif; ?>
					<?php foreach ( OPENBB_Access::role_labels() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $role, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Separate from the WordPress role. Members, coordinators and managers can order; coordinators and managers can run the co-op.', 'open-bulk-buying' ); ?></p>
				<?php if ( user_can( $user, 'manage_options' ) ) : ?>
					<p class="description"><?php esc_html_e( 'Administrators already have every co-op permission. A co-op role only lets them order.', 'open-bulk-buying' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Grants', 'open-bulk-buying' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Grants', 'open-bulk-buying' ); ?></legend>
					<?php foreach ( OPENBB_Access::grant_labels() as $grant => $label ) : ?>
						<label>
							<input type="checkbox" name="openbb_grants[]" value="<?php echo esc_attr( $grant ); ?>" <?php checked( in_array( $grant, $grants, true ) ); ?> />
							<?php echo esc_html( $label ); ?>
						</label><br />
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Extra permissions on top of the co-op role. They only apply to members, coordinators and managers.', 'open-bulk-buying' ); ?></p>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Validates the profile before saving access. Permissions are checked again by OPENBB_Access.
 */
function openbb_save_user_access_fields( WP_Error $errors, bool $update, $user ): void {
	if ( ! $update || $errors->has_errors() || ! isset( $_POST['openbb_access_nonce'] ) ) {
		return;
	}

	$user_id = (int) $user->ID;
	$nonce   = is_string( $_POST['openbb_access_nonce'] ) ? wp_unslash( $_POST['openbb_access_nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'openbb_user_access_' . $user_id ) ) {
		$errors->add( 'openbb_invalid_nonce', __( 'The co-op access form expired. Reload the profile and try again.', 'open-bulk-buying' ) );
		return;
	}

	$role   = isset( $_POST['openbb_coop_role'] ) && is_string( $_POST['openbb_coop_role'] ) ? wp_unslash( $_POST['openbb_coop_role'] ) : '';
	$grants = isset( $_POST['openbb_grants'] ) ? wp_unslash( $_POST['openbb_grants'] ) : array();
	if ( ( '' !== $role && OPENBB_Access::sanitize_role( $role ) !== $role )
		|| ! is_array( $grants )
		|| count( array_filter( $grants, 'is_string' ) ) !== count( $grants )
		|| count( OPENBB_Access::sanitize_grants( $grants ) ) !== count( array_unique( $grants ) )
	) {
		$errors->add( 'openbb_invalid_access', __( 'Select a valid co-op role and grants.', 'open-bulk-buying' ) );
		return;
	}

	if ( ! current_user_can( 'edit_user', $user_id )
		|| ! current_user_can( 'openbb_set_grants', $user_id )
		|| ( '' !== $role && ! current_user_can( 'openbb_set_coop_role', $user_id, $role ) )
	) {
		$errors->add( 'openbb_forbidden', __( 'You cannot change this person\'s co-op access.', 'open-bulk-buying' ) );
		return;
	}

	if ( '' !== $role ) {
		$result = OPENBB_Access::set_coop_role( $user_id, $role );
		if ( is_wp_error( $result ) ) {
			$errors->add( $result->get_error_code(), $result->get_error_message() );
			return;
		}
	}

	$result = OPENBB_Access::set_grants( $user_id, $grants );
	if ( is_wp_error( $result ) ) {
		$errors->add( $result->get_error_code(), $result->get_error_message() );
	}
}

function openbb_user_access_column( array $columns ): array {
	$columns['openbb_coop_role'] = __( 'Co-op role', 'open-bulk-buying' );
	return $columns;
}

function openbb_user_access_column_value( $value, string $column, int $user_id ): string {
	if ( 'openbb_coop_role' !== $column ) {
		return (string) $value;
	}

	$member = OPENBB_Member::get( $user_id );
	if ( ! $member ) {
		return '';
	}

	$label  = $member->is_declined() ? __( 'Declined', 'open-bulk-buying' ) : OPENBB_Access::role_labels()[ $member->coop_role ];
	$labels = OPENBB_Access::grant_labels();
	$grants = array_map(
		static function ( string $grant ) use ( $labels ): string {
			return $labels[ $grant ] ?? $grant;
		},
		OPENBB_Access::get_grants( $user_id )
	);

	return esc_html( $label . ( $grants ? ' (' . implode( ', ', $grants ) . ')' : '' ) );
}
