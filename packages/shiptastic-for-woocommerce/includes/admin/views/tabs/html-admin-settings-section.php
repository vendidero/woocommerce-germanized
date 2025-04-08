<?php
/**
 * Admin View: Settings section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$section_key = sanitize_key( $current_section );
?>

<div class="wc-shiptastic-admin-settings wc-shiptastic-admin-settings-<?php echo esc_attr( $current_tab_name ); ?> <?php echo( ! empty( $current_section ) ? 'wc-shiptastic-admin-settings-' . esc_attr( $current_tab ) . '-' . esc_attr( $current_section ) : '' ); ?>">
	<div class="wc-shiptastic-admin-settings-fields">
		<?php
		/**
		 * Before admin tab settings output.
		 *
		 * Executes right before setting output for a specific admin setting tab `$tab_name` e.g. shopmarks.
		 *
		 */
		do_action( 'woocommerce_shiptastic_admin_settings_before_' . $current_tab_name, $settings );

		if ( ! empty( $current_section ) ) {
			/**
			 * Before admin tab section settings output.
			 *
			 * Executes right before setting output for a specific admin setting tab `$tab_name` e.g. shopmarks.
			 * `$current_section` refers to the current section e.g. product_widgets.
			 *
			 * @param array[] $settings The settings array.
			 *
			 *
			 */
			do_action( 'woocommerce_shiptastic_admin_settings_before_' . $current_tab_name . '_' . $current_section, $settings );
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
		 *
		 */
		do_action( 'woocommerce_shiptastic_admin_settings_after_' . $current_tab_name, $settings );

		if ( ! empty( $current_section ) ) {
			/**
			 * After admin tab section settings output.
			 *
			 * Executes right after setting output for a specific admin setting tab `$tab_name` e.g. shopmarks.
			 * `$current_section` refers to the current section e.g. product_widgets.
			 *
			 * @param array[] $settings The settings array.
			 *
			 *
			 */
			do_action( 'woocommerce_shiptastic_admin_settings_after_' . $current_tab_name . '_' . $current_section, $settings );
		}
		?>
	</div>
</div>
