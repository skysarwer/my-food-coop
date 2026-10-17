<?php
/**
 * Model services: order editing rules, cycle validation and product deletion,
 * exercised without the REST layer.
 *
 * @package Open_Bulk_Buying
 */

class Test_OPENBB_Services extends OPENBB_TestCase {

	/** @var int */
	private $actor;

	public function set_up(): void {
		parent::set_up();
		$this->actor = $this->make_user( 'coordinator' );
	}

	private function make_product( float $stock = 0.0 ): OPENBB_Product {
		$product_id = self::factory()->post->create(
			array(
				'post_type'   => OPENBB_PRODUCT_POST_TYPE,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $product_id, OPENBB_META_PREFIX . 'name', array( 'default' => 'Oats' ) );
		update_post_meta( $product_id, OPENBB_META_PREFIX . 'price', 250 );
		update_post_meta( $product_id, OPENBB_META_PREFIX . 'active', true );

		$product = OPENBB_Product::get( $product_id );
		if ( $stock > 0 ) {
			$this->assertInstanceOf( 'OPENBB_Stock_Move', OPENBB_Inventory::record_manual_move( $product, $stock, OPENBB_Stock_Move::REASON_RECEIPT ) );
		}

		return $product;
	}

	private function start_order( int $cycle_id ): OPENBB_Order {
		$order = OPENBB_Order_Editor::start( OPENBB_Cycles::get( $cycle_id ), $this->make_user( 'member' ) );
		$this->assertInstanceOf( 'OPENBB_Order', $order );

		return $order;
	}

	private function submit( OPENBB_Order $order, int $product_id, float $qty ) {
		return OPENBB_Order_Editor::apply(
			$order,
			array(
				'status' => OPENBB_Order::STATUS_SUBMITTED,
				'lines'  => array(
					array(
						'product_id' => $product_id,
						'qty'        => $qty,
					),
				),
			),
			$this->actor
		);
	}

	public function test_order_editor_caps_lines_at_available_stock(): void {
		$product  = $this->make_product( 5 );
		$cycle_id = $this->make_cycle( OPENBB_Cycle::STATUS_ORDERING );

		$first = $this->submit( $this->start_order( $cycle_id ), $product->id, 3 );
		$this->assertInstanceOf( 'OPENBB_Order', $first );
		$this->assertGreaterThan( 0, $first->id );

		$second = $this->submit( $this->start_order( $cycle_id ), $product->id, 3 );
		$this->assertWPError( $second );
		$this->assertSame( 'openbb_insufficient_stock', $second->get_error_code() );
		$this->assertSame( 409, $second->get_error_data()['status'] );
		$this->assertEquals( 2, $second->get_error_data()['max_qty'] );
	}

	public function test_order_editor_requires_a_reason_to_change_a_submitted_order(): void {
		$product  = $this->make_product( 5 );
		$cycle_id = $this->make_cycle( OPENBB_Cycle::STATUS_ORDERING );
		$order    = $this->submit( $this->start_order( $cycle_id ), $product->id, 2 );
		$this->assertInstanceOf( 'OPENBB_Order', $order );

		$result = OPENBB_Order_Editor::apply( $order, array( 'donation' => 100 ), $this->actor );
		$this->assertWPError( $result );
		$this->assertSame( 'openbb_reason_required', $result->get_error_code() );

		$result = OPENBB_Order_Editor::apply(
			$order,
			array(
				'donation' => 100,
				'reason'   => 'Member asked',
			),
			$this->actor
		);
		$this->assertInstanceOf( 'OPENBB_Order', $result );
		$this->assertSame( 100, (int) $result->donation );
	}

	public function test_order_editor_rejects_stale_edits(): void {
		$cycle_id = $this->make_cycle( OPENBB_Cycle::STATUS_ORDERING );
		$order    = OPENBB_Order_Editor::apply( $this->start_order( $cycle_id ), array(), $this->actor );
		$this->assertInstanceOf( 'OPENBB_Order', $order );

		$result = OPENBB_Order_Editor::apply( $order, array( 'expected_updated_at' => '2000-01-01 00:00:00' ), $this->actor );
		$this->assertWPError( $result );
		$this->assertSame( 409, $result->get_error_data()['status'] );
	}

	public function test_order_editor_refuses_a_second_order_per_member(): void {
		$cycle_id = $this->make_cycle( OPENBB_Cycle::STATUS_ORDERING );
		$member   = $this->make_user( 'member' );
		$cycle    = OPENBB_Cycles::get( $cycle_id );

		$order = OPENBB_Order_Editor::apply( OPENBB_Order_Editor::start( $cycle, $member ), array(), $this->actor );
		$this->assertInstanceOf( 'OPENBB_Order', $order );

		$again = OPENBB_Order_Editor::start( $cycle, $member );
		$this->assertWPError( $again );
		$this->assertSame( 'openbb_order_exists', $again->get_error_code() );
		$this->assertSame( $order->id, $again->get_error_data()['order_id'] );
	}

	public function test_cycle_validation(): void {
		$cycle = new OPENBB_Cycle( array( 'status' => OPENBB_Cycle::STATUS_SETUP ) );
		$this->assertSame( 'openbb_invalid_cycle', $cycle->validate()->get_error_code() );

		$cycle->name      = 'Spring';
		$cycle->opens_at  = '2030-04-10 00:00:00';
		$cycle->closes_at = '2030-04-01 00:00:00';
		$this->assertSame( 'openbb_invalid_cycle', $cycle->validate()->get_error_code() );

		$cycle->closes_at = '2030-04-20 00:00:00';
		$this->assertTrue( $cycle->validate() );

		$cycle->opens_at = null;
		$cycle->status   = OPENBB_Cycle::STATUS_ORDERING;
		$this->assertSame( 'openbb_invalid_cycle', $cycle->validate()->get_error_code() );
	}

	public function test_store_rejects_overlapping_windows_and_announces_status_changes(): void {
		$fired = did_action( 'openbb_cycle_status_changed' );
		$first = new OPENBB_Cycle(
			array(
				'name'      => 'Spring',
				'status'    => OPENBB_Cycle::STATUS_SETUP,
				'opens_at'  => '2030-04-01 00:00:00',
				'closes_at' => '2030-04-20 00:00:00',
			)
		);
		$this->assertTrue( OPENBB_Cycles::store( $first, '', $this->actor ) );
		$this->assertSame( $fired + 1, did_action( 'openbb_cycle_status_changed' ) );

		$second = new OPENBB_Cycle(
			array(
				'name'      => 'Overlap',
				'status'    => OPENBB_Cycle::STATUS_SETUP,
				'opens_at'  => '2030-04-15 00:00:00',
				'closes_at' => '2030-04-30 00:00:00',
			)
		);
		$result = OPENBB_Cycles::store( $second, '', $this->actor );
		$this->assertWPError( $result );
		$this->assertSame( 'openbb_cycle_overlap', $result->get_error_code() );
		$this->assertSame( 0, $second->id );

		// Saving without a status change does not fire the hook again.
		$this->assertTrue( OPENBB_Cycles::store( $first, OPENBB_Cycle::STATUS_SETUP, $this->actor ) );
		$this->assertSame( $fired + 1, did_action( 'openbb_cycle_status_changed' ) );
	}

	public function test_products_with_history_cannot_be_deleted(): void {
		$stocked = $this->make_product( 1 );
		$result  = $stocked->delete();
		$this->assertWPError( $result );
		$this->assertSame( 'openbb_product_has_history', $result->get_error_code() );
		$this->assertNotNull( get_post( $stocked->id ) );

		$unused = $this->make_product();
		$this->assertTrue( $unused->delete() );
		$this->assertNull( get_post( $unused->id ) );
	}
}
