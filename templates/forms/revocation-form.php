<?php
/**
 * Single Product Price per Unit
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$fields = WC_GZD_Revocation::get_fields();

?>

<form name="revocation" method="post" id="woocommerce-gzd-revocation">
	<p class="form-row" id="to_field">
		<label for="to" class=""><?php echo _x( 'To', 'revocation-form', 'woocommerce-germanized' );?></label>
		<span class="description"><?php echo nl2br( get_option( 'woocommerce_gzd_revocation_address' ) );?></span>
	</p>
	<?php if ( !empty( $fields ) ) : ?>
		<?php foreach ( $fields as $name => $field ) : ?>
			<?php echo ($name == 'sep') ? '<h3>' . $field . '</h3>' : woocommerce_form_field( $name, $field ); ?>
		<?php endforeach;?>
	<?php endif;?>

    <?php do_action( 'woocommerce_gzd_after_revocation_form_fields' ); ?>

	<div class="form-row submit-revocation checkout-btn-wrap">
		<?php wp_nonce_field( 'woocommerce-revocation' ); ?>
		<input type="submit" class="button alt" name="woocommerce_gzd_revocation_submit" id="submit_revocation" value="<?php echo _x( 'Forward Revocation', 'revocation-form', 'woocommerce-germanized' );?>" data-value="<?php echo _x( 'Forward Revocation', 'revocation-form', 'woocommerce-germanized' );?>"/>
	</div>

</form>