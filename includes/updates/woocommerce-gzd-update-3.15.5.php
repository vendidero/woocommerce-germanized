<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$manager = WC_GZD_Legal_Checkbox_Manager::instance();

/**
 * Update checkbox label to use the placeholder instead of law reference.
 */
if ( $checkbox = $manager->get_checkbox( 'photovoltaic_systems' ) ) {
	$label = $checkbox->get_label();

	if ( ! strstr( $label, '{legal_text}' ) ) {
		$label = str_replace( array( '§12 paragraph 3 UStG', '§12 Absatz 3 UStG' ), '{legal_text}', $label );
		$checkbox->update_option( 'label', $label );
	}
}
