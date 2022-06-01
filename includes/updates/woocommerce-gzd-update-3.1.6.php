<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$option_names = array(
	'managewoocommerce_page_wc-gzd-shipmentscolumnshidden',
	'managewoocommerce_page_wc-gzd-return-shipmentscolumnshidden',
);

/**
 * Hide weight + dimensions columns by default
 */
foreach ( $option_names as $option_name ) {
	$hidden_columns = get_user_option( $option_name );

	if ( $hidden_columns && is_array( $hidden_columns ) ) {
		update_user_option( get_current_user_id(), $option_name, array_merge( $hidden_columns, array( 'weight', 'dimensions' ) ) );
	}
}
