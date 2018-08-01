<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class 		WC_GZD_WPML_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Compatibility_Woocommerce_Role_Based_Price extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public function __construct() {
		parent::__construct(
			'WooCommerce Role Based Price',
			'woocommerce-role-based-price/woocommerce-role-based-price.php'
		);
	}
}