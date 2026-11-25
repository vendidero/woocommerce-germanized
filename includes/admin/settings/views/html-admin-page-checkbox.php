<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkbox_id = $checkbox->get_id()
?>

<div class="wc-gzd-admin-settings wc-gzd-admin-settings-checkboxes">
	<div class="wc-gzd-admin-settings-fields">
		<?php

		/**
		 * Before output admin checkbox settings.
		 *
		 * Fires before a checkbox admin settings screen is rendered.
		 *
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 *
		 * @since 2.0.0
		 *
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
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 *
		 * @since 2.0.0
		 *
		 */
		do_action( "woocommerce_gzd_settings_section_after_checkbox_options_{$checkbox_id}", $checkbox );
		?>
	</div>

</div>
