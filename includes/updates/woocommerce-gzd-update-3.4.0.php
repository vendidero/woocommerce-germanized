<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'yes' === get_option( 'woocommerce_gzd_dhl_label_checkout_validate_street_number_address' ) ) {
	update_option( 'woocommerce_gzd_checkout_validate_street_number', 'eu_only' );
}
