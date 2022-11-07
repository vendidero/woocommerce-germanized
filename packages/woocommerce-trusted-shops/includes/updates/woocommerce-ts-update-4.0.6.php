<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TS Option updates
$status_option = get_option( 'woocommerce_trusted_shops_review_reminder_status' );

if ( ! empty( $status_option ) && ! is_array( $status_option ) ) {
	$status_option = array( $status_option );
	update_option( 'woocommerce_trusted_shops_review_reminder_status', $status_option );
}

