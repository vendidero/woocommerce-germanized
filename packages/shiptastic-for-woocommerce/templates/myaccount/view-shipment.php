<?php
/**
 * The Template for displaying details for a shipment on the myaccount page.
 *
 * Shows the details of a particular shipment on the account page.
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/myaccount/view-shipment.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/Shiptastic/Templates
 * @version 4.3.0
 */
defined( 'ABSPATH' ) || exit;
?>
<p>
	<?php
	printf(
	/* translators: 1: order number 2: order date 3: order status */
		esc_html_x( 'Shipment #%1$s was created on %2$s and is currently %3$s.', 'shipments', 'woocommerce-germanized' ),
		'<mark class="shipment-number">' . $shipment->get_shipment_number() . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<mark class="shipment-date">' . wc_format_datetime( $shipment->get_date_created() ) . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<mark class="shipment-status">' . wc_stc_get_shipment_status_name( $shipment->get_status() ) . '</mark>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	?>
</p>

<?php
/**
 * This action is executed after printing the shipment details
 * on the customer account page.
 *
 * @param int $shipment_id The shipment id.
 *
 * @package Vendidero/Shiptastic
 */
do_action( 'woocommerce_shiptastic_view_shipment', $shipment_id ); ?>
