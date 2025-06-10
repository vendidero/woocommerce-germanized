<?php

namespace Vendidero\Shiptastic\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class PickupLocation {

	protected $code = '';

	protected $type = '';

	protected $label = '';

	protected $latitude = '';

	protected $longitude = '';

	protected $shipping_provider_name = '';

	protected $supports_customer_number = false;

	protected $customer_number_is_mandatory = false;

	protected $address = array();

	protected $address_replacement_map = array();

	public function __construct( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'code'                         => '',
				'type'                         => '',
				'label'                        => '',
				'latitude'                     => '',
				'longitude'                    => '',
				'shipping_provider_name'       => '',
				'supports_customer_number'     => false,
				'customer_number_is_mandatory' => false,
				'address'                      => array(),
				'address_replacement_map'      => array(),
			)
		);

		if ( empty( $args['code'] ) ) {
			$args['code'] = sanitize_key( $args['label'] );
		}

		if ( empty( $args['code'] ) ) {
			throw new \Exception( esc_html( 'A pickup location needs a code.' ), 500 );
		}

		$code_parts      = wc_stc_get_pickup_location_code_parts( $args['code'] );
		$args['code']    = $code_parts['code'];
		$default_address = array(
			'address_1' => '',
			'city'      => '',
			'postcode'  => $code_parts['postcode'],
			'country'   => $code_parts['country'],
			'state'     => '',
			'company'   => '',
		);

		if ( ! empty( $code_parts['shipping_provider'] ) ) {
			$args['shipping_provider_name'] = $code_parts['shipping_provider'];
		}

		$this->code                         = $args['code'];
		$this->type                         = $args['type'];
		$this->label                        = $args['label'];
		$this->latitude                     = $args['latitude'];
		$this->longitude                    = $args['longitude'];
		$this->shipping_provider_name       = is_a( $args['shipping_provider_name'], '\Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ? $args['shipping_provider_name']->get_name() : $args['shipping_provider_name'];
		$this->supports_customer_number     = wc_string_to_bool( $args['supports_customer_number'] );
		$this->customer_number_is_mandatory = wc_string_to_bool( $args['customer_number_is_mandatory'] );
		$this->address                      = wp_parse_args( (array) $args['address'], $default_address );

		if ( empty( $this->address['company'] ) ) {
			$this->address['company'] = $this->get_label();
		}

		$this->address_replacement_map = (array) $args['address_replacement_map'];
	}

	public function get_id() {
		return $this->get_code();
	}

	public function get_code( $context = 'view' ) {
		if ( 'view' === $context ) {
			$provider_name = str_replace( '_', '#', $this->get_shipping_provider_name() );

			return sanitize_key( "{$this->code}_{$this->get_country()}_{$this->get_postcode()}" . ( ! empty( $provider_name ) ? "_{$provider_name}" : '' ) );
		} else {
			return $this->code;
		}
	}

	public function get_label() {
		return $this->label;
	}

	public function get_shipping_provider_name() {
		return $this->shipping_provider_name;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_address() {
		return $this->address;
	}

	public function get_postcode() {
		return $this->address['postcode'];
	}

	public function get_country() {
		return $this->address['country'];
	}

	public function get_city() {
		return $this->address['city'];
	}

	public function get_state() {
		return $this->address['state'];
	}

	public function get_address_1() {
		return $this->address['address_1'];
	}

	public function supports_customer_number() {
		return $this->supports_customer_number;
	}

	public function supports_cart( $cart_data ) {
		$cart_data = wp_parse_args(
			$cart_data,
			array(
				'max_dimensions' => array(),
				'max_weight'     => 0.0,
			)
		);

		return $this->supports_dimensions( $cart_data['max_dimensions'] ) && $this->supports_weight( $cart_data['max_weight'] );
	}

	/**
	 * @param array $dimensions
	 *
	 * @return boolean
	 */
	public function supports_dimensions( $dimensions ) {
		return true;
	}

	/**
	 * @param $weight
	 *
	 * @return boolean
	 */
	public function supports_weight( $weight ) {
		return true;
	}

	public function customer_number_is_mandatory() {
		return $this->customer_number_is_mandatory;
	}

	/**
	 * @param $customer_number
	 *
	 * @return bool|\WP_Error
	 */
	public function customer_number_is_valid( $customer_number ) {
		$is_valid = $this->customer_number_is_mandatory() ? ! empty( $customer_number ) : true;

		return $is_valid;
	}

	public function get_customer_number_field_label() {
		return _x( 'Customer Number', 'shipments', 'woocommerce-germanized' );
	}

	public function get_latitude() {
		return $this->latitude;
	}

	public function get_longitude() {
		return $this->longitude;
	}

	public function get_formatted_address( $separator = ', ' ) {
		return WC()->countries->get_formatted_address( $this->get_address(), $separator );
	}

	public function get_address_replacement_map() {
		return $this->address_replacement_map;
	}

	public function get_address_replacements() {
		$replacements              = array();
		$location_address          = $this->get_address();
		$location_address['label'] = $this->get_label();
		$location_address['code']  = $this->get_code( 'edit' );

		foreach ( $this->get_address_replacement_map() as $address_key => $location_address_key ) {
			$replacements[ $address_key ] = '';

			if ( isset( $location_address[ $location_address_key ] ) ) {
				$replacements[ $address_key ] = $location_address[ $location_address_key ];
			}
		}

		return $replacements;
	}

	public function replace_address( $target_object ) {
		foreach ( $this->get_address_replacements() as $address_field => $address_value ) {
			$setter = "set_shipping_{$address_field}";

			if ( is_callable( array( $target_object, $setter ) ) ) {
				$target_object->{$setter}( $address_value );
			}
		}
	}

	public function get_data() {
		return array(
			'code'                         => $this->get_code(),
			'type'                         => $this->get_type(),
			'label'                        => $this->get_label(),
			'latitude'                     => $this->get_latitude(),
			'longitude'                    => $this->get_longitude(),
			'supports_customer_number'     => $this->supports_customer_number(),
			'customer_number_is_mandatory' => $this->customer_number_is_mandatory(),
			'customer_number_field_label'  => $this->get_customer_number_field_label(),
			'address'                      => $this->get_address(),
			'address_replacement_map'      => $this->get_address_replacement_map(),
			'address_replacements'         => $this->get_address_replacements(),
			'formatted_address'            => $this->get_formatted_address(),
			'shipping_provider_name'       => $this->get_shipping_provider_name(),
		);
	}
}
