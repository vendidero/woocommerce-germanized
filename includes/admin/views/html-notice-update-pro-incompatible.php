<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<style>
	.plugins p.wc-gzd-update-warning {
		border-top: 1px solid #ffb900; margin-left: -12px; margin-right: -12px; padding: 12px; padding-bottom: 3px;
	}
	.plugins p.wc-gzd-update-warning::before {
		display: none;
	}
</style>
<p class="wc-gzd-update-warning">
	<?php printf( __( '<strong>Be aware!</strong> This update is not compatible with your current Germanized Pro version. Please <a href="%s">check for updates</a> before updating Germanized to prevent <a href="%s">compatibility issues</a>.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/germanized-pro-aktualisieren', 'https://vendidero.de/dokument/wichtige-plugins-fehlen-sind-veraltet-oder-werden-nicht-unterstuetzt' ); ?>
