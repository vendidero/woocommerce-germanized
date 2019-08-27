<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

update_option( 'woocommerce_gzd_checkout_phone_non_required', ( get_option( 'woocommerce_gzd_checkout_phone_required' ) === 'no' ? 'yes' : 'no' ) );
?>