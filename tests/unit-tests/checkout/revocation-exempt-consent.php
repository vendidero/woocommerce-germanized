<?php

/**
 * Consent to the early performance of the contract for services and digital items (loss of the right of withdrawal).
 */
class WC_GZD_Tests_Checkout_Revocation_Exempt_Consent extends WC_GZD_Unit_Test_Case {

	protected $checked = array();

	protected $checked_filter = null;

	public function setUp(): void {
		parent::setUp();

		WC_GZD_Legal_Checkbox_Manager::instance()->do_register_action();

		$this->checked        = array();
		$this->checked_filter = function ( $is_checked, $checkbox_id ) {
			return in_array( $checkbox_id, $this->checked, true );
		};

		add_filter( 'woocommerce_gzd_checkout_checkbox_is_checked', $this->checked_filter, 999, 2 );
	}

	public function tearDown(): void {
		remove_filter( 'woocommerce_gzd_checkout_checkbox_is_checked', $this->checked_filter, 999 );

		$service = wc_gzd_get_legal_checkbox( 'service' );
		$service->set_is_mandatory( true );
		$service->set_is_shown( false );

		parent::tearDown();
	}

	protected function create_service_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_service', 'yes' );
		$product->save();

		$order = new WC_Order();
		$order->add_product( wc_get_product( $product->get_id() ), 1 );
		$order->save();

		return $order;
	}

	public function test_order_has_service_items() {
		$order = $this->create_service_order();

		$this->assertTrue( wc_gzd_order_has_revocation_exempt_items( $order, 'service' ) );
		$this->assertFalse( wc_gzd_order_has_revocation_exempt_items( WC_Helper_Order::create_order(), 'service' ) );
	}

	public function test_optional_service_checkbox_not_checked() {
		wc_gzd_get_legal_checkbox( 'service' )->set_is_mandatory( false );
		wc_gzd_get_legal_checkbox( 'service' )->set_is_shown( true );

		$order = $this->create_service_order();
		WC_GZD_Checkout::instance()->order_store_checkbox_data( $order );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$this->assertSame( 'no', $order->get_meta( '_service_opted_in' ) );
		$this->assertTrue( WC_GZD_Legal_Checkbox_Manager::instance()->is_logged( 'service', $order ) );
		$this->assertFalse( wc_gzd_order_has_revocation_exempt_consent( $order, 'service' ) );
	}

	public function test_service_checkbox_checked() {
		$this->checked = array( 'service' );

		$order = $this->create_service_order();
		WC_GZD_Checkout::instance()->order_store_checkbox_data( $order );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$this->assertSame( 'yes', $order->get_meta( '_service_opted_in' ) );
		$this->assertTrue( wc_gzd_order_has_revocation_exempt_consent( $order, 'service' ) );
	}

	public function test_no_logging_without_service_items() {
		$this->checked = array( 'service' );

		$order = WC_Helper_Order::create_order();
		WC_GZD_Checkout::instance()->order_store_checkbox_data( $order );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$this->assertSame( '', $order->get_meta( '_service_opted_in' ) );
	}

	/**
	 * Orders placed before the consent was logged.
	 */
	public function test_legacy_order_fallback() {
		$order   = $this->create_service_order();
		$service = wc_gzd_get_legal_checkbox( 'service' );

		$service->set_is_shown( true );
		$this->assertTrue( wc_gzd_order_has_revocation_exempt_consent( $order, 'service' ) );

		$service->set_is_mandatory( false );
		$this->assertFalse( wc_gzd_order_has_revocation_exempt_consent( $order, 'service' ) );

		$service->set_is_mandatory( true );
		$service->set_is_shown( false );
		$this->assertFalse( wc_gzd_order_has_revocation_exempt_consent( $order, 'service' ) );
	}
}
