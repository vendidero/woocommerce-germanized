<?php
/**
 * Shipment Address Details
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/shipment/shipment-details-address.php.
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
use Vendidero\Shiptastic\Shipment;

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
 * @package Vendidero/Shiptastic
 */
do_action( 'woocommerce_shiptastic_shipment_details_after_address_details', $shipment ); ?>
