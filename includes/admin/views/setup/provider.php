<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<h1><?php _e( 'Choose integrations', 'woocommerce-germanized' ); ?></h1>
<p class="headliner"><?php printf( __( 'Germanized offers seamless integration with your favourite shipping provider.', 'woocommerce-germanized' ) ); ?></p>

<div class="wc-gzd-admin-settings">
	<?php WC_Admin_Settings::output_fields( $settings ); ?>
</div>