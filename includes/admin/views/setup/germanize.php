<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$base_country = in_array(
	wc_gzd_get_base_country(),
	array(
		'DE',
		'AT',
	),
	true
) ? wc_gzd_get_base_country() : 'DE';
$countries    = WC()->countries->get_countries();
$country_name = isset( $countries[ $base_country ] ) ? $countries[ $base_country ] : __( 'Germany', 'woocommerce-germanized' );
?>
<h1><?php esc_html_e( 'Germanize WooCommerce', 'woocommerce-germanized' ); ?></h1>

<p class="headliner"><?php printf( esc_html__( 'Let Germanized prepare your WooCommerce installation for %s.', 'woocommerce-germanized' ), esc_html( $country_name ) ); ?></p>

<div class="wc-gzd-admin-settings">
	<?php WC_Admin_Settings::output_fields( $settings ); ?>
</div>
