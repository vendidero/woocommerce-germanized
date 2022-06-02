<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<h1><?php esc_html_e( 'Adjust Germanized', 'woocommerce-germanized' ); ?></h1>

<p class="headliner"><?php esc_html_e( 'Configure Germanized to your needs. You can always adjust these settings later on.', 'woocommerce-germanized' ); ?></p>

<div class="wc-gzd-admin-settings">
	<?php WC_Admin_Settings::output_fields( $settings ); ?>
</div>
