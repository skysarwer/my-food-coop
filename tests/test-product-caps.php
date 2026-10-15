<?php
/**
 * Products and their terms need openbb_manage_products, not post or category caps (G1/G2).
 *
 * @package Open_Bulk_Buying
 */

class Test_OPENBB_Product_Caps extends OPENBB_TestCase {

	private function make_product(): int {
		return self::factory()->post->create( array( 'post_type' => OPENBB_PRODUCT_POST_TYPE ) );
	}

	public function test_wordpress_content_roles_cannot_touch_products(): void {
		$product = $this->make_product();
		$type    = get_post_type_object( OPENBB_PRODUCT_POST_TYPE );

		foreach ( array( 'editor', 'author', 'contributor' ) as $wp_role ) {
			$user_id = $this->make_user( 'member', $wp_role );

			$this->assertFalse( user_can( $user_id, 'edit_post', $product ), $wp_role );
			$this->assertFalse( user_can( $user_id, 'delete_post', $product ), $wp_role );
			$this->assertFalse( user_can( $user_id, $type->cap->create_posts ), $wp_role );
			$this->assertFalse( user_can( $user_id, 'edit_post_meta', $product, OPENBB_META_PREFIX . 'price' ), $wp_role );
		}
	}

	public function test_wordpress_content_roles_cannot_touch_product_terms(): void {
		$editor = $this->make_user( '', 'editor' );

		foreach ( array( OPENBB_PRODUCT_TAXONOMY, OPENBB_SUPPLIER_TAXONOMY ) as $taxonomy ) {
			$caps = get_taxonomy( $taxonomy )->cap;
			$this->assertFalse( user_can( $editor, $caps->manage_terms ), $taxonomy );
			$this->assertFalse( user_can( $editor, $caps->assign_terms ), $taxonomy );
		}
	}

	public function test_coordinators_manage_products_and_terms(): void {
		$product     = $this->make_product();
		$coordinator = $this->make_user( 'coordinator', '' );

		$this->assertTrue( user_can( $coordinator, 'edit_post', $product ) );
		$this->assertTrue( user_can( $coordinator, 'delete_post', $product ) );
		$this->assertTrue( user_can( $coordinator, 'edit_post_meta', $product, OPENBB_META_PREFIX . 'price' ) );
		foreach ( array( OPENBB_PRODUCT_TAXONOMY, OPENBB_SUPPLIER_TAXONOMY ) as $taxonomy ) {
			$caps = get_taxonomy( $taxonomy )->cap;
			$this->assertTrue( user_can( $coordinator, $caps->manage_terms ), $taxonomy );
			$this->assertTrue( user_can( $coordinator, $caps->assign_terms ), $taxonomy );
		}
	}
}
