<?php
/**
 * The Template for displaying revocation form.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/forms/revocation-form.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.4
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$fields = WC_GZD_Revocation::get_fields();
?>

<form name="revocation" method="post" id="woocommerce-gzd-revocation">
	<p class="form-row" id="to_field">
		<label for="to" class=""><?php echo esc_html_x( 'To', 'revocation-form', 'woocommerce-germanized' ); ?></label>
		<span class="description"><?php echo wp_kses_post( wc_gzd_get_formatted_revocation_address() ); ?></span>
	</p>
	<?php if ( ! empty( $fields ) ) : ?>
		<?php foreach ( $fields as $name => $field ) : ?>
			<?php echo ( ( 'sep' === $name ) ? '<h3>' . esc_html( $field ) . '</h3>' : woocommerce_form_field( $name, $field ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php
	/**
	 * After revocation form fields.
	 *
	 * Executes after outputting revocation form fields.
	 *
	 * @since 1.8.11
	 */
	do_action( 'woocommerce_gzd_after_revocation_form_fields' );
	?>

	<div class="form-row submit-revocation checkout-btn-wrap">
		<?php wp_nonce_field( 'woocommerce-revocation' ); ?>
		<button class="button alt<?php echo esc_attr( wc_gzd_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_gzd_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="woocommerce_gzd_revocation_submit" id="submit_revocation" value="<?php echo esc_attr( _x( 'Forward Withdrawal', 'revocation-form', 'woocommerce-germanized' ) ); ?>" data-value="<?php echo esc_attr( _x( 'Forward Withdrawal', 'revocation-form', 'woocommerce-germanized' ) ); ?>"><?php echo esc_html_x( 'Forward Withdrawal', 'revocation-form', 'woocommerce-germanized' ); ?></button>
	</div>
</form>
