<?php

namespace Vendidero\Shiptastic\ShippingProvider;

use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class Service {

	protected $shipment_type = 'simple';

	protected $products = array();

	protected $id = '';

	protected $internal_id = '';

	protected $label = '';

	protected $shipping_provider = null;

	protected $shipping_provider_name = '';

	protected $option_type = '';

	protected $description = '';

	protected $options = array();

	protected $default_value = '';

	protected $countries = null;

	protected $zones = array();

	protected $shipment_types = array();

	protected $setting_id = '';

	protected $locations = array();

	protected $long_description = '';

	public function __construct( $shipping_provider, $args = array() ) {
		if ( is_a( $shipping_provider, 'Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
			$this->shipping_provider      = $shipping_provider;
			$this->shipping_provider_name = $shipping_provider->get_name();
		} else {
			$this->shipping_provider_name = $shipping_provider;
		}

		$args = wp_parse_args(
			$args,
			array(
				'id'                 => '',
				'internal_id'        => '',
				'label'              => '',
				'description'        => '',
				'long_description'   => '',
				'option_type'        => 'checkbox',
				'default_value'      => 'no',
				'excluded_locations' => array(),
				'options'            => array(),
				'products'           => null,
				'shipment_types'     => array( 'simple' ),
				'countries'          => null,
				'zones'              => array_keys( wc_stc_get_shipping_label_zones() ),
			)
		);

		if ( empty( $args['id'] ) ) {
			$args['id'] = sanitize_key( $args['label'] );
		}

		if ( empty( $args['id'] ) ) {
			throw new \Exception( esc_html_x( 'A service needs an id.', 'shipments', 'woocommerce-germanized' ), 500 );
		}

		$this->id               = $args['id'];
		$this->internal_id      = empty( $args['internal_id'] ) ? $this->id : $args['internal_id'];
		$this->label            = $args['label'];
		$this->description      = $args['description'];
		$this->long_description = $args['long_description'];
		$this->option_type      = $args['option_type'];
		$this->default_value    = $args['default_value'];
		$this->options          = array_filter( (array) $args['options'] );
		$this->locations        = array_diff( wc_stc_get_shipping_provider_service_locations(), array_filter( (array) $args['excluded_locations'] ) );
		$this->products         = is_null( $args['products'] ) ? null : array_filter( (array) $args['products'] );
		$this->shipment_types   = array_filter( (array) $args['shipment_types'] );
		$this->countries        = is_null( $args['countries'] ) ? null : array_filter( (array) $args['countries'] );

		if ( ! empty( $this->countries ) ) {
			if ( 1 === count( $this->countries ) && Package::get_base_country() === $this->countries[0] ) {
				$args['zones'] = array( 'dom' );
			}

			if ( in_array( 'ALL_EU', $this->countries, true ) ) {
				$this->countries = array_diff( $this->countries, array( 'ALL_EU' ) );
				$this->countries = array_unique( array_merge( WC()->countries->get_european_union_countries(), $this->countries ) );
			}
		}

		$this->zones = array_filter( (array) $args['zones'] );
	}

	protected function get_general_hook_prefix() {
		return "woocommerce_shiptastic_{$this->shipping_provider_name}_label_service_{$this->get_id()}_";
	}

	public function get_id() {
		return $this->id;
	}

	public function get_internal_id() {
		return $this->internal_id;
	}

	public function get_label() {
		return $this->label;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_option_type() {
		return $this->option_type;
	}

	public function get_long_description() {
		return $this->long_description;
	}

	public function get_products() {
		return is_null( $this->products ) ? array() : $this->products;
	}

	public function get_locations() {
		return $this->locations;
	}

	public function get_setting_id( $args = array(), $service_meta = '' ) {
		if ( is_a( $args, 'Vendidero\Shiptastic\Shipment' ) ) {
			$args = array(
				'zone'          => $args->get_shipping_zone(),
				'shipment_type' => $args->get_type(),
			);
		} elseif ( is_a( $args, 'Vendidero\Shiptastic\Labels\ConfigurationSet' ) ) {
			$setting_id = $this->get_id() . ( empty( $service_meta ) ? '' : '-m-' . $service_meta );
			$group      = empty( $service_meta ) ? 'service' : 'service_meta';

			return $args->get_setting_id( $setting_id, $group );
		}

		$args = wp_parse_args(
			$args,
			array(
				'zone'          => 'dom',
				'shipment_type' => 'simple',
				'shipment'      => false,
			)
		);

		if ( is_a( $args['shipment'], 'Vendidero\Shiptastic\Shipment' ) ) {
			$args['zone']          = $args['shipment']->get_shipping_zone();
			$args['shipment_type'] = $args['shipment']->get_type();
		}

		$suffix = $this->get_id();
		$prefix = $args['shipment_type'] . '_' . $args['zone'];

		if ( ! empty( $service_meta ) ) {
			$suffix = $suffix . '_' . $service_meta;
		}

		return $prefix . "_label_service_{$suffix}";
	}

	public function get_label_field_id( $suffix = '' ) {
		$setting_base_id = $this->get_id();

		if ( ! empty( $suffix ) ) {
			$setting_base_id .= "_{$suffix}";
		}

		return "service_{$setting_base_id}";
	}

	public function get_default_value( $suffix = '' ) {
		return $this->default_value;
	}

	public function supports_location( $location ) {
		return in_array( $location, $this->get_locations(), true );
	}

	public function supports_product( $product ) {
		return is_null( $this->products ) ? true : in_array( $product, $this->get_products(), true );
	}

	public function supports_zone( $zone ) {
		return in_array( $zone, $this->zones, true );
	}

	public function get_zones() {
		return $this->zones;
	}

	public function supports_country( $country, $postcode = '' ) {
		$supports_country = true;

		if ( is_array( $this->countries ) ) {
			// Northern Ireland
			if ( 'GB' === $country && 'BT' === strtoupper( substr( trim( $postcode ), 0, 2 ) ) ) {
				$country = 'IX';
			}

			$supports_country = in_array( $country, $this->countries, true );
		}

		return $supports_country;
	}

	public function supports_shipment_type( $type ) {
		return in_array( $type, $this->shipment_types, true );
	}

	public function supports( $filter_args = array() ) {
		$filter_args = wp_parse_args(
			$filter_args,
			array(
				'country'       => '',
				'zone'          => '',
				'location'      => '',
				'product'       => '',
				'product_id'    => '',
				'shipment'      => false,
				'shipment_type' => '',
			)
		);

		if ( ! empty( $filter_args['product_id'] ) ) {
			$filter_args['product'] = $filter_args['product_id'];
		}

		if ( ! empty( $filter_args['product'] ) && is_a( $filter_args['product'], '\Vendidero\Shiptastic\ShippingProvider\Product' ) ) {
			$filter_args['product'] = $filter_args['product']->get_id();
		}

		$include_service = true;

		if ( ! empty( $filter_args['shipment'] ) && ( $shipment = wc_stc_get_shipment( $filter_args['shipment'] ) ) ) {
			$include_service = $this->supports_shipment( $shipment );

			$filter_args['shipment_type'] = '';
			$filter_args['zone']          = '';
			$filter_args['country']       = '';
		}

		if ( $include_service && ! empty( $filter_args['product'] ) && ! $this->supports_product( $filter_args['product'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['location'] ) && ! $this->supports_location( $filter_args['location'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['country'] ) && ! $this->supports_country( $filter_args['country'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['zone'] ) && ! $this->supports_zone( $filter_args['zone'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['shipment_type'] ) && ! $this->supports_shipment_type( $filter_args['shipment_type'] ) ) {
			$include_service = false;
		}

		return $include_service;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return boolean
	 */
	public function supports_shipment( $shipment ) {
		$supports_shipment = true;

		if ( ! $this->supports_shipment_type( $shipment->get_type() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_zone( $shipment->get_shipping_zone() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_country( $shipment->get_country() ) ) {
			$supports_shipment = false;
		}

		return $supports_shipment;
	}

	public function get_options() {
		return $this->options;
	}

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_stc_get_shipping_provider( $this->shipping_provider_name );
		}

		return $this->shipping_provider;
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return array
	 */
	public function get_setting_fields( $configuration_set ) {
		if ( ! $this->supports_location( $configuration_set->get_setting_type() . '_settings' ) || ! $this->supports_location( 'settings' ) ) {
			return array();
		}

		$setting_id  = $this->get_setting_id( $configuration_set );
		$value       = $this->get_value( $configuration_set );
		$option_type = $this->get_option_type();

		if ( 'checkbox' === $this->get_option_type() ) {
			if ( is_string( $value ) ) {
				$value = wc_bool_to_string( $value );
			}

			$option_type = 'shiptastic_toggle';
		}

		return array_merge(
			array(
				array(
					'title'   => $this->get_label(),
					'desc'    => $this->get_description() . ( ! empty( $this->get_long_description() ) ? ' <div class="wc-shiptastic-additional-desc">' . $this->get_long_description() . '</div>' : '' ),
					'id'      => $setting_id,
					'value'   => $value,
					'default' => $this->get_default_value(),
					'options' => $this->options,
					'type'    => $option_type,
				),
			),
			$this->get_additional_setting_fields( $configuration_set )
		);
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return array
	 */
	protected function get_additional_setting_fields( $configuration_set ) {
		return array();
	}

	/**
	 * @param $props
	 * @param Shipment $shipment
	 *
	 * @return true|\WP_Error
	 */
	public function validate_label_request( $props, $shipment ) {
		$error = new ShipmentError();

		foreach ( $this->get_additional_label_fields( $shipment ) as $field ) {
			$field = wp_parse_args(
				$field,
				array(
					'label'       => '',
					'id'          => '',
					'type'        => '',
					'is_required' => false,
					'data_type'   => '',
				)
			);

			if ( true === $field['is_required'] ) {
				$value = isset( $props[ $field['id'] ] ) ? $props[ $field['id'] ] : null;

				if ( in_array( $field['data_type'], array( 'price', 'decimal' ), true ) ) {
					$value = (float) wc_format_decimal( $value );
				}

				if ( empty( $value ) ) {
					$error->add( 500, sprintf( _x( 'Please choose a valid value for the service %1$s: %2$s.', 'shipments', 'woocommerce-germanized' ), $this->get_label(), $field['label'] ) );
				}
			}
		}

		if ( wc_stc_shipment_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return true;
	}

	protected function get_show_if_attributes() {
		if ( ! empty( $this->get_products() ) ) {
			return array(
				'data-products-supported' => implode( ',', $this->get_products() ),
			);
		} else {
			return array();
		}
	}

	/**
	 * @param Shipment|ConfigurationSet $shipment
	 * @param $suffix
	 *
	 * @return mixed
	 */
	public function get_value( $shipment, $suffix = '' ) {
		if ( is_a( $shipment, 'Vendidero\Shiptastic\Labels\ConfigurationSet' ) ) {
			$config_set = $shipment;
		} else {
			$config_set = $shipment->get_label_configuration_set();
		}

		$value = $this->get_default_value( $suffix );

		if ( $config_set ) {
			if ( empty( $suffix ) ) {
				$value = $config_set->get_service_value( $this->get_id(), $value );
			} else {
				$value = $config_set->get_service_meta( $this->get_id(), $suffix, $value );
			}
		}

		if ( 'no' === $value && is_a( $shipment, '\Vendidero\Shiptastic\Shipment' ) && true === $this->book_as_default( $shipment ) ) {
			$value = 'yes';
		}

		return apply_filters( "{$this->get_general_hook_prefix()}get_value", $value, $suffix, $shipment );
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return boolean
	 */
	public function book_as_default( $shipment ) {
		$book_as_default = false;
		$config_set      = $shipment->get_label_configuration_set();

		if ( $config_set && $config_set->has_service( $this->get_id() ) ) {
			$book_as_default = true;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}book_as_default", $book_as_default, $shipment );
	}

	public function get_label_fields( $shipment, $location = '' ) {
		if ( ( ! empty( $location ) && ! $this->supports_location( 'label_' . $location ) ) || ! $this->supports_location( 'labels' ) ) {
			return array();
		}

		$option_type = $this->get_option_type();

		return array_merge(
			array(
				array(
					'label'             => $this->get_label(),
					'description'       => $this->get_description(),
					'desc_tip'          => true,
					'wrapper_class'     => 'form-field-' . $option_type,
					'id'                => $this->get_label_field_id(),
					'value'             => $this->get_value( $shipment ),
					'options'           => $this->options,
					'type'              => $option_type,
					'custom_attributes' => $this->get_show_if_attributes(),
				),
			),
			$this->get_additional_label_fields( $shipment )
		);
	}

	protected function get_additional_label_fields( $shipment ) {
		return array();
	}
}
