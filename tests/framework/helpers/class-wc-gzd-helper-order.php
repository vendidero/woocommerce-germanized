<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_GZD_Helper_Order {

	public static function create_billing_order( $incl_tax = true, $items = array(), $calculate_taxes = true ) {
		update_option( 'woocommerce_calc_taxes', $calculate_taxes ? 'yes' : 'no' );
		update_option( 'woocommerce_prices_include_tax', $incl_tax ? 'yes' : 'no' );
		update_option( 'woocommerce_tax_round_at_subtotal', $incl_tax ? 'yes' : 'no' );

		$tax_rate = array(
			'tax_rate_country'  => 'DE',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_id = \WC_Tax::_insert_tax_rate( $tax_rate );
		$order       = new \WC_Order();

		$order->set_billing_country( 'DE' );
		$order->set_billing_address_1( 'Musterstr. 12' );
		$order->set_billing_city( 'Musterstadt' );
		$order->set_billing_first_name( 'Max' );
		$order->set_billing_last_name( 'Mustermann' );
		$order->set_prices_include_tax( $incl_tax );

		// Add order products - total and subtotal are net prices
		$item = new \WC_Order_Item_Product();

		if ( empty( $items ) ) {
			if ( $incl_tax ) {
				$item->set_props(
					array(
						'quantity' => 4,
						// Woo treats these as net
						'subtotal' => 33.613445,
						// Woo treats these as net
						'total'    => 33.613445,
					)
				);
			} else {
				$item->set_props(
					array(
						'quantity' => 4,
						// Woo treats these as net
						'subtotal' => 33.61,
						// Woo treats these as net
						'total'    => 33.61,
					)
				);
			}

			$item->save();
			$order->add_item( $item );

			$item = new \WC_Order_Item_Shipping();

			if ( $incl_tax ) {
				$item->set_props(
					array(
						'method_title' => 'shipping',
						'total'        => 8.403361,
					)
				);
			} else {
				$item->set_props(
					array(
						'method_title' => 'shipping',
						// Woo treats these as net
						'total'        => wc_format_decimal( 8.40 ),
					)
				);
			}

			$item->save();
			$order->add_item( $item );
		} else {
			foreach( $items as $item_data ) {
				$item_data = wp_parse_args( $item_data, array(
					'type' => 'line_item'
				) );

				switch( $item_data['type'] ) {
					case "line_item":
						// Add order products - total and subtotal are net prices
						$item = new \WC_Order_Item_Product();
						break;
					case "shipping":
						// Add order products - total and subtotal are net prices
						$item = new \WC_Order_Item_Shipping();
						break;
					case "fee":
						// Add order products - total and subtotal are net prices
						$item = new \WC_Order_Item_Fee();
						break;
					case "tax":
						// Add order products - total and subtotal are net prices
						$item = new \WC_Order_Item_Tax();
						break;
					case "coupon":
						// Add order products - total and subtotal are net prices
						$item = new \WC_Order_Item_Coupon();
						break;
					default:
						// Add order products - total and subtotal are net prices
						$item = new \WC_Order_Item_Product();
						break;
				}

				$item->set_props( $item_data );
				$item->save();
				$order->add_item( $item );
			}
		}

		// Save the order to make sure order exists before calculating
		$order->save();

		$order->calculate_totals( $calculate_taxes );
		$order->save();

		return $order;
	}

	public static function create_order() {

		$customer = WC_GZD_Helper_Customer::create_customer();
		$product  = WC_GZD_Helper_Product::create_simple_product();
		$order    = WC_Helper_Order::create_order( $customer->get_id(), $product );

		$order->update_meta_data( '_billing_title', 1 );
		$order->update_meta_data( '_shipping_title', 1 );
		$order->update_meta_data( '_shipping_parcelshop', 1 );
		$order->update_meta_data( '_shipping_parcelshop_post_number', '123456' );
		$order->update_meta_data( '_parcel_delivery_opted_in', 'yes' );
		$order->update_meta_data( '_direct_debit_holder', 'Holder' );
		$order->update_meta_data( '_direct_debit_iban', 'DE2424242424' );
		$order->update_meta_data( '_direct_debit_bic', 'DEU234242' );
		$order->update_meta_data( '_direct_debit_mandate_id', '123456' );

		$order->save();

		return $order;

	}

}