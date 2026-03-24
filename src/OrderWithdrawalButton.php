<?php

namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

class OrderWithdrawalButton {

	public static function init() {
		add_filter(
			'eu_owb_woocommerce_product_type_options',
			function ( $product_type_options ) {
				$product_type_options['service'] = __( 'Service', 'woocommerce-germanized' );

				return $product_type_options;
			}
		);

		add_filter(
			'eu_owb_woocommerce_template_path',
			function ( $path ) {
				return WC_germanized()->template_path();
			}
		);
	}
}
