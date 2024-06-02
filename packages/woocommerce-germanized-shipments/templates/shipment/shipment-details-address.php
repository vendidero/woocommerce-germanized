<?php
/**
 * Shipment Address Details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/shipment/shipment-details-address.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 3.0.1
 */
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;
?>
<section class="woocommerce-shipment-address-details">

	<section class="woocommerce-columns--addresses addresses">

		<h2 class="woocommerce-column__title"><?php echo esc_html_x( 'Shipment receiver', 'shipments', 'woocommerce-germanized' ); ?></h2>

		<address>
			<?php echo wp_kses_post( $shipment->get_formatted_address( esc_html_x( 'N/A', 'shipments', 'woocommerce-germanized' ) ) ); ?>

			<?php if ( $shipment->get_phone() ) : ?>
				<p class="woocommerce-customer-details--phone"><?php echo esc_html( $shipment->get_phone() ); ?></p>
			<?php endif; ?>

			<?php if ( $shipment->get_email() ) : ?>
				<p class="woocommerce-customer-details--email"><?php echo esc_html( $shipment->get_email() ); ?></p>
			<?php endif; ?>
		</address>

	</section>

</section>

<?php
/**
 * This action is executed after printing the shipment address details on the customer account page.
 *
 * @param Shipment $shipment The shipment instance.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/Shipments
 */
do_action( 'woocommerce_gzd_shipment_details_after_address_details', $shipment ); ?>
