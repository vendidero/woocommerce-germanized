<?php

class WC_GZD_Tests_Order_Fees extends WC_GZD_Unit_Test_Case {

	function test_non_taxable_fee_does_not_store_split_tax_meta_during_checkout() {
		$order = new WC_Order();
		$fee   = new WC_Order_Item_Fee();

		$fee->set_props(
			array(
				'name'       => 'Payment fee',
				'total'      => 10.00,
				'tax_status' => 'none',
			)
		);

		$order->add_item( $fee );

		$cart_fee = (object) array(
			'split_taxes' => array(
				'' => array(
					'taxable_amount' => 10.00,
					'tax_share'      => 1,
					'tax_rates'      => array( 1 ),
					'net_amount'     => 8.40,
					'includes_tax'   => true,
				),
			),
		);

		WC_GZD_Order_Helper::instance()->set_fee_split_tax_meta( $fee, 'payment-fee', $cart_fee, $order );

		$this->assertEmpty( $fee->get_meta( '_split_taxes', true ) );
	}

	function test_non_taxable_fee_removes_existing_split_tax_meta_on_recalculation() {
		$previous_mode = get_option( 'woocommerce_gzd_tax_mode_additional_costs', 'main_service' );

		try {
			update_option( 'woocommerce_gzd_tax_mode_additional_costs', 'split_tax' );

			$order = WC_GZD_Helper_Order::create_billing_order(
				true,
				array(
					array(
						'type'     => 'line_item',
						'quantity' => 1,
						'subtotal' => 100.00,
						'total'    => 100.00,
					),
					array(
						'type'       => 'fee',
						'name'       => 'Payment fee',
						'total'      => 10.00,
						'tax_status' => 'none',
					),
				)
			);

			$fee = current( $order->get_fees() );

			$fee->update_meta_data(
				'_split_taxes',
				array(
					'' => array(
						'taxable_amount' => 10.00,
						'tax_share'      => 1,
						'tax_rates'      => array( 1 ),
						'net_amount'     => 8.40,
						'includes_tax'   => true,
					),
				)
			);
			$fee->update_meta_data(
				'_tax_shares',
				array(
					'' => array(
						'total' => 10.00,
						'key'   => '1',
						'share' => 1,
					),
				)
			);
			$fee->save();

			$order->calculate_totals();
			$order->save();

			$order = wc_get_order( $order->get_id() );
			$fee   = current( $order->get_fees() );

			$this->assertEquals( 'none', $fee->get_tax_status() );
			$this->assertEquals( '0.00', wc_format_decimal( $fee->get_total_tax(), '' ) );
			$this->assertEmpty( $fee->get_meta( '_split_taxes', true ) );
			$this->assertEmpty( $fee->get_meta( '_tax_shares', true ) );
			$this->assertEmpty( $order->get_meta( '_has_split_tax', true ) );
		} finally {
			update_option( 'woocommerce_gzd_tax_mode_additional_costs', $previous_mode );
		}
	}
}
