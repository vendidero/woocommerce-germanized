<?php
/**
 * Admin View: Settings section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$section_key      = sanitize_title( $current_section );
/**
 * Filter to adjust the div class for `$current_section` within the Germanized
 * admin settings screen.
 *
 * @since 1.0.0
 *
 * @param string $classname The class name.
 */
$settings_wrapper = apply_filters( 'woocommerce_gzd_settings_wrapper_' . $current_section, '' );
?>

<div class="wc-gzd-admin-settings wc-gzd-admin-settings-<?php echo $section_key; ?> <?php echo esc_attr( $settings_wrapper ); ?>">
	<?php
    /**
     * Before admin section output.
     *
     * Executes right before setting output for a specific admin setting section.
     * `$section_key` equals the current section e.g. display or email.
     *
     * @since 1.0.0
     */
    do_action( 'wc_germanized_settings_section_before_' . $section_key );
    ?>

    <?php
    /** This filter is documented in includes/admin/settings/class-wc-gzd-settings-germanized.php */
    if ( apply_filters( 'wc_germanized_show_settings_' . $section_key, true ) ) : ?>
		<?php WC_Admin_Settings::output_fields( $settings ); ?>
	<?php endif; ?>

    <?php
    /**
     * After admin section output.
     *
     * Executes right after setting output for a specific admin setting section.
     * `$section_key` equals the current section e.g. display or email.
     *
     * @since 1.0.0
     */
    do_action( 'wc_germanized_settings_section_after_' . $section_key );
    ?>
</div>

<?php echo $sidebar; ?>