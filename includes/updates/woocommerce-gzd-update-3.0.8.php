<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add DB options for old DHL-specific settings
if ( $dhl = \Vendidero\Germanized\Shiptastic::get_shipping_provider( 'dhl' ) ) {
	\Vendidero\Germanized\Shiptastic::define_tables();

	update_metadata( 'stc_shipping_provider', $dhl->shipping_provider_id, '_tracking_desc_placeholder', get_option( 'woocommerce_gzd_dhl_label_tracking_desc' ) );
	update_metadata( 'stc_shipping_provider', $dhl->shipping_provider_id, '_tracking_url_placeholder', 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?lang=de&idc={tracking_id}&rfn=&extendedSearch=true' );
}
