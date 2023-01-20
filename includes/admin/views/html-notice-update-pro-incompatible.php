<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<p class="wc-gzd-update-warning"><?php echo wp_kses_post( sprintf( __( '<strong>Be aware!</strong> This update is not compatible with your current Germanized Pro version. Please <a href="%1$s">check for updates</a> before updating Germanized to prevent <a href="%2$s">compatibility issues</a>.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/germanized-pro-aktualisieren', 'https://vendidero.de/dokument/wichtige-plugins-fehlen-sind-veraltet-oder-werden-nicht-unterstuetzt' ) ); ?>
