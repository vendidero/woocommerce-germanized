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

    <p class="form-row legal terms wc-terms-and-conditions validate-required">
        <label for="legal" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <?php if ( get_option( 'woocommerce_gzd_display_checkout_legal_no_checkbox' ) === 'no' ) : ?>
                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="legal" id="legal" />
            <?php endif; ?>
            <span class="woocommerce-gzd-terms-and-conditions-checkbox-text"><?php echo wc_gzd_get_legal_text(); ?></span>
        </label>
    </p>

<?php endif; ?>