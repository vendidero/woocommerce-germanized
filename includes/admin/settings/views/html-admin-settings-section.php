<?php
/**
 * Admin View: Settings section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$section_key = sanitize_key( $current_section );
?>

<div class="wc-gzd-admin-settings <?php echo( ! empty( $sidebar ) ? 'wc-gzd-admin-settings-has-sidebar' : '' ); ?> wc-gzd-admin-settings-<?php echo esc_attr( $current_tab_name ); ?> <?php echo( ! empty( $current_section ) ? 'wc-gzd-admin-settings-' . esc_attr( $current_tab ) . '-' . esc_attr( $current_section ) : '' ); ?>">
	<div class="wc-gzd-admin-settings-fields">
		<?php
		/**
		 * Before admin tab settings output.
		 *
		 * Executes right before setting output for a specific admin setting tab `$tab_name` e.g. shopmarks.
		 *
		 * @since 3.0.0
		 */
		do_action( 'woocommerce_gzd_admin_settings_before_' . $current_tab_name, $settings );

		if ( ! empty( $current_section ) ) {
			/**
			 * Before admin tab section settings output.
			 *
			 * Executes right before setting output for a specific admin setting tab `$tab_name` e.g. shopmarks.
			 * `$current_section` refers to the current section e.g. product_widgets.
			 *
			 * @param array[] $settings The settings array.
			 *
			 * @since 3.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_admin_settings_before_' . $current_tab_name . '_' . $current_section, $settings );
		}
		?>
		<?php WC_Admin_Settings::output_fields( $settings ); ?>
		<?php
		/**
		 * After admin tab settings output.
		 *
		 * Executes right after setting output for a specific admin setting tab `$tab_name` e.g. shopmarks.
		 *
		 * @param array[] $settings The settings array.
		 *
		 * @since 3.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_admin_settings_after_' . $current_tab_name, $settings );

		if ( ! empty( $current_section ) ) {
			/**
			 * After admin tab section settings output.
			 *
			 * Executes right after setting output for a specific admin setting tab `$tab_name` e.g. shopmarks.
			 * `$current_section` refers to the current section e.g. product_widgets.
			 *
			 * @param array[] $settings The settings array.
			 *
			 * @since 3.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_admin_settings_after_' . $current_tab_name . '_' . $current_section, $settings );
		}
		?>
	</div>

	<?php if ( ! empty( $sidebar ) ) : ?>
		<div class="wc-gzd-admin-settings-sidebar">
			<?php echo wp_kses_post( $sidebar ); ?>
		</div>
	<?php endif; ?>
</div>
