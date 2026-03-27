<?php
/**
 * Customer withdrawal items
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-withdrawal-items.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

$margin_side = is_rtl() ? 'left' : 'right';

$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();
$price_text_align           = $email_improvements_enabled ? 'right' : 'left';

foreach ( $items as $item_id => $item_data ) :
	$item     = $item_data['item'];
	$quantity = $item_data['quantity'];
	$product  = $item->get_product();
	$sku      = '';

	if ( is_object( $product ) ) {
		$sku = $product->get_sku();
	}

	if ( $email_improvements_enabled ) {
		/**
		 * Email Order Item Name hook.
		 *
		 * @since 2.1.0
		 * @since 2.4.0 Added $is_visible parameter.
		 * @param string        $product_name Product name.
		 * @param WC_Order_Item $item Order item object.
		 * @param bool          $is_visible Is item visible.
		 */
		$product_name = apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
		/**
		 * Email Order Item Quantity hook.
		 *
		 * @since 2.4.0
		 * @param int           $quantity Item quantity.
		 * @param WC_Order_Item $item     Item object.
		 */
		$product_name .= ' × ' . apply_filters( 'eu_owb_woocommerce_withdrawal_item_quantity', $quantity, $item );
		echo wp_kses_post( str_pad( wp_kses_post( $product_name ), 40 ) );
		echo ' ';
		if ( $show_sku && $sku ) {
			echo esc_html( '(#' . $sku . ")\n" );
		}
	} else {
		/**
		 * Email Order Item Name hook.
		 *
		 * @since 2.1.0
		 * @since 2.4.0 Added $is_visible parameter.
		 * @param string        $product_name Product name.
		 * @param WC_Order_Item $item Order item object.
		 * @param bool          $is_visible Is item visible.
		 */
		echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
		if ( $show_sku && $sku ) {
			echo esc_html( ' (#' . $sku . ')' );
		}
		/**
		 * Email Order Item Quantity hook.
		 *
		 * @since 2.4.0
		 * @param int           $quantity Item quantity.
		 * @param WC_Order_Item $item     Item object.
		 */
		echo ' X ' . wp_kses_post( apply_filters( 'eu_owb_woocommerce_withdrawal_item_quantity', $quantity, $item ) );
	}

	// allow other plugins to add additional product information here.
	do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );
	echo wp_kses_post(
		wp_strip_all_tags(
			wc_display_item_meta(
				$item,
				array(
					'before'    => "\n- ",
					'separator' => "\n- ",
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				)
			)
		)
	);

	// allow other plugins to add additional product information here.
	do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );

	echo "\n\n";
	?>
	<?php
endforeach;
