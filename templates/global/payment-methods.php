<?php
/**
 * Single Product Shipping Time Info
 *
 * @author   Vendidero
 * @package  WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<?php if ( $available_gateways = WC()->payment_gateways->get_available_payment_gateways() ) : ?>

	<ul class="payment_methods methods">

		<?php foreach ( $available_gateways as $gateway ) : ?>

			<li class="payment_method_<?php echo $gateway->id; ?>">
				<label for="payment_method_<?php echo $gateway->id; ?>"><?php echo $gateway->get_title(); ?> <?php echo $gateway->get_icon(); ?></label>
				<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
					<div class="payment_box payment_method_<?php echo $gateway->id; ?>">
						<p><?php echo $gateway->get_description(); ?></p>
					</div>
				<?php endif;?>
			</li>

		<?php endforeach; ?>

	</ul>

<?php endif; ?>
