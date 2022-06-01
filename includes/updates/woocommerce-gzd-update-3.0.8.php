<?php

use Vendidero\Germanized\DHL\Package;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add DB options for old DHL-specific settings
if ( Package::has_dependencies() && Package::is_enabled() ) {

	if ( $provider = wc_gzd_get_shipping_provider( 'dhl' ) ) {

		$provider->set_tracking_desc_placeholder( get_option( 'woocommerce_gzd_dhl_label_tracking_desc' ) );
		$provider->set_tracking_url_placeholder( 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?lang=de&idc={tracking_id}&rfn=&extendedSearch=true' );
		$provider->save();
	}
}
