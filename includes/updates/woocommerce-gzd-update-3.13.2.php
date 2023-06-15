<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( \Vendidero\Germanized\Shopmarks::get( 'cart' ) as $shopmark ) {
	if ( 'woocommerce_cart_item_name' === $shopmark->get_filter() ) {
		update_option( $shopmark->get_option_name( 'filter' ), 'woocommerce_after_cart_item_name' );
		update_option( $shopmark->get_option_name( 'action' ), $shopmark->get_default_priority() );
	}
}
