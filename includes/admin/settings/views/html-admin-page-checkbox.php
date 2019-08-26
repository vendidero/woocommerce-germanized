<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkbox_id = $checkbox->get_id()
?>

<div class="wc-gzd-admin-settings wc-gzd-admin-settings-checkboxes">
	<?php

    /**
     * Before output admin checkbox settings.
     *
     * Fires before a checkbox admin settings screen is rendered.
     *
     * @since 2.0.0
     *
     * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
     */
    do_action( "woocommerce_gzd_settings_section_before_checkbox_options_{$checkbox_id}", $checkbox );
    ?>

	<?php $checkbox->admin_options(); ?>

	<?php

    /**
     * After output admin checkbox settings.
     *
     * Fires after a checkbox admin settings screen is rendered.
     *
     * @since 2.0.0
     *
     * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
     */
    do_action( "woocommerce_gzd_settings_section_after_checkbox_options_{$checkbox_id}", $checkbox );
    ?>
</div>

<?php if ( ! WC_germanized()->is_pro() ) : ?>
    <?php include_once( 'html-admin-page-checkbox-sidebar.php' ); ?>
<?php endif; ?>