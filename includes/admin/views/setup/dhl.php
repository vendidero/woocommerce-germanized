<?php

use \Vendidero\Germanized\DHL\Admin\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<h1><?php _e( 'DHL', 'woocommerce-germanized' ); ?></h1>

<p class="headliner"><?php _e( 'Want to easily ship your orders via DHL? Enable our deep DHL integration and start generating labels for shipments comfortably via your admin panel.', 'woocommerce-germanized' ); ?></p>

<div class="wc-gzd-admin-settings">
    <?php if ( Importer::is_available() ) : ?>
        <div class="notice inline updated" style="margin: 0">
            <p><?php _e( 'We\'ve found out that you have been using DHL for WooCommerce already. We will automatically import your settings and you can start using our integration instead.', 'woocommerce-germanized' ); ?></p>
        </div>
    <?php else: ?>
	    <?php WC_Admin_Settings::output_fields( $settings ); ?>
    <?php endif; ?>
</div>