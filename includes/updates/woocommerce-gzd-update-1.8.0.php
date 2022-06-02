<?php

// Get all variable products
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Insert complaints shortcode in terms (if existent)
$terms = wc_get_page_id( 'terms' );

if ( -1 !== $terms ) {
	WC_GZD_Admin::instance()->insert_complaints_shortcode( $terms );
}


