<?php
/**
 * The Template for displaying shipments belonging to a certain order.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/myaccount/order-shipments.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 1.0.2
 */
defined( 'ABSPATH' ) || exit;
?>

<?php if ( ! empty( $shipments ) ) : ?>
	<h2 class="woocommerce-shipments-list__title"><?php echo esc_html_x( 'Shipments', 'shipments', 'woocommerce-germanized' ); ?></h2>

	<?php
	wc_get_template(
		'myaccount/shipments.php',
		array(
			'type'      => 'simple',
			'shipments' => $shipments,
			'order'     => $order,
		)
	);
	?>
<?php endif; ?>

<?php if ( wc_gzd_order_is_customer_returnable( $order ) ) : ?>
	<p class="shipments-add-return"><a class="add-return-shipment woocommerce-button button<?php echo esc_attr( wc_gzd_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_gzd_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( wc_gzd_get_order_customer_add_return_url( $order ) ); ?>"><?php echo esc_html_x( 'Add return request', 'shipments', 'woocommerce-germanized' ); ?></a></p>
<?php elseif ( wc_gzd_order_is_customer_returnable( $order, false ) ) : ?>
	<p class="shipments-return-not-available"><?php echo esc_html_x( 'Adding return requests to this order is no longer available. You may contact us for further information.', 'shipments', 'woocommerce-germanized' ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $returns ) ) : ?>
	<h2 class="woocommerce-shipments-list__title woocommerce-return-shipments-list__title"><?php echo esc_html_x( 'Returns', 'shipments', 'woocommerce-germanized' ); ?></h2>

	<?php
	wc_get_template(
		'myaccount/shipments.php',
		array(
			'type'      => 'return',
			'shipments' => $returns,
			'order'     => $order,
		)
	);
	?>
<?php endif; ?>
