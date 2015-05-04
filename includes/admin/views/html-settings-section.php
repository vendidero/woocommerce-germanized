<?php
/**
 * Admin View: Settings section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="wc-gzd-admin-settings wc-gzd-admin-settings-<?php echo sanitize_title( $current_section ); ?> <?php echo apply_filters( 'woocommerce_gzd_settings_wrapper_' . $current_section, '' ); ?>">
	<?php do_action( 'wc_germanized_settings_section_before_' . sanitize_title( $current_section ) ); ?>
	<?php if ( apply_filters( 'wc_germanized_show_settings_' . sanitize_title( $current_section ), true ) ) : ?>
		<?php WC_Admin_Settings::output_fields( $settings ); ?>
	<?php endif; ?>
	<?php do_action( 'wc_germanized_settings_section_after_' . sanitize_title( $current_section ) ); ?>
</div>

<?php echo $sidebar; ?>