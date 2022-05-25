<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

/**
 * Shipment Label Interface
 *
 * @package  Germanized/Shipments/Interfaces
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ShipmentLabel class.
 */
interface ShippingProvider {

	/**
	 * Return the unique identifier for the label
	 *
	 * @return mixed
	 */
	public function get_id();

	public function get_help_link();

	public function get_signup_link();

	public function is_pro();

	/**
	 * Whether or not this instance is a manual integration.
	 * Manual integrations are constructed dynamically from DB and do not support
	 * automatic shipment handling, e.g. label creation.
	 *
	 * @return bool
	 */
	public function is_manual_integration();

	public function supports_customer_return_requests();

	public function supports_labels( $label_type, $shipment = false );

	public function hide_return_address();

	public function get_edit_link( $section = '' );

	public function is_activated();

	public function needs_manual_confirmation_for_returns();

	public function supports_customer_returns( $order = false );

	public function supports_guest_returns();

	public function get_title( $context = 'view' );

	public function get_name( $context = 'view' );

	public function get_description( $context = 'view' );

	public function has_return_instructions();

	public function activate();

	public function deactivate();

	public function get_tracking_url( $shipment );

	public function get_tracking_desc( $shipment, $plain = false );

	public function get_tracking_placeholders( $shipment = false );

	public function get_setting( $key, $default = null );

	public function get_shipment_setting( $shipment, $key, $default = null );

	public function update_settings( $section = '', $data = null, $save = true );

	public function get_settings( $section = '', $for_shipping_method = false );

	public function update_setting( $setting, $value );

	public function get_setting_sections();

	public function get_shipping_method_settings();

	public function save();

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return mixed|void
	 */
	public function get_label( $shipment );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields_html( $shipment );

	public function create_label( $shipment, $props = false );
}
