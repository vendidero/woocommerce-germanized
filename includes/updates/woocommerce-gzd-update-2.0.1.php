<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Checkboxes
$options = array(
	'terms'           => array(),
	'download'        => array(),
	'service'         => array(),
	'parcel_delivery' => array(),
	'sepa'            => array(),
	'privacy'         => array(),
);

// Terms
$options['terms']['label']         = get_option( 'woocommerce_gzd_checkout_legal_text', __( 'With your order, you agree to have read and understood our {term_link}Terms and Conditions{/term_link} your {revocation_link}Right of Recission{/revocation_link} and our {data_security_link}Privacy Policy{/data_security_link}.', 'woocommerce-germanized' ) );
$options['terms']['error_message'] = get_option( 'woocommerce_gzd_checkout_legal_text_error', __( 'To finish the order you have to accept to our {term_link}Terms and Conditions{/term_link}, {revocation_link}Right of Recission{/revocation_link} and our {data_security_link}Privacy Policy{/data_security_link}.', 'woocommerce-germanized' ) );
$options['terms']['hide_input']    = get_option( 'woocommerce_gzd_display_checkout_legal_no_checkbox', 'no' ) === 'yes' ? 'yes' : 'no';

// Download
$options['download']['is_enabled']    = get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox', 'yes' ) === 'yes' ? 'yes' : 'no';
$options['download']['label']         = get_option( 'woocommerce_gzd_checkout_legal_text_digital', __( 'For digital products: I strongly agree that the execution of the agreement starts before the revocation period has expired. I am aware that my right of withdrawal ceases with the beginning of the agreement.', 'woocommerce-germanized' ) );
$options['download']['error_message'] = get_option( 'woocommerce_gzd_checkout_legal_text_digital_error', __( 'To retrieve direct access to digital content you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ) );
$options['download']['types']         = get_option( 'woocommerce_gzd_checkout_legal_digital_types', array( 'downloadable' ) );
$options['download']['confirmation']  = get_option( 'woocommerce_gzd_order_confirmation_legal_digital_notice', __( 'Furthermore you have expressly agreed to start the performance of the contract for digital items (e.g. downloads) before expiry of the withdrawal period. I have noted to lose my {link}right of withdrawal{/link} with the beginning of the performance of the contract.', 'woocommerce-germanized' ) );

// Service
$options['service']['is_enabled']    = get_option( 'woocommerce_gzd_checkout_legal_service_checkbox', 'yes' ) === 'yes' ? 'yes' : 'no';
$options['service']['label']         = get_option( 'woocommerce_gzd_checkout_legal_text_service', __( 'For services: I demand and acknowledge the immediate performance of the service before the expiration of the withdrawal period. I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' ) );
$options['service']['error_message'] = get_option( 'woocommerce_gzd_checkout_legal_text_service_error', __( 'To allow the immediate performance of the services you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ) );
$options['service']['confirmation']  = get_option( 'woocommerce_gzd_order_confirmation_legal_service_notice', __( 'Furthermore you have expressly agreed to start the performance of the contract for services before expiry of the withdrawal period. I have noted to lose my {link}right of withdrawal{/link} with the beginning of the performance of the contract.', 'woocommerce-germanized' ) );

// Parcel Delivery
$options['parcel_delivery']['is_enabled']            = get_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox', 'no' ) === 'yes' ? 'yes' : 'no';
$options['parcel_delivery']['show_special']          = get_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_show', 'always' );
$options['parcel_delivery']['show_shipping_methods'] = get_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_methods', array() );
$options['parcel_delivery']['is_mandatory']          = get_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_required', 'no' ) === 'yes' ? 'yes' : 'no';
$options['parcel_delivery']['label']                 = get_option( 'woocommerce_gzd_checkout_legal_text_parcel_delivery', __( 'Yes, I would like to be reminded via E-mail about parcel delivery ({shipping_method_title}). Your E-mail Address will only be transferred to our parcel service provider for that particular reason.', 'woocommerce-germanized' ) );

// Privacy
$options['privacy']['is_enabled'] = get_option( 'woocommerce_gzd_customer_account_checkbox', 'yes' ) === 'yes' ? 'yes' : 'no';
$options['privacy']['label']      = get_option( 'woocommerce_gzd_customer_account_text', __( 'Yes, I’d like create a new account and have read and understood the {data_security_link}data privacy statement{/data_security_link}.', 'woocommerce-germanized' ) );

$direct_debit_settings = get_option( 'woocommerce_direct-debit_settings', array() );

if ( ! is_array( $direct_debit_settings ) ) {
	$direct_debit_settings = array();
}

// Sepa
$options['sepa']['is_enabled'] = ( isset( $direct_debit_settings['enable_checkbox'] ) && 'yes' === $direct_debit_settings['enable_checkbox'] ) ? 'yes' : 'no';

if ( isset( $direct_debit_settings['checkbox_label'] ) ) {
	$options['sepa']['label'] = $direct_debit_settings['checkbox_label'];
}

update_option( 'woocommerce_gzd_legal_checkboxes_settings', $options );

// Tour options
delete_option( 'woocommerce_gzd_hide_tour' );

$tour_sections = array(
	'general',
	'display',
	'email',
);

foreach ( $tour_sections as $section ) {
	update_option( 'woocommerce_gzd_hide_tour_' . $section, '1' );
}


