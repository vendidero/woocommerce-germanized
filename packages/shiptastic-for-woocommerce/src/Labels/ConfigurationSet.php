<?php

namespace Vendidero\Shiptastic\Labels;

use Vendidero\Shiptastic\Interfaces\LabelConfigurationSet;
use Vendidero\Shiptastic\Package;

defined( 'ABSPATH' ) || exit;

class ConfigurationSet {

	protected $shipment_type = 'simple';

	protected $shipping_provider_name = '';

	protected $zone = 'dom';

	protected $product = '';

	protected $services = array();

	protected $additional = array();

	protected $settings = null;

	protected $shipping_provider = null;

	protected $setting_type = 'shipping_provider';

	protected $all_services = null;

	/**
	 * @var null|LabelConfigurationSet
	 */
	protected $handler = null;

	/**
	 * @param array $args
	 * @param LabelConfigurationSet|null $handler
	 */
	public function __construct( $args = array(), $handler = null ) {
		$args = wp_parse_args(
			$args,
			array(
				'shipping_provider_name' => '',
				'shipment_type'          => 'simple',
				'setting_type'           => 'shipping_provider',
				'zone'                   => 'dom',
				'product'                => '',
				'services'               => array(),
				'additional'             => array(),
			)
		);

		$this->handler       = is_a( $handler, '\Vendidero\Shiptastic\Interfaces\LabelConfigurationSet' ) ? $handler : null;
		$this->shipment_type = $args['shipment_type'];
		$this->setting_type  = $args['setting_type'];
		$this->zone          = $args['zone'];

		$this->product    = $args['product'];
		$this->services   = $args['services'];
		$this->additional = $args['additional'];

		if ( is_a( $args['shipping_provider_name'], '\Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
			$this->shipping_provider      = $args['shipping_provider_name'];
			$this->shipping_provider_name = $this->shipping_provider->get_name();
		} else {
			$this->shipping_provider_name = $args['shipping_provider_name'];
		}
	}

	public function get_id() {
		return "-p-{$this->get_shipping_provider_name()}-s-{$this->get_shipment_type()}-z-{$this->get_zone()}";
	}

	/**
	 * @return null|LabelConfigurationSet
	 */
	public function get_handler() {
		return $this->handler;
	}

	/**
	 * @param LabelConfigurationSet|null $handler
	 */
	public function set_handler( $handler ) {
		$this->handler = $handler;
	}

	public function get_shipping_provider_name() {
		return $this->shipping_provider_name;
	}

	public function get_setting_type() {
		return $this->setting_type;
	}

	public function set_shipping_provider_name( $provider_name ) {
		$this->shipping_provider_name = $provider_name;
		$this->shipping_provider      = null;
	}

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_stc_get_shipping_provider( $this->get_shipping_provider_name() );
		}

