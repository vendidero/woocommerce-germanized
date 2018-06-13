<?php
/**
 * Checkout terms and conditions checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Make sure only template calls from germanized are accepted
if ( ! isset( $gzd_checkbox ) || ! $gzd_checkbox )
	return;

do_action( "woocommerce_gzd_before_legal_checkbox_{$checkbox->get_id()}", $checkbox );
?>

<?php if ( apply_filters( 'woocommerce_germanized_checkout_show_terms', true ) ) : ?>

    <?php
    /**
     * Terms and conditions hook used to inject content.
     *
     * @since 3.4.0.
     * @hooked wc_privacy_policy_text() Shows custom privacy policy text. Priority 20.
     * @hooked wc_terms_and_conditions_page_content() Shows t&c page content. Priority 30.
     */
    do_action( 'woocommerce_checkout_terms_and_conditions' );
    ?>

    <p class="<?php $checkbox->render_classes( $checkbox->get_html_wrapper_classes() ); ?>" data-checkbox="<?php echo esc_attr( $checkbox->get_id() ); ?>">
        <label for="<?php echo esc_attr( $checkbox->get_html_id() ); ?>" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <?php if ( ! $checkbox->hide_input() ) : ?>
                <input type="checkbox" class="<?php $checkbox->render_classes( $checkbox->get_html_classes() ); ?>" name="<?php echo esc_attr( $checkbox->get_html_name() ); ?>" id="<?php echo esc_attr( $checkbox->get_html_id() ); ?>" />
            <?php endif; ?>
            <span class="woocommerce-gzd-<?php echo esc_attr( $checkbox->get_html_id() ); ?>-checkbox-text"><?php echo $checkbox->get_label(); ?></span>
        </label>
    </p>

<?php endif; ?>