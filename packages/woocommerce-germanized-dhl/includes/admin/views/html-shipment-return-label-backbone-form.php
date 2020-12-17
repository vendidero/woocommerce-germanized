<?php
/**
 * Shipment label HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\DHL\Package;

$default_args = wc_gzd_dhl_get_return_label_default_args( $dhl_order, $shipment );
?>

<form action="" method="post" class="wc-gzd-create-shipment-label-form">

	<?php woocommerce_wp_select( array(
		'id'          		=> 'dhl_label_receiver_slug',
		'label'       		=> _x( 'Receiver', 'dhl', 'woocommerce-germanized' ),
		'description'		=> '',
		'options'			=> wc_gzd_dhl_get_return_receivers(),
		'value'             => isset( $default_args['receiver_slug'] ) ? $default_args['receiver_slug'] : '',
	) ); ?>

</form>
