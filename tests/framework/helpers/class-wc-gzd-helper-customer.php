<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_GZD_Helper_Customer {

	public static function create_customer() {

		$customer = WC_Helper_Customer::create_customer( 'vendidero', 'test', 'info@vendidero.de' );

		$customer->update_meta_data( 'billing_title', 1 );
		$customer->update_meta_data( 'shipping_title', 1 );
		$customer->update_meta_data( 'direct_debit_holder', 'Holder' );
		$customer->update_meta_data( 'direct_debit_iban', 'DE2424242424' );
		$customer->update_meta_data( 'direct_debit_bic', 'DEU234242' );
		
		$customer->save();

		return $customer;

	}

}