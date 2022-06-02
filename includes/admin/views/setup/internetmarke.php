<?php

use \Vendidero\Germanized\DHL\Admin\Importer\Internetmarke;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<h1><?php esc_html_e( 'Internetmarke', 'woocommerce-germanized' ); ?></h1>

<p class="headliner"><?php esc_html_e( 'Want to easily ship your orders via Deutsche Post? Enable our Internetmarke integration and start generating labels for shipments comfortably via your admin panel.', 'woocommerce-germanized' ); ?></p>

<div class="wc-gzd-admin-settings">
	<?php if ( Internetmarke::is_available() ) : ?>
		<div class="notice inline updated" style="margin: 0">
			<p><?php esc_html_e( 'We\'ve found out that you have been using the Internetmarke standalone Plugin already. We will automatically import your settings and you can start using our integration instead.', 'woocommerce-germanized' ); ?></p>
		</div>
	<?php else : ?>
		<?php WC_Admin_Settings::output_fields( $settings ); ?>
	<?php endif; ?>
</div>
