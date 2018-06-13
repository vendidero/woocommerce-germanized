<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2>
	<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized&section=checkboxes' ); ?>"><?php _e( 'Checkboxes', 'woocommerce-germanized' ); ?></a> &gt;
	<?php echo esc_html( $checkbox->get_admin_name() ); ?>
</h2>

<div class="wc-gzd-admin-settings wc-gzd-admin-settings-checkboxes">
	<?php do_action( "woocommerce_gzd_settings_section_before_checkbox_options_{$checkbox->get_id()}", $checkbox ); ?>

	<?php $checkbox->admin_options(); ?>

	<?php do_action( "woocommerce_gzd_settings_section_after_checkbox_options_{$checkbox->get_id()}", $checkbox ); ?>
</div>

<?php if ( ! WC_germanized()->is_pro() ) : ?>
    <?php include_once( 'html-admin-page-checkbox-sidebar.php' ); ?>
<?php endif; ?>