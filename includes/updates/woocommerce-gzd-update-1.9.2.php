<?php

// Get all variable products
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$direct_debit_settings = get_option( 'woocommerce_direct-debit_settings', array() );

if ( ! empty( $direct_debit_settings ) && ! empty( $direct_debit_settings['mandate_text'] ) ) {
	$direct_debit_settings['mandate_text'] = str_replace(
		array(
			'eine einmalige Zahlung',
			'a single payment',
		),
		'[mandate_type_text]',
		$direct_debit_settings['mandate_text']
	);
	update_option( 'woocommerce_direct-debit_settings', $direct_debit_settings );
}


