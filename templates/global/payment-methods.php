<?php
/**
 * The Template for displaying information about available payment methods..
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/global/payment-methods.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.1.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php if ( $gateways = WC()->payment_gateways()->payment_gateways() ) : ?>
	<ul class="payment_methods methods">
		<?php
		foreach ( $gateways as $gateway ) :
			if ( 'yes' !== $gateway->enabled ) {
				continue;
			}
			?>
			<li class="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
				<label><?php echo esc_html( $gateway->get_title() ); ?><?php echo wp_kses_post( $gateway->get_icon() ); ?></label>
				<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
					<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>">
						<p><?php echo wp_kses_post( $gateway->get_description() ); ?></p>
					</div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
