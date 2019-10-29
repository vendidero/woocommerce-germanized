<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$base_country = in_array( WC()->countries->get_base_country(), array(
	'DE',
	'AT'
) ) ? WC()->countries->get_base_country() : 'DE';
$countries    = WC()->countries->get_countries();
$country_name = isset( $countries[ $base_country ] ) ? $countries[ $base_country ] : __( 'Germany', 'woocommerce-germanized' );
?>
<h1><?php _e( 'Germanize WooCommerce', 'woocommerce-germanized' ); ?></h1>

<p class="headliner"><?php printf( __( 'Let Germanized help you to adjust your WooCommerce settings for %s.', 'woocommerce-germanized' ), $country_name ); ?></p>

<div class="wc-gzd-admin-settings">
	<?php WC_Admin_Settings::output_fields( $settings ); ?>
</div>