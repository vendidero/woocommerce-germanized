<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$manager = WC_GZD_Legal_Checkbox_Manager::instance();

/**
 * Update checkbox label to use the placeholder instead of law reference.
 */
if ( $checkbox = $manager->get_checkbox( 'review_reminder' ) ) {
	$checkbox->update_option( 'admin_desc', __( 'Obtain customers\' consent to receive a review invitation.', 'woocommerce-germanized' ) );
}
