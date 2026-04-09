<?php

namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

class OrderWithdrawalButton {

	public static function init() {
		add_filter(
			'eu_owb_woocommerce_product_type_options',
			function ( $product_type_options ) {
				$product_type_options['service']        = __( 'Service', 'woocommerce-germanized' );
				$product_type_options['is_food']        = __( 'Food', 'woocommerce-germanized' );
				$product_type_options['defective_copy'] = __( 'Defective Copy', 'woocommerce-germanized' );
				$product_type_options['used_good']      = __( 'Used Good', 'woocommerce-germanized' );

				return $product_type_options;
			}
		);

		add_filter(
			'eu_owb_woocommerce_template_path',
			function ( $path ) {
				return WC_germanized()->template_path();
			}
		);

		add_filter( 'eu_owb_woocommerce_product_matches_type', array( __CLASS__, 'matches_product_matches_type' ), 10, 3 );
	}

	public static function matches_product_matches_type( $matches_type, $product, $types ) {
		$intersect = array_intersect(
			(array) $types,
			array(
				'service',
				'is_food',
				'defective_copy',
				'used_good',
			)
		);

		if ( ! empty( $intersect ) ) {
			$matches_type = wc_gzd_product_matches_extended_type( $types, $product );
		}

		return $matches_type;
	}
}
