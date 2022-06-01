<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$manager = WC_GZD_Legal_Checkbox_Manager::instance();

/**
 * Add terms checkbox to pay for order page
 */
if ( $checkbox = $manager->get_checkbox( 'terms' ) ) {
	$locations = $checkbox->get_locations();

	if ( ! in_array( 'pay_for_order', $locations, true ) ) {
		$locations[] = 'pay_for_order';

		$checkbox->update_option( 'locations', $locations );
	}
}
