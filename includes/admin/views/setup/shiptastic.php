<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$provider_available = array_merge(
	array(
		'dhl'           => array(
			'title'               => _x( 'DHL', 'shipments', 'woocommerce-germanized' ),
			'countries_supported' => array( 'DE' ),
			'is_builtin'          => false,
			'is_pro'              => false,
			'extension_name'      => 'shiptastic-integration-for-dhl',
		),
		'deutsche_post' => array(
			'title'               => _x( 'Deutsche Post', 'shipments', 'woocommerce-germanized' ),
			'countries_supported' => array( 'DE' ),
			'is_builtin'          => false,
			'is_pro'              => false,
			'extension_name'      => 'shiptastic-integration-for-dhl',
		),
		'ups'           => array(
			'title'          => _x( 'UPS', 'shipments', 'woocommerce-germanized' ),
			'is_builtin'     => false,
			'is_pro'         => false,
			'extension_name' => 'shiptastic-integration-for-ups',
		),
	),
	\Vendidero\Germanized\Shiptastic::get_shipping_provider_integrations_for_pro()
);

$base_country  = wc_gzd_get_base_country();
$provider_list = array();

foreach ( $provider_available as $provider ) {
	if ( ! empty( $provider_available['countries_supported'] ) && ! in_array( $base_country, $provider_available['countries_supported'], true ) ) {
		continue;
	}

	$provider_list[] = $provider['title'];
}

$provider_name_list = implode( ', ', $provider_list );
?>
<h1><?php esc_html_e( 'Shiptastic', 'woocommerce-germanized' ); ?></h1>

<p class="headliner"><?php esc_html_e( 'Shiptastic is your all-in-one shipping solution for WooCommerce - seamlessly integrated with Germanized.', 'woocommerce-germanized' ); ?></p>

<ul class="features">
	<li>✓ <?php echo esc_html_x( 'Create (partial) shipments for orders – either automatically or by hand.', 'shipments', 'woocommerce-germanized' ); ?></li>
	<li>✓ <?php printf( esc_html_x( 'Use one of our built-in integrations for %s.', 'shipments', 'woocommerce-germanized' ), esc_html( $provider_name_list ) ); ?></li>
	<li>✓ <?php echo esc_html_x( 'Create complex shipping scenarios.', 'shipments', 'woocommerce-germanized' ); ?></li>
</ul>
