<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wc-gzd-wizard-entry">
	<h1><?php esc_html_e( 'Adjust Germanized', 'woocommerce-germanized' ); ?></h1>
</div>

<div class="wc-gzd-wizard-inner-content inner-content-small">
	<p class="entry-desc"><?php esc_html_e( 'Configure Germanized to your needs. You can always adjust these settings later on.', 'woocommerce-germanized' ); ?></p>

	<div class="wc-gzd-wizard-config">
		<?php $wizard->output_errors(); ?>

		<table class="wc-gzd-wizard-settings form-table">
			<tbody>
				<?php WC_Admin_Settings::output_fields( $settings ); ?>
			</tbody>
		</table>
	</div>

	<?php $wizard->output_links(); ?>
</div>
