<?php

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class WC_GZD_Tests_Order_Vouchers extends WC_GZD_Unit_Test_Case {

	function test_order_voucher() {
		$order = WC_GZD_Helper_Order::create_billing_order( true );

		$coupon_obj = new WC_Coupon();
		$coupon_obj->set_code( 'xyz' );
		$coupon_obj->set_amount( 50 );
		$coupon_obj->update_meta_data( 'is_voucher', 'yes' );
		$coupon_obj->save();

		$result = $order->apply_coupon( $coupon_obj );
		$order->calculate_totals();
		$order->save();

		$coupon_helper = WC_GZD_Coupon_Helper::instance();
		$coupon        = array_values( $order->get_items( 'coupon' ) )[0];
		$fee           = $coupon_helper->get_order_item_fee_by_coupon( $coupon, $order );

		$this->assertEquals( true, $result );
		$this->assertEquals( true, $coupon_helper->order_supports_fee_vouchers( $order ) );
		$this->assertEquals( true, $coupon_helper->fee_is_voucher( $fee ) );
		$this->assertEquals( true, $coupon_helper->order_has_voucher( $order ) );

		$coupon = $coupon_helper->get_order_item_coupon_by_fee( $fee, $order );

		$this->assertEquals( true, ( is_a( $coupon, 'WC_Order_Item_Coupon' ) ? true : false ) );
		$this->assertEquals( 'xyz', $coupon->get_code() );
		$this->assertEquals( 'yes', $coupon->get_meta( 'is_stored_as_fee' ) );

		$this->assertEquals( '10.00', wc_format_decimal( $order->get_total(), '' ) );
		$this->assertEquals( '7.98', wc_format_decimal( $order->get_total_tax(), '' ) );
		$this->assertEquals( '-40.00', wc_format_decimal( $order->get_total_fees(), '' ) );
		$this->assertEquals( '0.00', wc_format_decimal( $order->get_total_discount(), '' ) );
		$this->assertEquals( false, $coupon_helper->voucher_includes_shipping_costs( $coupon ) );
	}

	function test_order_voucher_with_shipping() {
		$order = WC_GZD_Helper_Order::create_billing_order( true );

		$coupon_obj = new WC_Coupon();
		$coupon_obj->set_code( 'xyz' );
		$coupon_obj->set_amount( 50 );
		$coupon_obj->set_free_shipping( true );
		$coupon_obj->update_meta_data( 'is_voucher', 'yes' );
		$coupon_obj->save();

		$result = $order->apply_coupon( $coupon_obj );
		$order->calculate_totals();
		$order->save();

		$coupon_helper = WC_GZD_Coupon_Helper::instance();
		$coupon        = array_values( $order->get_items( 'coupon' ) )[0];
		$fee           = $coupon_helper->get_order_item_fee_by_coupon( $coupon, $order );

		$this->assertEquals( true, $result );
		$this->assertEquals( true, $coupon_helper->order_supports_fee_vouchers( $order ) );
		$this->assertEquals( true, $coupon_helper->fee_is_voucher( $fee ) );
		$this->assertEquals( true, $coupon_helper->order_has_voucher( $order ) );

		$coupon = $coupon_helper->get_order_item_coupon_by_fee( $fee, $order );

		$this->assertEquals( true, ( is_a( $coupon, 'WC_Order_Item_Coupon' ) ? true : false ) );
		$this->assertEquals( 'xyz', $coupon->get_code() );
		$this->assertEquals( 'yes', $coupon->get_meta( 'is_stored_as_fee' ) );

		$this->assertEquals( '0.00', wc_format_decimal( $order->get_total(), '' ) );
		$this->assertEquals( '7.98', wc_format_decimal( $order->get_total_tax(), '' ) );
		$this->assertEquals( '-50.00', wc_format_decimal( $order->get_total_fees(), '' ) );
		$this->assertEquals( '0.00', wc_format_decimal( $order->get_total_discount(), '' ) );
		$this->assertEquals( true, $coupon_helper->voucher_includes_shipping_costs( $coupon ) );
	}

	function test_net_order_voucher_with_shipping() {
		$order = WC_GZD_Helper_Order::create_billing_order( false );

		$coupon_obj = new WC_Coupon();
		$coupon_obj->set_code( 'xyz' );
		$coupon_obj->set_amount( 50 );
		$coupon_obj->set_free_shipping( true );
		$coupon_obj->update_meta_data( 'is_voucher', 'yes' );
		$coupon_obj->save();

		$result = $order->apply_coupon( $coupon_obj );
		$order->calculate_totals();
		$order->save();

		$coupon_helper = WC_GZD_Coupon_Helper::instance();
		$coupon        = array_values( $order->get_items( 'coupon' ) )[0];
		$fee           = $coupon_helper->get_order_item_fee_by_coupon( $coupon, $order );

		$this->assertEquals( true, $result );
		$this->assertEquals( true, $coupon_helper->order_supports_fee_vouchers( $order ) );
		$this->assertEquals( true, $coupon_helper->fee_is_voucher( $fee ) );
		$this->assertEquals( true, $coupon_helper->order_has_voucher( $order ) );

		$coupon = $coupon_helper->get_order_item_coupon_by_fee( $fee, $order );

		$this->assertEquals( true, ( is_a( $coupon, 'WC_Order_Item_Coupon' ) ? true : false ) );
		$this->assertEquals( 'xyz', $coupon->get_code() );
		$this->assertEquals( 'yes', $coupon->get_meta( 'is_stored_as_fee' ) );

		$this->assertEquals( '0.00', wc_format_decimal( $order->get_total(), '' ) );
		$this->assertEquals( '7.99', wc_format_decimal( $order->get_total_tax(), '' ) );
		$this->assertEquals( '-50.00', wc_format_decimal( $order->get_total_fees(), '' ) );
		$this->assertEquals( '0.00', wc_format_decimal( $order->get_total_discount(), '' ) );
	}
}