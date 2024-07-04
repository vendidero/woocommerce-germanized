<?php
/**
 * Email Shipment items
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/email-shipment-items.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Shipments/Templates/Emails
 * @version 1.0.1
 */
use Vendidero\Germanized\Shipments\ShipmentItem;

defined( 'ABSPATH' ) || exit;

$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

foreach ( $items as $item_id => $item ) :
	$product       = $item->get_product();
	$sku           = $item->get_sku();
	$purchase_note = '';
	$image         = '';

	/**
	 * Filter to decide whether a specific ShipmentItem is visible within email table or not.
	 *
	 * @param boolean                                      $is_visible Whether the ShipmentItem is visible or not.
	 * @param ShipmentItem $item The ShipmentItem object.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	if ( ! apply_filters( 'woocommerce_gzd_shipment_item_visible', true, $item ) ) {
		continue;
	}

	if ( is_object( $product ) ) {
		$image = $product->get_image( $image_size );
	}

	?>
	<tr class="shipment_item">
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
			<?php

			// Show title/image etc.
			if ( $show_image ) {
				/**
				 * Filter to adjust the ShipmentItem product image in emails.
				 *
				 * @param string                                       $image The image.
				 * @param ShipmentItem $item The ShipmentItem object.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				echo wp_kses_post( apply_filters( 'woocommerce_gzd_shipment_item_thumbnail', $image, $item ) );
			}

			/**
			 * Filter to adjust the ShipmentItem name.
			 *
			 * @param string                                       $name The ShipmentItem name.
			 * @param ShipmentItem $item The ShipmentItem object.
			 * @param boolean                                      $is_plain Whether the email is sent in plain format or not.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			echo wp_kses_post( apply_filters( 'woocommerce_gzd_shipment_item_name', $item->get_name(), $item, false ) );

			// SKU.
			if ( $show_sku && $sku ) {
				echo wp_kses_post( ' (#' . $sku . ')' );
			}

			/*
			 * Action that fires while outputting meta data for a ShipmentItem table display in an Email.
			 *
			 * @param integer                                      $item_id The shipment item id.
			 * @param \Vendidero\Germanized\Shipments\ShipmentItem $item The shipment item instance.
			 * @param \Vendidero\Germanized\Shipments\Shipment     $shipment The shipment instance.
			 * @param boolean                                      $plain_text Whether this email is in plaintext format or not.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_shipment_item_meta', $item_id, $item, $shipment, $plain_text );

			?>
		</td>
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
			<?php
			/**
			 * Filter to adjust the ShipmentItem quantity in emails.
			 *
			 * @param string                                       $quantity The ShipmentItem quantity.
			 * @param ShipmentItem $item The ShipmentItem object.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			echo wp_kses_post( apply_filters( 'woocommerce_gzd_email_shipment_item_quantity', $item->get_quantity(), $item ) );
			?>
		</td>
	</tr>

<?php endforeach; ?>
