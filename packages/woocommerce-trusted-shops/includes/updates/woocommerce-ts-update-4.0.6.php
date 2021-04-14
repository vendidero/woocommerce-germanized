<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TS Option updates
$status = get_option( 'woocommerce_trusted_shops_review_reminder_status' );

if ( ! empty( $status ) && ! is_array( $status ) ) {
	$status = array( $status );
	update_option( 'woocommerce_trusted_shops_review_reminder_status', $status );
}
?>
