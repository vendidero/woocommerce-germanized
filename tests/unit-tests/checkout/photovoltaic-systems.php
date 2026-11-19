<?php

/**
 * Photovoltaic systems VAT exemption (§ 12 Abs. 3 UStG) within cart/checkout.
 */
class WC_GZD_Tests_Checkout_Photovoltaic_Systems extends WC_GZD_Unit_Test_Case {

	protected $product = null;

	protected $default_value_filter = null;

	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_default_country', 'DE' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_gzd_photovoltaic_systems_zero_tax_class', 'zero-rate' );

		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate'          => '19.0000',
				'tax_rate_name'     => 'MwSt.',
				'tax_rate_priority' => '1',
				'tax_rate_shipping' => '1',
				'tax_rate_class'    => '',
			)
		);

		WC_GZD_Legal_Checkbox_Manager::instance()->do_register_action();

		if ( ! WC()->session ) {
			WC()->initialize_session();
		}

		if ( ! WC()->cart ) {
			WC()->initialize_cart();
		}

		WC()->customer->set_billing_country( 'DE' );
		WC()->customer->set_shipping_country( 'DE' );
		WC()->customer->set_billing_postcode( '10115' );
		WC()->customer->set_shipping_postcode( '10115' );
		WC()->customer->set_is_vat_exempt( false );

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( '100' );
		$this->product->set_price( '100' );
		$this->product->update_meta_data( '_photovoltaic_system', 'yes' );
		$this->product->save();

		$this->reset_checkout_data();
	}

	public function tearDown(): void {
		if ( $this->default_value_filter ) {
			remove_filter( 'woocommerce_checkout_get_value', $this->default_value_filter, 10 );
			$this->default_value_filter = null;
		}

		remove_all_filters( 'woocommerce_gzd_is_express_checkout_request' );
		unset( $_POST['post_data'], $_POST['woocommerce-process-checkout-nonce'], $_POST['photovoltaic_systems'], $_POST['photovoltaic_systems-field'] );
		WC()->session->set( 'checkout_checkboxes_checked', array() );
		WC()->session->set( 'gzd_is_checkout_checkout', false );
		$this->reset_checkout_data();

		WC()->cart->empty_cart();
		update_option( 'woocommerce_calc_taxes', 'no' );

		parent::tearDown();
	}

	protected function reset_checkout_data() {
		$cache = new ReflectionProperty( WC_GZD_Checkout::class, 'checkout_data' );
		$cache->setAccessible( true );
		$cache->setValue( WC_GZD_Checkout::instance(), null );
	}

	protected function get_confirmed_form() {
		return array(
			'billing_country'            => 'DE',
			'billing_postcode'           => '10115',
			'photovoltaic_systems'       => '1',
			'photovoltaic_systems-field' => '1',
		);
	}

	/**
	 * E.g. PayPal Payments returning a checkout form stored during an earlier checkout attempt.
	 */
	protected function add_stored_default_values() {
		$this->default_value_filter = function ( $value, $key ) {
			return in_array( $key, array( 'photovoltaic_systems', 'photovoltaic_systems-field' ), true ) ? '1' : $value;
		};

		add_filter( 'woocommerce_checkout_get_value', $this->default_value_filter, 10, 2 );
	}

	protected function get_cart_tax( $tax_class = '' ) {
		$this->product->set_tax_class( $tax_class );
		$this->product->save();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();

		return (float) WC()->cart->get_total_tax();
	}

	public function test_checkbox_ignores_default_values() {
		$this->add_stored_default_values();

		$this->assertSame( '1', WC_GZD_Checkout::instance()->get_checkout_value( 'photovoltaic_systems' ) );
		$this->assertFalse( WC_GZD_Checkout::instance()->checkbox_is_visible( 'photovoltaic_systems' ) );
		$this->assertFalse( WC_GZD_Checkout::instance()->checkbox_is_checked( 'photovoltaic_systems' ) );
		$this->assertSame( 19.0, $this->get_cart_tax() );
	}

	/**
	 * Backwards compatibility: custom code providing checkbox values via woocommerce_gzd_get_checkout_value.
	 */
	public function test_checkbox_checked_via_gzd_checkout_value_filter() {
		$filter = function ( $value, $key ) {
			return in_array( $key, array( 'photovoltaic_systems', 'photovoltaic_systems-field' ), true ) ? '1' : $value;
		};

		add_filter( 'woocommerce_gzd_get_checkout_value', $filter, 10, 2 );

		$is_checked = WC_GZD_Checkout::instance()->checkbox_is_checked( 'photovoltaic_systems' );
		$tax        = $this->get_cart_tax();

		remove_filter( 'woocommerce_gzd_get_checkout_value', $filter, 10 );

		$this->assertTrue( $is_checked );
		$this->assertSame( 0.0, $tax );
	}

	public function test_checkbox_checked_via_checkout_refresh() {
		$_POST['post_data'] = http_build_query( $this->get_confirmed_form() );

		$this->assertTrue( WC_GZD_Checkout::instance()->checkbox_is_checked( 'photovoltaic_systems' ) );
		$this->assertSame( 0.0, $this->get_cart_tax() );
	}

	public function test_checkbox_checked_via_checkout_submit() {
		$_POST['woocommerce-process-checkout-nonce'] = 'nonce';
		$_POST['photovoltaic_systems']               = '1';
		$_POST['photovoltaic_systems-field']         = '1';

		$this->assertTrue( WC_GZD_Checkout::instance()->checkbox_is_checked( 'photovoltaic_systems' ) );
	}

	public function test_checkbox_checked_via_explicit_checkout_data() {
		WC_GZD_Checkout::instance()->set_checkout_data( $this->get_confirmed_form() );

		$this->assertTrue( WC_GZD_Checkout::instance()->checkbox_is_checked( 'photovoltaic_systems' ) );
		$this->assertSame( 0.0, $this->get_cart_tax() );

		$form = $this->get_confirmed_form();
		unset( $form['photovoltaic_systems'] );
		WC_GZD_Checkout::instance()->set_checkout_data( $form );

		$this->assertFalse( WC_GZD_Checkout::instance()->checkbox_is_checked( 'photovoltaic_systems' ) );
		$this->assertSame( 19.0, $this->get_cart_tax() );
	}

	/**
	 * Zero tax classes (e.g. to show zero-taxed prices within the shop) are kept outside checkout/express contexts.
	 */
	public function test_zero_tax_class_kept_outside_exemption_context() {
		$this->assertFalse( WC_GZD_Checkout::instance()->is_photovoltaic_systems_exemption_context() );
		$this->assertSame( 0.0, $this->get_cart_tax( 'zero-rate' ) );
	}

	/**
	 * Express checkouts must not inherit the zero tax class without the customer confirming the exemption.
	 */
	public function test_zero_tax_class_removed_for_express_checkouts() {
		add_filter( 'woocommerce_gzd_is_express_checkout_request', '__return_true' );

		$this->assertTrue( WC_GZD_Checkout::instance()->is_photovoltaic_systems_exemption_context() );
		$this->assertSame( 19.0, $this->get_cart_tax( 'zero-rate' ) );

		// Stored default values do not count as confirmation.
		$this->add_stored_default_values();
		$this->assertSame( 19.0, $this->get_cart_tax( 'zero-rate' ) );

		// Explicitly confirmed (e.g. checkout form sent with the request).
		WC_GZD_Checkout::instance()->set_checkout_data( $this->get_confirmed_form() );
		$this->assertSame( 0.0, $this->get_cart_tax( 'zero-rate' ) );
	}

	/**
	 * Classic checkout refreshes store the confirmation within the session (evaluated by Store API requests,
	 * e.g. the cart requested by PayPal Payments while the customer is on the checkout).
	 */
	public function test_classic_checkout_refresh_stores_state_for_store_api() {
		$_POST['post_data'] = http_build_query( $this->get_confirmed_form() );
		WC_GZD_Checkout::instance()->store_photovoltaic_systems_checkout_state();

		$this->assertContains( 'photovoltaic_systems', (array) WC()->session->get( 'checkout_checkboxes_checked' ) );
		$this->assertTrue( WC()->session->get( 'gzd_is_checkout_checkout' ) );

		// Unchecked within the next refresh.
		$form = $this->get_confirmed_form();
		unset( $form['photovoltaic_systems'] );
		$_POST['post_data'] = http_build_query( $form );
		$this->reset_checkout_data();
		WC_GZD_Checkout::instance()->store_photovoltaic_systems_checkout_state();

		$this->assertNotContains( 'photovoltaic_systems', (array) WC()->session->get( 'checkout_checkboxes_checked' ) );
		$this->assertTrue( WC()->session->get( 'gzd_is_checkout_checkout' ) );
	}

	public function test_leaving_the_checkout_resets_state() {
		WC()->session->set( 'checkout_checkboxes_checked', array( 'terms', 'photovoltaic_systems' ) );
		WC()->session->set( 'gzd_is_checkout_checkout', true );

		// Not on the checkout page (e.g. product or cart page view).
		WC_GZD_Checkout::instance()->maybe_reset_photovoltaic_systems_checkout_state();

		$this->assertSame( array( 'terms' ), WC()->session->get( 'checkout_checkboxes_checked' ) );
		$this->assertFalse( WC()->session->get( 'gzd_is_checkout_checkout' ) );
	}
}
