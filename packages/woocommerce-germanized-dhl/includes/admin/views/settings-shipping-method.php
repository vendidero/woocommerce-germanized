<?php
/**
 * Settings for shipping method.
 */

defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\DHL\Package;
use \Vendidero\Germanized\DHL\ParcelServices;
use \Vendidero\Germanized\DHL\Admin\Settings;
use \Vendidero\Germanized\DHL\ParcelLocator;

$settings = array(
	'dhl_label_title' => array(
		'title'       => _x( 'DHL Labels', 'dhl', 'woocommerce-germanized' ),
		'type'        => 'title',
		'default'     => '',
		'description' => sprintf( _x( 'Adjust DHL label settings. Changes override <a href="%s">global settings</a>.', 'dhl', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=labels' ) ),
	),
);

$label_settings = Settings::get_label_default_settings( true );
$settings       = array_merge( $settings, $label_settings );

if ( Package::base_country_supports( 'services' ) ) {
	$settings = array_merge( $settings, array(
		'dhl_label_service_title' => array(
			'title'       => _x( 'DHL Label Services', 'dhl', 'woocommerce-germanized' ),
			'type'        => 'title',
			'default'     => '',
			'description' => sprintf( _x( 'Adjust default DHL label service settings. Changes override <a href="%s">global settings</a>.', 'dhl', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=labels' ) ),
		),
	) );

	$label_service_settings = Settings::get_label_default_services_settings( true );
	$settings               = array_merge( $settings, $label_service_settings );
}

$settings = array_merge( $settings, array(
	'dhl_label_auto_title' => array(
		'title'       => _x( 'DHL Label Automation', 'dhl', 'woocommerce-germanized' ),
		'type'        => 'title',
		'default'     => '',
		'description' => sprintf( _x( 'Adjust label automation settings. Changes override <a href="%s">global settings</a>.', 'dhl', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=labels' ) ),
	),
) );
$auto_settings  = Settings::get_automation_settings( true );
$settings       = array_merge( $settings, $auto_settings );

if ( ParcelServices::is_enabled() ) {
	$settings = array_merge( $settings, array(
		'dhl_preferred_services_title' => array(
			'title'       => _x( 'DHL Preferred Services', 'dhl', 'woocommerce-germanized' ),
			'type'        => 'title',
			'default'     => '',
			'description' => sprintf( _x( 'Adjust preferred service settings. Changes override <a href="%s">global settings</a>.', 'dhl', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=services' ) ),
		),
	) );
	$service_settings = Settings::get_preferred_services_settings( true );
	$settings         = array_merge( $settings, $service_settings );
}

if ( ParcelLocator::is_enabled() ) {
	$settings = array_merge( $settings, array(
		'dhl_parcel_pickup_title' => array(
			'title'       => _x( 'DHL Pickup', 'dhl', 'woocommerce-germanized' ),
			'type'        => 'title',
			'default'     => '',
			'description' => sprintf( _x( 'Adjust pickup settings. Changes override <a href="%s">global settings</a>.', 'dhl', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=pickup' ) ),
		),
	) );
	$service_settings = Settings::get_parcel_pickup_type_settings( true );
	$settings         = array_merge( $settings, $service_settings );
}

return $settings;
