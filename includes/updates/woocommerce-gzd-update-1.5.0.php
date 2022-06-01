<?php

// Insert complaints shortcode
$imprint = wc_get_page_id( 'imprint' );

if ( -1 !== $imprint ) {
	WC_GZD_Admin::instance()->insert_complaints_shortcode( $imprint );
}


