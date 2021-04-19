<?php
/**
 * Admin View: Settings section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wc-gzd-admin-settings wc-ts-admin-settings <?php echo ( ! empty( $sidebar ) ? 'wc-gzd-admin-settings-has-sidebar' : '' ); ?> wc-gzd-admin-settings-trusted_shops <?php echo ( ! empty( $current_section ) ? 'wc-gzd-admin-settings-trusted-shops-' . $current_section : '' ); ?>">
    <div class="wc-gzd-admin-settings-fields">
	    <?php
	    /**
	     * Before admin settings output.
	     *
	     * Executes right before setting output.
	     *
	     * @since 3.0.0
	     *
	     * @param array[] $settings The settings array.
	     */
	    do_action( 'woocommerce_ts_admin_settings_before', $settings );
	    ?>
        <?php WC_Admin_Settings::output_fields( $settings ); ?>
        <?php
        /**
         * After admin settings output.
         *
         * Executes right after setting output.
         *
         * @since 3.0.0
         *
         * @param array[] $settings The settings array.
         */
        do_action( 'woocommerce_ts_admin_settings_after', $settings );
        ?>
    </div>

    <?php if ( ! empty( $sidebar ) ) : ?>
        <div class="wc-gzd-admin-settings-sidebar">
            <?php echo $sidebar; ?>
        </div>
    <?php endif; ?>
</div>
