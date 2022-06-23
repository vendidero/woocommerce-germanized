<?php
/**
 * The Template for displaying the terms and conditions checkbox.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/terms.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.9.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Make sure only template calls from germanized are accepted
if ( ! isset( $gzd_checkbox ) || ! $gzd_checkbox ) {
	return;
}

$checkbox_id = $checkbox->get_id();

/**
 * Before render checkbox template.
 *
 * Fires before a checkbox with `$checkbox_id` is rendered.
 *
 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
 *
 * @since 2.0.0
 *
 */
do_action( "woocommerce_gzd_before_legal_checkbox_{$checkbox_id}", $checkbox );
?>

<?php
/**
 * Filter that allows hiding the terms checkbox in checkout.
 *
 * @param bool $hide Whether to hide the terms checkbox.
 *
 * @since 2.0.0
 */
if ( apply_filters( 'woocommerce_germanized_checkout_show_terms', true ) ) :
	?>
	<?php do_action( 'woocommerce_checkout_before_terms_and_conditions' ); ?>

	<p class="<?php echo esc_attr( wc_gzd_get_html_classes( $checkbox->get_html_wrapper_classes() ) ); ?>" data-checkbox="<?php echo esc_attr( $checkbox->get_id() ); ?>" style="<?php echo esc_attr( $checkbox->get_html_style() ); ?>">
		<?php do_action( 'woocommerce_checkout_terms_and_conditions' ); ?>

		<label for="<?php echo esc_attr( $checkbox->get_html_id() ); ?>" class="woocommerce-form__label <?php echo ( ! $checkbox->hide_input() ? 'woocommerce-form__label-for-checkbox checkbox' : '' ); ?>">
			<?php if ( ! $checkbox->hide_input() ) : ?>
				<input
					type="checkbox"
					class="<?php echo esc_attr( wc_gzd_get_html_classes( $checkbox->get_html_classes() ) ); ?>"
					name="<?php echo esc_attr( $checkbox->get_html_name() ); ?>"
					id="<?php echo esc_attr( $checkbox->get_html_id() ); ?>"
				/>
			<?php endif; ?>
			<span class="woocommerce-gzd-<?php echo esc_attr( $checkbox->get_html_id() ); ?>-checkbox-text"><?php echo wp_kses_post( $checkbox->get_label() ); ?></span>
			<?php if ( $checkbox->is_mandatory() ) : ?>
				&nbsp;<abbr class="required" title="<?php echo esc_attr__( 'required', 'woocommerce-germanized' ); ?>">*</abbr>
			<?php endif; ?>
		</label>

		<input type="hidden" name="<?php echo esc_attr( $checkbox->get_html_name() ); ?>-field" value="1" />
	</p>

	<?php do_action( 'woocommerce_checkout_after_terms_and_conditions' ); ?>
<?php endif; ?>