		return $this->shipping_provider;
	}

	public function get_product() {
		return $this->product;
	}

	public function get_zone() {
		return $this->zone;
	}

	public function set_zone( $zone ) {
		$this->zone = $zone;
	}

	public function set_setting_type( $type ) {
		$this->setting_type = $type;
	}

	public function get_shipment_type() {
		return $this->shipment_type;
	}

	public function set_shipment_type( $type ) {
		$this->shipment_type = $type;
	}

	protected function get_all_services() {
		if ( is_null( $this->all_services ) ) {
			$this->all_services = wp_list_pluck( wp_list_filter( wp_list_filter( $this->services, array( 'value' => 'no' ), 'NOT' ), array( 'value' => '' ), 'NOT' ), 'name' );
		}

		return $this->all_services;
	}

	public function get_services() {
		return array_values( $this->get_all_services() );
	}

	public function get_service_id( $name ) {
		if ( array_key_exists( $name, $this->services ) ) {
			return $name;
		} elseif ( $key = array_search( $name, $this->get_all_services(), true ) ) {
				return $key;
		}

		return $name;
	}

	public function update_product( $value ) {
		$this->product = $value;
		$this->update();
	}

	protected function current_product_supports_service( $id ) {
		$supports_service = true;

		if ( $shipping_provider = $this->get_shipping_provider() ) {
			if ( $service = $shipping_provider->get_service( $id ) ) {
				if ( ! $service->supports_product( $this->get_product() ) ) {
					$supports_service = false;
				}
			}
		}

		return $supports_service;
	}

	public function update_service( $id, $value, $service_name = '' ) {
		if ( empty( $service_name ) ) {
			$service_name = array_key_exists( $id, $this->services ) ? $this->services[ $id ]['name'] : $id;
		}

		if ( ! $this->current_product_supports_service( $id ) ) {
			return;
		}

		if ( in_array( $value, array( true, false, 'true', 'false', 'yes', 'no' ), true ) ) {
			$value = wc_bool_to_string( $value );
		}

		$this->services[ $id ] = array(
			'id'    => $id,
			'name'  => $service_name,
			'value' => $value,
		);

		$this->all_services = null;

		$this->update();
	}

	public function update_service_meta( $service_id, $meta_key, $value ) {
		if ( $this->has_service( $service_id ) ) {
			$this->update_setting( $service_id . '_' . $meta_key, $value, 'additional' );
		}
	}

	public function has_service( $service ) {
		if ( $service_id = $this->get_service_id( $service ) ) {
			$service = $service_id;
		}

		return in_array( $service, $this->get_all_services(), true ) ? true : false;
	}

	public function get_service( $service ) {
		if ( $service_id = $this->get_service_id( $service ) ) {
			$service = $service_id;
		}

		if ( array_key_exists( $service, $this->services ) ) {
			return wp_parse_args(
				$this->services[ $service ],
				array(
					'id'    => '',
					'name'  => '',
					'value' => null,
				)
			);
		}

		return false;
	}

	public function get_service_meta( $service_id, $meta_key, $default_value = null ) {
		$additional_id = $service_id . '_' . $meta_key;

		return array_key_exists( $additional_id, $this->additional ) ? $this->additional[ $additional_id ] : $default_value;
	}

	public function get_service_value( $service, $default_value = null ) {
		if ( $service_id = $this->get_service_id( $service ) ) {
			$service = $service_id;
		}

		return array_key_exists( $service, $this->services ) ? $this->services[ $service ]['value'] : $default_value;
	}

	public function get_settings() {
		if ( is_null( $this->settings ) ) {
			$this->settings = array_merge( array( 'product' => $this->product ), wp_list_pluck( $this->services, 'value' ), $this->additional );
		}

		return $this->settings;
	}

	public function get_setting_id( $setting_name, $group = '' ) {
		if ( 'label_config_set_' === substr( $setting_name, 0, 17 ) ) {
			$setting_name = self::get_clean_setting_id( $setting_name );
		}

		if ( 'product' === $setting_name ) {
			$group = 'product';
		} elseif ( empty( $group ) ) {
			$group = 'additional';
		}

		$setting_id = "label_config_set_{$this->get_id()}-g-{$group}-n-{$setting_name}";

		return $setting_id;
	}

	protected function get_clean_setting_id( $id ) {
		$id = strrpos( $id, '-' ) !== false ? substr( $id, strrpos( $id, '-n-' ) + 3 ) : $id;

		return $id;
	}

	protected function get_setting_details( $id ) {
		$default_args = array(
			'suffix'       => $id,
			'group'        => '',
			'service_meta' => '',
		);

		$args = Package::extract_args_from_id( $id );

		if ( ! empty( $args['setting_name'] ) ) {
			$default_args['suffix']       = $args['setting_name'];
			$default_args['group']        = $args['setting_group'];
			$default_args['service_meta'] = $args['meta'];
		}

		return $default_args;
	}

	public function has_setting( $id ) {
		$details = $this->get_setting_details( $id );
		$id      = $this->get_clean_setting_id( $id );

		if ( 'service_meta' === $details['group'] && ! empty( $details['service_meta'] ) ) {
			$id = $details['suffix'] . '_' . $details['service_meta'];
		}

		$all_settings = $this->get_settings();
		$the_setting  = array_key_exists( $id, $all_settings ) ? $all_settings[ $id ] : null;

		if ( 'product' === $id && '' === $the_setting ) {
			return false;
		} elseif ( ! is_null( $the_setting ) ) {
			return true;
		}

		return false;
	}

	public function get_setting( $id, $default_value = null, $group = '' ) {
		$details  = $this->get_setting_details( $id );
		$settings = $this->get_settings();

		if ( '' === $group && ! empty( $details['group'] ) ) {
			$group = $details['group'];
			$id    = $details['suffix'];
		}

		if ( 'service_meta' === $group && ! empty( $details['service_meta'] ) ) {
			return $this->get_service_meta( $id, $details['service_meta'], $default_value );
		} else {
			$setting_id = $this->get_clean_setting_id( $id );

			if ( $this->has_setting( $setting_id ) ) {
				return $settings[ $setting_id ];
			}
		}

		return $default_value;
	}

	public function update_setting( $id, $value, $group = '' ) {
		$details = $this->get_setting_details( $id );

		if ( '' === $group && ! empty( $details['group'] ) ) {
			$group = $details['group'];
			$id    = $details['suffix'];
		}

		if ( ! empty( $group ) ) {
			if ( 'product' === $group ) {
				$this->update_product( $value );
			} elseif ( 'service' === $group ) {
				$this->update_service( $id, $value );
			} elseif ( 'service_meta' === $group && ! empty( $details['service_meta'] ) ) {
				$this->update_service_meta( $id, $details['service_meta'], $value );
			} elseif ( 'additional' === $group ) {
				$this->update_additional( $id, $value );
			}
		} elseif ( 'product' === $id ) {
			$this->update_product( $value );
		} elseif ( array_key_exists( $id, $this->services ) ) {
			$this->update_service( $id, $value );
		} elseif ( array_key_exists( $id, $this->additional ) ) {
			$this->update_additional( $id, $value );
		}
	}

	public function update_additional( $key, $value ) {
		$this->additional[ $key ] = $value;
		$this->update();
	}

	protected function update() {
		if ( $handler = $this->get_handler() ) {
			$handler->update_configuration_set( $this );
		}

		$this->settings = null;
	}

	public function get_data() {
		return array(
			'product'                => $this->product,
			'services'               => $this->services,
			'additional'             => $this->additional,
			'shipment_type'          => $this->get_shipment_type(),
			'shipping_provider_name' => $this->get_shipping_provider_name(),
			'zone'                   => $this->get_zone(),
			'setting_type'           => $this->get_setting_type(),
		);
	}
}
