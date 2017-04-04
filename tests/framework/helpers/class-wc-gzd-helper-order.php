<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_GZD_Helper_Order {

	public static function create_order() {

		$customer = WC_GZD_Helper_Customer::create_customer();
		$order = WC_Helper_Order::create_order( $customer->get_id(), WC_GZD_Helper_Product::create_simple_product() );
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