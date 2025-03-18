<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\ShippingProvider;

use Vendidero\Shiptastic\Interfaces\ShippingProviderAuto;
use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\Labels\ConfigurationSetTrait;
use Vendidero\Shiptastic\Labels\Factory;
use Vendidero\Shiptastic\Labels\Label;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Packaging;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentError;
use Vendidero\Shiptastic\SimpleShipment;

defined( 'ABSPATH' ) || exit;

abstract class Auto extends Simple implements ShippingProviderAuto {

	use ConfigurationSetTrait;

	protected $extra_data = array(
		'label_print_format'                 => '',
		'label_auto_enable'                  => false,
		'label_auto_shipment_status'         => 'processing',
		'label_return_auto_enable'           => false,
		'label_return_auto_shipment_status'  => 'processing',
		'label_auto_shipment_status_shipped' => false,
		'label_references'                   => array(),
		'pickup_locations_enable'            => true,
		'pickup_locations_max_results'       => 20,
		'configuration_sets'                 => array(),
	);

	public function get_label_default_shipment_weight( $context = 'view' ) {
		return apply_filters( "{$this->get_hook_prefix()}label_default_shipment_weight", 0.5, $this );
	}

	protected function get_default_label_default_print_format() {
		return '';
	}

	/**
	 * Returns the minimum weight applied to the label. Defaults to 1g.
	 *
	 * @param $context
	 *
	 * @return float
	 */
	public function get_label_minimum_shipment_weight( $context = 'view' ) {
		return apply_filters( "{$this->get_hook_prefix()}label_minimum_shipment_weight", 0.001, $this );
	}

	/**
	 * @param false|Shipment $shipment
	 *
	 * @return boolean
	 */
	public function automatically_generate_label( $shipment = false ) {
		if ( $shipment && 'return' === $shipment->get_type() ) {
			return $this->automatically_generate_return_label();
		} else {
			return $this->get_label_auto_enable();
		}
	}

	public function automatically_generate_return_label() {
		return $this->get_label_return_auto_enable();
	}

	/**
	 * @param false|Shipment $shipment
	 *
	 * @return string
	 */
	public function get_label_automation_shipment_status( $shipment = false ) {
		if ( $shipment && 'return' === $shipment->get_type() ) {
			return $this->get_label_return_auto_shipment_status();
		}

		return $this->get_label_auto_shipment_status();
	}

	public function automatically_set_shipment_status_shipped( $shipment = false ) {
		return $this->get_label_auto_shipment_status_shipped();
	}

	public function get_label_auto_enable( $context = 'view' ) {
		return $this->get_prop( 'label_auto_enable', $context );
	}

	public function get_label_auto_shipment_status_shipped( $context = 'view' ) {
		return $this->get_prop( 'label_auto_shipment_status_shipped', $context );
	}

	public function get_label_auto_shipment_status( $context = 'view' ) {
		return $this->get_prop( 'label_auto_shipment_status', $context );
	}

	public function get_pickup_locations_enable( $context = 'view' ) {
		return $this->get_prop( 'pickup_locations_enable', $context );
	}

	public function get_pickup_locations_max_results( $context = 'view' ) {
		return $this->get_prop( 'pickup_locations_max_results', $context );
	}

	public function enable_pickup_location_delivery() {
		return $this->get_pickup_locations_enable();
	}

	public function get_label_references( $context = 'view' ) {
		$references = array_filter( (array) $this->get_prop( 'label_references', $context ) );

		if ( 'view' === $context ) {
			foreach ( $this->get_supported_shipment_types() as $shipment_type ) {
				if ( ! isset( $references[ $shipment_type ] ) || ! is_array( $references[ $shipment_type ] ) ) {
					$references[ $shipment_type ] = array();
				}

				foreach ( $this->get_supported_label_reference_types( $shipment_type ) as $ref_type => $ref_type_data ) {
					$ref_type_data = wp_parse_args(
						$ref_type_data,
						array(
							'default' => '',
						)
					);

					if ( ! isset( $references[ $shipment_type ][ $ref_type ] ) ) {
						$references[ $shipment_type ][ $ref_type ] = $ref_type_data['default'];
					}
				}
			}
		}

		return $references;
	}

	public function get_supported_label_reference_types( $shipment_type = 'simple' ) {
		return array();
	}

	/**
	 * @param $ref_type
	 * @param $shipment_type
	 *
	 * @return array|false
	 */
	public function get_supported_label_reference_type( $ref_type, $shipment_type = 'simple' ) {
		$ref_types = $this->get_supported_label_reference_types( $shipment_type );
		$type      = false;

		if ( array_key_exists( $ref_type, $ref_types ) ) {
			$type = wp_parse_args(
				$ref_types[ $ref_type ],
				array(
					'max_length' => -1,
					'label'      => '',
					'default'    => '',
				)
			);
		}

		return $type;
	}

	/**
	 * @param string $shipment_type
	 * @param Label|null $label
	 *
	 * @return array
	 */
	public function get_label_reference_placeholders( $shipment_type = 'simple', $label = null ) {
		$placeholders = array(
			'{shipment_number}' => _x( 'Shipment number, e.g. 5', 'shipments', 'woocommerce-germanized' ),
			'{order_number}'    => _x( 'Order number, e.g. 1234', 'shipments', 'woocommerce-germanized' ),
			'{item_count}'      => _x( 'Number of items included in the shipment, e.g. 5', 'shipments', 'woocommerce-germanized' ),
		);

		if ( $label ) {
			if ( $shipment = $label->get_shipment() ) {
				$placeholders['{shipment_number}'] = $shipment->get_shipment_number();
				$placeholders['{shipment_id}']     = $shipment->get_id();
				$placeholders['{order_number}']    = $shipment->get_order_number();
				$placeholders['{item_count}']      = $shipment->get_item_count();
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}label_reference_placeholders", $placeholders, $shipment_type, $label );
	}

	/**
	 * @param string $shipment_type
	 * @param string $reference_type
	 *
	 * @return string
	 */
	public function get_label_reference( $shipment_type = 'simple', $reference_type = '', $context = 'view' ) {
		$references = $this->get_label_references( $context );
		$reference  = '';

		if ( array_key_exists( $shipment_type, $references ) ) {
			$references = $references[ $shipment_type ];

			if ( array_key_exists( $reference_type, $references ) ) {
				$reference = $references[ $reference_type ];
			}
		}

		return $reference;
	}

	/**
	 * @param Label $label
	 * @param string $shipment_type
	 * @param string $reference_type
	 *
	 * @return string
	 */
	public function get_formatted_label_reference( $label, $shipment_type = 'simple', $reference_type = '' ) {
		$reference  = $this->get_label_reference( $shipment_type, $reference_type );
		$max_length = -1;

		if ( $ref_type = $this->get_supported_label_reference_type( $reference_type, $shipment_type ) ) {
			$max_length = $ref_type['max_length'];
		}

		if ( ! empty( $reference ) ) {
			$placeholders = $this->get_label_reference_placeholders( $shipment_type, $label );
			$reference    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $reference );
		}

		$formatted_ref = apply_filters( "{$this->get_hook_prefix()}formatted_label_reference", $reference, $label, $shipment_type, $reference_type );

		if ( -1 !== $max_length ) {
			$formatted_ref = wc_shiptastic_substring( $formatted_ref, 0, $max_length );
		}

		return $formatted_ref;
	}

	public function get_label_return_auto_enable( $context = 'view' ) {
		return $this->get_prop( 'label_return_auto_enable', $context );
	}

	public function get_label_print_format( $context = 'view' ) {
		return $this->get_prop( 'label_print_format', $context );
	}

	public function get_label_return_auto_shipment_status( $context = 'view' ) {
		return $this->get_prop( 'label_return_auto_shipment_status', $context );
	}

	public function is_sandbox() {
		return false;
	}

	public function set_label_auto_enable( $enable ) {
		$this->set_prop( 'label_auto_enable', wc_string_to_bool( $enable ) );
	}

	public function set_label_auto_shipment_status_shipped( $enable ) {
		$this->set_prop( 'label_auto_shipment_status_shipped', wc_string_to_bool( $enable ) );
	}

	public function set_label_auto_shipment_status( $status ) {
		$this->set_prop( 'label_auto_shipment_status', $status );
	}

	public function set_label_references( $references ) {
		$this->set_prop( 'label_references', $references );
	}

	public function set_label_return_auto_enable( $enable ) {
		$this->set_prop( 'label_return_auto_enable', wc_string_to_bool( $enable ) );
	}

	public function set_label_print_format( $format ) {
		$this->set_prop( 'label_print_format', $format );
	}

	public function set_label_return_auto_shipment_status( $status ) {
		$this->set_prop( 'label_return_auto_shipment_status', $status );
	}

	public function set_pickup_locations_enable( $enable ) {
		$this->set_prop( 'pickup_locations_enable', wc_string_to_bool( $enable ) );
	}

	public function set_pickup_locations_max_results( $max_results ) {
		$this->set_prop( 'pickup_locations_max_results', absint( $max_results ) );
	}

	public function get_label_classname( $type ) {
		$classname = '\Vendidero\Shiptastic\Labels\Label';

		if ( 'return' === $type ) {
			$classname = '\Vendidero\Shiptastic\Labels\ReturnLabel';
		}

		return $classname;
	}

	/**
	 * Whether or not this instance is a manual integration.
	 * Manual integrations are constructed dynamically from DB and do not support
	 * automatic shipment handling, e.g. label creation.
	 *
	 * @return bool
	 */
	public function is_manual_integration() {
		return false;
	}

	/**
	 * Whether or not this instance supports a certain label type.
	 *
	 * @param string $label_type The label type e.g. simple or return.
	 *
	 * @return bool
	 */
	public function supports_labels( $label_type, $shipment = false ) {
		return true;
	}

	/**
	 * @return null|\WP_Error|true
	 */
	public function test_connection() {
		return null;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return mixed|void
	 */
	public function get_label( $shipment ) {
		$type  = wc_stc_get_label_type_by_shipment( $shipment );
		$label = wc_stc_get_label_by_shipment( $shipment, $type );

		return apply_filters( "{$this->get_hook_prefix()}label", $label, $shipment, $this );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_label_fields_html( $shipment ) {
		/**
		 * Setup local variables
		 */
		$settings = $this->get_label_fields( $shipment );
		$provider = $this;

		if ( is_wp_error( $settings ) ) {
			$error = $settings;

			ob_start();
			include Package::get_path() . '/includes/admin/views/label/html-shipment-label-backbone-error.php';
			$html = ob_get_clean();
		} else {
			ob_start();
			include Package::get_path() . '/includes/admin/views/label/html-shipment-label-backbone-form.php';
			$html = ob_get_clean();
		}

		return apply_filters( "{$this->get_hook_prefix()}label_fields_html", $html, $shipment, $this );
	}

	public function get_section_help_link( $section ) {
		$help_link = parent::get_section_help_link( $section );

		if ( 'pickup_locations' === $section ) {
			$help_link = _x( 'https://vendidero.com/doc/shiptastic/shipping-to-pick-up-stations', 'shipments-help-url', 'woocommerce-germanized' );
		}

		return $help_link;
	}

	protected function get_printing_settings() {
		$settings = array(
			array(
				'title' => _x( 'Label Format', 'shipments', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'shipping_provider_label_format_options',
			),
		);

		$settings = array_merge(
			$settings,
			array(
				array(
					'title'   => _x( 'Default Format', 'shipments', 'woocommerce-germanized' ),
					'type'    => 'select',
					'id'      => 'label_print_format',
					'default' => $this->get_default_label_default_print_format(),
					'desc'    => _x( 'Set the default print format for a label.', 'shipments', 'woocommerce-germanized' ),
					'options' => $this->get_print_formats()->as_options(),
					'class'   => 'wc-enhanced-select',
					'value'   => $this->get_setting( 'label_print_format', $this->get_default_label_default_print_format() ),
				),
			)
		);

		foreach ( $this->get_products( array( 'parent_id' => 0 ) ) as $product ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title'             => $product->get_label(),
						'type'              => 'select',
						'id'                => 'label_print_format_' . $product->get_id(),
						'default'           => '',
						'custom_attributes' => array( 'data-placeholder' => _x( 'Same as default format', 'shipments', 'woocommerce-germanized' ) ),
						'options'           => array( '' => _x( 'Same as default format', 'shipments', 'woocommerce-germanized' ) ) + $this->get_print_formats( array( 'product_id' => $product->get_id() ) )->as_options(),
						'class'             => 'wc-enhanced-select-nostd',
						'value'             => $this->get_setting( "label_print_format_{$product->get_id()}" ),
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_label_format_options',
				),
			)
		);

		return $settings;
	}

	protected function get_pickup_locations_settings() {
		$settings = array(
			array(
				'title' => _x( 'Pickup Locations', 'shipments', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'shipping_provider_pickup_locations_options',
			),
			array(
				'title'   => _x( 'Enable', 'shipments', 'woocommerce-germanized' ),
				'desc'    => _x( 'Allow customers to choose a pickup location within checkout.', 'shipments', 'woocommerce-germanized' ),
				'id'      => 'pickup_locations_enable',
				'type'    => 'shiptastic_toggle',
				'default' => 'yes',
				'value'   => wc_bool_to_string( $this->get_setting( 'pickup_locations_enable', 'yes' ) ),
			),
			array(
				'title'             => _x( 'Limit results', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'number',
				'id'                => 'pickup_locations_max_results',
				'value'             => $this->get_setting( 'pickup_locations_max_results', 20 ),
				'desc_tip'          => _x( 'Limit the number of pickup locations presented to the customer.', 'shipments', 'woocommerce-germanized' ),
				'default'           => 20,
				'css'               => 'max-width: 60px;',
				'custom_attributes' => array(
					'max'                                  => 50,
					'data-show_if_pickup_locations_enable' => '',
				),
			),
		);

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_pickup_locations_options',
				),
			)
		);

		return $settings;
	}

	protected function get_automation_settings() {
		$settings = array(
			array(
				'title' => _x( 'Automation', 'shipments', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'shipping_provider_label_auto_options',
			),
		);

		$shipment_statuses = array_diff_key( wc_stc_get_shipment_statuses(), array_fill_keys( array( 'draft', 'delivered', 'returned', 'requested' ), '' ) );

		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => _x( 'Labels', 'shipments', 'woocommerce-germanized' ),
					'desc'  => _x( 'Automatically create labels for shipments.', 'shipments', 'woocommerce-germanized' ),
					'id'    => 'label_auto_enable',
					'type'  => 'shiptastic_toggle',
					'value' => wc_bool_to_string( $this->get_setting( 'label_auto_enable' ) ),
				),

				array(
					'title'             => _x( 'Status', 'shipments', 'woocommerce-germanized' ),
					'type'              => 'select',
					'id'                => 'label_auto_shipment_status',
					'desc'              => '<div class="wc-shiptastic-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a label.', 'shipments', 'woocommerce-germanized' ) . ' ' . ( 'yes' === Package::get_setting( 'auto_enable' ) ? sprintf( _x( 'Your current default shipment status is: <em>%s</em>.', 'shipments', 'woocommerce-germanized' ), wc_stc_get_shipment_status_name( Package::get_setting( 'auto_default_status' ) ) ) : '' ) . '</div>',
					'options'           => $shipment_statuses,
					'class'             => 'wc-enhanced-select',
					'custom_attributes' => array( 'data-show_if_label_auto_enable' => '' ),
					'value'             => $this->get_setting( 'label_auto_shipment_status' ),
				),

				array(
					'title' => _x( 'Shipment Status', 'shipments', 'woocommerce-germanized' ),
					'desc'  => _x( 'Mark shipment as shipped after label has been created successfully.', 'shipments', 'woocommerce-germanized' ),
					'id'    => 'label_auto_shipment_status_shipped',
					'type'  => 'shiptastic_toggle',
					'value' => wc_bool_to_string( $this->get_setting( 'label_auto_shipment_status_shipped' ) ),
				),
			)
		);

		if ( $this->supports_labels( 'return' ) ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title' => _x( 'Returns', 'shipments', 'woocommerce-germanized' ),
						'desc'  => _x( 'Automatically create labels for returns.', 'shipments', 'woocommerce-germanized' ),
						'id'    => 'label_return_auto_enable',
						'type'  => 'shiptastic_toggle',
						'value' => wc_bool_to_string( $this->get_setting( 'label_return_auto_enable' ) ),
					),

					array(
						'title'             => _x( 'Status', 'shipments', 'woocommerce-germanized' ),
						'type'              => 'select',
						'id'                => 'label_return_auto_shipment_status',
						'desc'              => '<div class="wc-shiptastic-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a return label.', 'shipments', 'woocommerce-germanized' ) . '</div>',
						'options'           => $shipment_statuses,
						'class'             => 'wc-enhanced-select',
						'custom_attributes' => array( 'data-show_if_label_return_auto_enable' => '' ),
						'value'             => $this->get_setting( 'label_return_auto_shipment_status' ),
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_label_auto_options',
				),
			)
		);

		return $settings;
	}

	public function get_settings_help_pointers( $section = '' ) {
		return array();
	}

	public function supports_pickup_locations() {
		return false;
	}

	public function supports_pickup_location_delivery( $address, $query_args = array() ) {
		return false;
	}

	/**
	 * @param $location_code
	 * @param $address
	 *
	 * @return PickupLocation|null
	 */
	protected function fetch_single_pickup_location( $location_code, $address = array() ) {
		return null;
	}

	protected function get_address_by_pickup_location_code( $location_code, $address = array() ) {
		$address    = $this->parse_pickup_location_address_args( $address );
		$code_parts = explode( '_', $location_code );

		if ( count( $code_parts ) === 3 ) {
			$address['country']  = $code_parts[1];
			$address['postcode'] = $code_parts[2];
		}

		return $address;
	}

	/**
	 * @param $location_code
	 * @param $address
	 *
	 * @return false|PickupLocation
	 */
	public function get_pickup_location_by_code( $location_code, $address = array() ) {
		$code = $this->parse_pickup_location_code( $location_code );

		if ( empty( $code ) ) {
			return false;
		}

		$address         = $this->get_address_by_pickup_location_code( $location_code, $address );
		$cache_key       = $this->get_pickup_location_cache_key( $location_code, $address );
		$pickup_location = get_transient( $cache_key );

		if ( false === $pickup_location ) {
			$pickup_location = $this->fetch_single_pickup_location( $location_code, $address );

			if ( ! is_null( $pickup_location ) ) {
				set_transient( $cache_key, $pickup_location, DAY_IN_SECONDS );
			} else {
				$pickup_location = false;
			}
		} elseif ( ! is_a( $pickup_location, 'Vendidero\Shiptastic\ShippingProvider\PickupLocation' ) ) {
			$pickup_location = false;
		}

		return $pickup_location;
	}

	protected function parse_pickup_location_code( $location_code ) {
		return wc_stc_parse_pickup_location_code( $location_code );
	}

	protected function get_pickup_location_cache_key( $location_code, $address = array() ) {
		$address       = $this->get_address_by_pickup_location_code( $location_code, $address );
		$location_code = $this->parse_pickup_location_code( $location_code );
		$cache_key     = "woocommerce_shiptastic_{$this->get_name()}_pickup_location_" . sanitize_key( $location_code ) . '_' . sanitize_key( $address['country'] ) . '_' . $address['postcode'];

		return $cache_key;
	}

	protected function get_pickup_locations_cache_key( $address, $query_args = array() ) {
		$address          = $this->parse_pickup_location_address_args( $address );
		$query_args       = $this->parse_pickup_location_query_args( $query_args );
		$cache_key_values = array(
			$query_args['limit'],
			$address['country'],
			$address['postcode'],
			$address['address_1'],
		);

		if ( ! empty( $query_args['shipping_method'] ) ) {
			$cache_key_values[] = 's:' . $query_args['shipping_method']->get_id() . '_' . $query_args['shipping_method']->get_instance_id();
		}

		if ( ! empty( $query_args['payment_method'] ) ) {
			$cache_key_values[] = 'p:' . $query_args['payment_method'];
		}

		$cache_key = "woocommerce_shiptastic_{$this->get_name()}_pickup_locations";

		foreach ( $cache_key_values as $cache_key_value ) {
			$cache_key .= '_' . sanitize_key( $cache_key_value );
		}

		return $cache_key;
	}

	public function is_valid_pickup_location( $location_code, $address = array() ) {
		if ( $this->get_pickup_location_by_code( $location_code, $address ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $address
	 * @param $query_args
	 *
	 * @return null|PickupLocation[]
	 */
	protected function fetch_pickup_locations( $address, $query_args = array() ) {
		return null;
	}

	protected function parse_pickup_location_address_args( $address ) {
		return wp_parse_args(
			$address,
			array(
				'city'      => '',
				'postcode'  => '',
				'country'   => '',
				'state'     => '',
				'address_1' => '',
			)
		);
	}

	protected function parse_pickup_location_query_args( $query_args ) {
		$query_args = wp_parse_args(
			$query_args,
			array(
				'max_dimensions'  => array(),
				'max_weight'      => 0.0,
				'limit'           => $this->get_pickup_locations_max_results(),
				'payment_gateway' => '',
				'shipping_method' => false,
			)
		);

		$query_args['max_dimensions'] = wp_parse_args(
			$query_args['max_dimensions'],
			array(
				'length' => 0.0,
				'width'  => 0.0,
				'height' => 0.0,
			)
		);

		return $query_args;
	}

	public function replace_shipping_address_by_pickup_location() {
		return true;
	}

	public function get_pickup_locations( $address, $query_args = array() ) {
		$query_args       = $this->parse_pickup_location_query_args( $query_args );
		$address          = $this->parse_pickup_location_address_args( $address );
		$cache_key        = $this->get_pickup_locations_cache_key( $address, $query_args );
		$pickup_locations = get_transient( $cache_key );

		if ( false === $pickup_locations && ( ! empty( $address['postcode'] ) || ! empty( $address['city'] ) ) ) {
			$pickup_locations = $this->fetch_pickup_locations( $address, $query_args );

			if ( ! is_null( $pickup_locations ) ) {
				set_transient( $cache_key, $pickup_locations, DAY_IN_SECONDS );
			} else {
				$pickup_locations = array();
			}
		}

		if ( ! is_array( $pickup_locations ) ) {
			$pickup_locations = array();
		}

		/**
		 * Make sure that (cached) pickup locations support data.
		 */
		if ( ! empty( $pickup_locations ) ) {
			foreach ( $pickup_locations as $k => $pickup_location ) {
				$cache_key                     = $this->get_pickup_location_cache_key( $pickup_location->get_id() );
				$single_cached_pickup_location = get_transient( $cache_key );

				if ( false === $single_cached_pickup_location || '' === $single_cached_pickup_location ) {
					set_transient( $cache_key, $pickup_location, DAY_IN_SECONDS );
				}

				if ( ! $pickup_location->supports_cart( $query_args ) ) {
					unset( $pickup_locations[ $k ] );
				}
			}

			$pickup_locations = array_values( $pickup_locations );
		}

		return $pickup_locations;
	}

	protected function get_label_settings_by_shipment_type( $shipment_type = 'simple' ) {
		$settings               = array();
		$reference_settings     = array();
		$label_placeholders     = $this->get_label_reference_placeholders( $shipment_type );
		$formatted_placeholders = '<ul class="wc-shiptastic-available-placeholders">';

		foreach ( $label_placeholders as $placeholder => $title ) {
			$formatted_placeholders .= sprintf( '<li><code>%s</code>: %s</li>', esc_attr( $placeholder ), esc_html( $title ) );
		}

		$formatted_placeholders .= '</ul>';

		foreach ( $this->get_supported_label_reference_types( $shipment_type ) as $reference_type => $reference_type_data ) {
			$reference_type_data  = wp_parse_args(
				(array) $reference_type_data,
				array(
					'label'      => '',
					'default'    => '',
					'max_length' => -1,
				)
			);
			$shipment_label_title = wc_stc_get_shipment_label_title( $shipment_type );

			$reference_settings[] = array(
				'title'        => $reference_type_data['label'],
				'desc'         => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Adjust %1$s printed on the %2$s label. Use the following placeholders to format the reference: %3$s', 'shipments', 'woocommerce-germanized' ), $reference_type_data['label'], $shipment_label_title, $formatted_placeholders ) . ( -1 !== $reference_type_data['max_length'] ? sprintf( _x( 'Please keep in mind that the formatted reference cannot exceed %1$s characters.', 'shipments', 'woocommerce-germanized' ), $reference_type_data['max_length'] ) : '' ) . '</div>',
				'id'           => "shipping_provider_label_reference_{$shipment_type}_{$reference_type}",
				'placeholder'  => $reference_type_data['default'],
				'value'        => $this->get_label_reference( $shipment_type, $reference_type, 'edit' ),
				'default'      => $reference_type_data['default'],
				'skip_install' => true,
				'type'         => 'textarea',
				'css'          => 'width: 100%; min-height: 60px; margin-top: 1em;',
			);
		}

		if ( ! empty( $reference_settings ) ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title' => _x( 'Label references', 'shipments', 'woocommerce-germanized' ),
						'type'  => 'title',
						'id'    => 'shipping_provider_label_references',
					),
				)
			);

			$settings = array_merge( $settings, $reference_settings );

			$settings = array_merge(
				$settings,
				array(
					array(
						'type' => 'sectionend',
						'id'   => 'shipping_provider_label_references',
					),
				)
			);
		}

		foreach ( $this->get_available_label_zones( $shipment_type ) as $zone ) {
			$setting_id          = 'shipping_provider_' . $shipment_type . '_label_' . $zone;
			$configuration_set   = $this->get_or_create_configuration_set(
				array(
					'shipping_provider_name' => $this->get_name(),
					'shipment_type'          => $shipment_type,
					'zone'                   => $zone,
				)
			);
			$inner_zone_settings = $this->get_label_settings_by_zone( $configuration_set );

			if ( ! empty( $inner_zone_settings ) ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title' => wc_stc_get_shipping_shipments_label_zone_title( $zone ),
							'type'  => 'title',
							'id'    => $setting_id,
						),
					),
					$inner_zone_settings,
					array(
						array(
							'type' => 'sectionend',
							'id'   => $setting_id,
						),
					)
				);
			}
		}

		return $settings;
	}

	protected function get_config_set_simple_label_settings() {
		$settings = $this->get_label_settings();
		$settings = array_merge( $settings, $this->get_label_settings_by_shipment_type( 'simple' ) );

		return $settings;
	}

	protected function get_config_set_return_label_settings() {
		$settings = $this->get_label_settings();
		$settings = array_merge( $settings, $this->get_label_settings_by_shipment_type( 'return' ) );

		return $settings;
	}

	protected function get_return_label_settings() {
		return array();
	}

	protected function get_label_settings() {
		return array();
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return string
	 */
	protected function get_default_product_for_zone( $configuration_set ) {
		$products = $this->get_products(
			array(
				'zone'          => $configuration_set->get_zone(),
				'shipment_type' => $configuration_set->get_shipment_type(),
			)
		);

		return $products->get_by_index( 0 )->get_id();
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return array
	 */
	protected function get_label_settings_by_zone( $configuration_set ) {
		$settings = array();
		$products = $this->get_products(
			array(
				'zone'          => $configuration_set->get_zone(),
				'shipment_type' => $configuration_set->get_shipment_type(),
			)
		);

		$services = $this->get_services(
			array(
				'zone'          => $configuration_set->get_zone(),
				'shipment_type' => $configuration_set->get_shipment_type(),
			)
		);

		if ( ! $products->empty() ) {
			$default_product    = $this->get_default_product_for_zone( $configuration_set );
			$product_setting_id = $configuration_set->get_setting_id( 'product' );
			$select             = $products->as_options();

			$settings = array_merge(
				$settings,
				array(
					array(
						'title'                 => _x( 'Default Service', 'dhl', 'woocommerce-germanized' ),
						'type'                  => 'select',
						'id'                    => $product_setting_id,
						'default'               => 'shipping_provider' === $configuration_set->get_setting_type() ? $default_product : $this->get_setting( $product_setting_id, $default_product ),
						'value'                 => $configuration_set->get_product() ? $configuration_set->get_product() : null,
						'desc'                  => sprintf( _x( 'Select the default service for %1$s.', 'shipments', 'woocommerce-germanized' ), wc_stc_get_shipping_shipments_label_zone_title( $configuration_set->get_zone() ) ),
						'options'               => $select,
						'class'                 => 'wc-enhanced-select',
						'provider'              => $this->get_name(),
						'shipment_setting_type' => 'product',
						'shipment_zone'         => $configuration_set->get_zone(),
						'shipment_type'         => $configuration_set->get_shipment_type(),
					),
				)
			);

			foreach ( $services as $service ) {
				$service_setting_fields = $service->get_setting_fields( $configuration_set );

				if ( ! empty( $product_setting_id ) ) {
					$count = 0;

					foreach ( $service_setting_fields as $k => $service_setting ) {
						++$count;

						$service_setting_fields[ $k ] = wp_parse_args(
							$service_setting_fields[ $k ],
							array(
								'custom_attributes'     => array(),
								'service'               => $service->get_id(),
								'provider'              => $this->get_name(),
								'shipment_setting_type' => 1 === $count ? 'service' : 'service_meta',
								'shipment_zone'         => $configuration_set->get_zone(),
								'shipment_type'         => $configuration_set->get_shipment_type(),
							)
						);

						$service_setting_fields[ $k ]['default']           = 'shipping_provider' === $configuration_set->get_setting_type() ? $service_setting_fields[ $k ]['default'] : $this->get_setting( $service_setting_fields[ $k ]['id'], $service_setting_fields[ $k ]['default'] );
						$service_setting_fields[ $k ]['custom_attributes'] = array_merge( array( "data-show_if_{$product_setting_id}" => implode( ',', $service->get_products() ) ), $service_setting_fields[ $k ]['custom_attributes'] );
					}
				}

				$settings = array_merge( $settings, $service_setting_fields );
			}
		}

		return $settings;
	}

	protected function get_available_base_countries() {
		$countries = array();

		if ( function_exists( 'WC' ) && WC()->countries ) {
			$countries = WC()->countries->get_countries();
		}

		return $countries;
	}

	public function get_setting_sections() {
		$sections = array(
			'' => _x( 'General', 'shipments', 'woocommerce-germanized' ),
		);

		foreach ( wc_stc_get_shipment_types() as $shipment_type ) {
			$settings = $this->{"get_config_set_{$shipment_type}_label_settings"}();

			if ( ! empty( $settings ) ) {
				$section_title = 'simple' === $shipment_type ? _x( 'Labels', 'shipments', 'woocommerce-germanized' ) : sprintf( _x( '%1$s labels', 'shipments', 'woocommerce-germanized' ), wc_stc_get_shipment_label_title( $shipment_type ) );
				$sections      = array_merge(
					$sections,
					array(
						'config_set_' . $shipment_type . '_label' => $section_title,
					)
				);
			}
		}

		if ( ! $this->get_print_formats()->empty() ) {
			$sections = array_merge(
				$sections,
				array(
					'printing' => _x( 'Printing', 'shipments', 'woocommerce-germanized' ),
				)
			);
		}

		$sections = array_merge(
			$sections,
			array(
				'automation' => _x( 'Automation', 'shipments', 'woocommerce-germanized' ),
			)
		);

		if ( $this->supports_pickup_locations() ) {
			$sections = array_merge(
				$sections,
				array(
					'pickup_locations' => _x( 'Pickup Locations', 'shipments', 'woocommerce-germanized' ),
				)
			);
		}

		$sections = array_replace_recursive( $sections, parent::get_setting_sections() );

		return $sections;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_label_fields( $shipment ) {
		if ( 'return' === $shipment->get_type() ) {
			$label_fields = $this->get_return_label_fields( $shipment );
		} else {
			$label_fields = $this->get_simple_label_fields( $shipment );
		}

		if ( ! is_wp_error( $label_fields ) ) {
			$label_fields = array_merge( $label_fields, $this->get_label_service_fields( $shipment ) );
		}

		return $label_fields;
	}

	public function get_label_service_fields( $shipment ) {
		$service_fields = array();
		$services       = $this->get_services( array( 'shipment' => $shipment ) );
		$default_props  = $this->get_default_label_props( $shipment );

		if ( ! empty( $services ) ) {
			$service_settings    = array();
			$has_default_service = ! empty( $default_props['services'] ) ? true : false;

			foreach ( $services as $service ) {
				if ( $service->supports_location( 'label_services' ) ) {
					$service_settings = array_merge( $service_settings, $service->get_label_fields( $shipment, 'services' ) );
				}
			}

			if ( ! empty( $service_settings ) ) {
				$service_fields[] = array(
					'type'         => 'services_start',
					'hide_default' => $has_default_service ? false : true,
					'id'           => '',
				);

				$service_fields = array_merge( $service_fields, $service_settings );
			}
		}

		return $service_fields;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	protected function get_simple_label_fields( $shipment ) {
		$defaults      = $this->get_default_label_props( $shipment );
		$available     = $this->get_available_label_products( $shipment );
		$print_formats = $this->get_available_label_print_formats( $shipment );

		$settings = array(
			array(
				'id'          => 'product_id',
				'label'       => sprintf( _x( '%s Product', 'shipments', 'woocommerce-germanized' ), $this->get_title() ),
				'description' => '',
				'options'     => $available,
				'value'       => $defaults['product_id'] && array_key_exists( $defaults['product_id'], $available ) ? $defaults['product_id'] : '',
				'type'        => 'select',
			),
		);

		if ( ! empty( $print_formats ) && count( $print_formats ) > 1 ) {
			$custom_attributes = array( 'data-products-supported' => '' );

			foreach ( $print_formats as $print_format_id ) {
				if ( $print_format = $this->get_print_format( $print_format_id ) ) {
					if ( ! empty( $print_format->get_products() ) ) {
						$custom_attributes['data-products-supported'] .= '&' . $print_format->get_id() . '=' . implode( ',', $print_format->get_products() );
					}
				}
			}

			$settings = array_merge(
				$settings,
				array(
					array(
						'id'                => 'print_format',
						'label'             => _x( 'Print Format', 'shipments', 'woocommerce-germanized' ),
						'description'       => '',
						'custom_attributes' => $custom_attributes,
						'options'           => $print_formats,
						'value'             => $defaults['print_format'] && array_key_exists( $defaults['print_format'], $print_formats ) ? $defaults['print_format'] : '',
						'type'              => 'select',
					),
				)
			);
		}

		if ( $this->label_supports_export_reference_number( $shipment ) ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'export_reference_number',
						'label'       => _x( 'Export Reference Number', 'shipments', 'woocommerce-germanized' ),
						'description' => _x( 'In case of a shipment with an export declaration - provide the assigned Export Reference Number for customs purposes.', 'shipments', 'woocommerce-germanized' ),
						'value'       => '',
						'type'        => 'text',
						'desc_tip'    => true,
					),
				)
			);
		}

		return $settings;
	}

	protected function label_supports_export_reference_number( $shipment ) {
		return apply_filters( "{$this->get_general_hook_prefix()}supports_export_reference_number", false, $shipment, $this );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	protected function get_return_label_fields( $shipment ) {
		return $this->get_simple_label_fields( $shipment );
	}

	/**
	 * @param Shipment $shipment
	 * @param $props
	 *
	 * @return ShipmentError|mixed
	 */
	protected function validate_label_request( $shipment, $props ) {
		return $props;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_label_props( $shipment ) {
		$default = array(
			'shipping_provider' => $this->get_name(),
			'weight'            => wc_stc_get_shipment_label_weight( $shipment ),
			'net_weight'        => wc_stc_get_shipment_label_weight( $shipment, true ),
			'shipment_id'       => $shipment->get_id(),
			'services'          => array(),
			'product_id'        => $this->get_default_label_product( $shipment ),
			'print_format'      => $this->get_default_label_print_format( $shipment ),
		);

		$dimensions = wc_stc_get_shipment_label_dimensions( $shipment );
		$default    = array_merge( $default, $dimensions );

		foreach ( $this->get_services(
			array(
				'shipment'   => $shipment,
				'product_id' => $default['product_id'],
			)
		) as $service ) {
			if ( $service->book_as_default( $shipment ) ) {
				$default['services'][] = $service->get_id();

				foreach ( $service->get_label_fields( $shipment ) as $setting ) {
					if ( isset( $setting['id'], $setting['value'] ) ) {
						if ( $setting['id'] === $service->get_label_field_id() && 'checkbox' === $setting['type'] ) {
							continue;
						} else {
							$setting_id = $setting['id'];

							/**
							 * Support array input fields, e.g. return_address[name].
							 * Automagically transform those values to associative arrays.
							 */
							if ( strstr( $setting_id, '[' ) ) {
								parse_str( $setting_id, $setting_array );

								foreach ( $setting_array as $setting_key => $inner_settings ) {
									$inner_setting_name                                   = current( array_keys( $inner_settings ) );
									$setting_array[ $setting_key ][ $inner_setting_name ] = $setting['value'];

									if ( ! isset( $default[ $setting_key ] ) ) {
										$default[ $setting_key ] = array();
									}

									$default[ $setting_key ] = array_replace_recursive( (array) $default[ $setting_key ], $setting_array[ $setting_key ] );
								}
							} else {
								$default[ $setting['id'] ] = $setting['value'];
							}
						}
					}
				}
			}
		}

		if ( 'return' === $shipment->get_type() ) {
			$default['sender_address'] = $shipment->get_sender_address();
		}

		return $default;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 * @param mixed $props
	 *
	 * @return ShipmentError|true
	 */
	public function create_label( $shipment, $props = false ) {
		$errors    = new ShipmentError();
		$org_props = $props;

		/**
		 * In case props is false this indicates an automatic (non-manual) request.
		 */
		if ( false === $props ) {
			$props = $this->get_default_label_props( $shipment );
		} elseif ( is_array( $props ) ) {
			$default_props                 = $this->get_default_label_props( $shipment );
			$default_props['services']     = array();
			$default_props['print_format'] = '';

			$props = wp_parse_args( $props, $default_props );
		}

		$props = wp_parse_args(
			$props,
			array(
				'services'     => array(),
				'print_format' => '',
				'product_id'   => '',
			)
		);

		/**
		 * Neither allow invalid service configuration from automatic nor manual requests.
		 */
		foreach ( $this->get_services() as $service ) {
			$label_field_id = $service->get_label_field_id();

			/**
			 * Should only be the case for manual requests, e.g. form submits.
			 */
			if ( array_key_exists( $label_field_id, $props ) ) {
				$value = $props[ $label_field_id ];

				if ( 'checkbox' === $service->get_option_type() ) {
					if ( wc_string_to_bool( $value ) ) {
						if ( ! in_array( $service->get_id(), $props['services'], true ) ) {
							$props['services'][] = $service->get_id();
						}
					}

					unset( $props[ $label_field_id ] );
				} elseif ( 'select' === $service->get_option_type() ) {
					if ( ! empty( $value ) && array_key_exists( $value, $service->get_options() ) ) {
						if ( ! in_array( $service->get_id(), $props['services'], true ) ) {
							$props['services'][] = $service->get_id();
						}
					} else {
						$props['services'] = array_diff( $props['services'], array( $service->get_id() ) );
						unset( $props[ $label_field_id ] );
					}
				}
			}

			/**
			 * Remove services + service meta in case the service is not available or not booked.
			 */
			if ( ! $service->supports(
				array(
					'shipment' => $shipment,
					'product'  => $props['product_id'],
				)
			) || ! in_array( $service->get_id(), $props['services'], true ) ) {
				$props['services'] = array_diff( $props['services'], array( $service->get_id() ) );

				foreach ( $service->get_label_fields( $shipment ) as $setting ) {
					$setting_id = $setting['id'];

					if ( strstr( $setting_id, '[' ) ) {
						$setting_parts = explode( '[', $setting_id );
						$setting_id    = $setting_parts[0];
					}

					if ( array_key_exists( $setting_id, $props ) ) {
						unset( $props[ $setting_id ] );
					}
				}

				if ( array_key_exists( $label_field_id, $props ) ) {
					unset( $props[ $label_field_id ] );
					continue;
				}
			}

			if ( in_array( $service->get_id(), $props['services'], true ) ) {
				$valid = $service->validate_label_request( $props, $shipment );

				if ( is_wp_error( $valid ) ) {
					$errors->merge_from( $valid );
				} else {
					foreach ( $service->get_label_fields( $shipment ) as $setting ) {
						if ( $label_field_id === $setting['id'] ) {
							continue;
						}

						if ( array_key_exists( $setting['id'], $props ) ) {
							$setting = wp_parse_args(
								$setting,
								array(
									'data_type' => '',
								)
							);

							if ( ! empty( $setting['data_type'] ) ) {
								if ( in_array( $setting['data_type'], array( 'price', 'decimal' ), true ) ) {
									$props[ $setting['id'] ] = wc_format_decimal( $props[ $setting['id'] ] );
								}
							}
						}
					}
				}
			}
		}

		$props = $this->validate_label_request( $shipment, $props );

		if ( is_wp_error( $props ) ) {
			$errors->merge_from( $props );
		}

		if ( wc_stc_shipment_wp_error_has_errors( $errors ) ) {
			return $errors;
		}

		if ( isset( $props['services'] ) ) {
			$props['services'] = array_unique( $props['services'] );
		}

		$label = Factory::get_label( 0, $this->get_name(), $shipment->get_type() );

		if ( $label ) {
			foreach ( $props as $key => $value ) {
				$setter = "set_{$key}";

				if ( is_callable( array( $label, $setter ) ) ) {
					$label->{$setter}( $value );
				} else {
					$label->update_meta_data( $key, $value );
				}
			}

			$label->set_shipment( $shipment );

			/**
			 * Fetch the label via API and store as file
			 */
			$result = $label->fetch();

			if ( is_wp_error( $result ) ) {
				$result = wc_stc_get_shipment_error( $result );

				if ( ! $result->is_soft_error() ) {
					$code = absint( $result->get_error_code() );

					/**
					 * Server error and/or too many requests: retry
					 */
					if ( 500 === $code || 429 === $code ) {
						$hits = false === get_transient( "{$this->get_general_hook_prefix()}label_rate_limit_hits" ) ? 0 : absint( get_transient( "{$this->get_general_hook_prefix()}label_rate_limit_hits" ) );

						if ( $hits <= apply_filters( 'woocommerce_shiptastic_max_api_retries', 5 ) ) {
							++$hits;

							sleep( 0.25 * $hits );
							set_transient( "{$this->get_general_hook_prefix()}label_rate_limit_hits", $hits, MINUTE_IN_SECONDS );

							return $this->create_label( $shipment, $org_props );
						}
					}

					do_action( "{$this->get_general_hook_prefix()}error_while_creating_label", $label, $result, $this );

					return $result;
				}
			}

			delete_transient( "{$this->get_general_hook_prefix()}label_rate_limit_hits" );

			do_action( "{$this->get_general_hook_prefix()}created_label", $label, $this );
			$label_id = $label->save();

			return is_wp_error( $result ) && $result->is_soft_error() ? $result : $label_id;
		}

		return new ShipmentError( 'label-error', _x( 'Error while creating the label.', 'shipments', 'woocommerce-germanized' ) );
	}

	public function get_available_label_zones( $shipment_type = 'simple' ) {
		return array(
			'dom',
			'eu',
			'int',
		);
	}

	/**
	 * @param Packaging $packaging
	 *
	 * @return array
	 */
	public function get_packaging_label_settings( $packaging ) {
		$settings = array();

		foreach ( $this->get_supported_label_config_set_shipment_types() as $shipment_type ) {
			$settings[ $shipment_type ] = array();

			foreach ( $this->get_available_label_zones( $shipment_type ) as $zone ) {
				$config_set_args = array(
					'shipping_provider_name' => $this->get_name(),
					'shipment_type'          => $shipment_type,
					'zone'                   => $zone,
					'setting_type'           => 'packaging',
				);

				if ( $available_config_set = $packaging->get_configuration_set( $config_set_args ) ) {
					$configuration_set = $available_config_set;
				} else {
					$configuration_set = new ConfigurationSet(
						array(
							'shipping_provider_name' => $this->get_name(),
							'shipment_type'          => $shipment_type,
							'zone'                   => $zone,
							'setting_type'           => 'packaging',
						)
					);
				}

				$label_settings = $this->get_label_settings_by_zone( $configuration_set );

				if ( ! empty( $label_settings ) ) {
					$settings[ $shipment_type ][ $zone ] = array();

					$settings[ $shipment_type ][ $zone ][] = array(
						'id'            => $configuration_set->get_setting_id( 'override' ),
						'default'       => '',
						'value'         => $packaging->has_configuration_set( $config_set_args ) ? 'yes' : 'no',
						'type'          => 'shipping_provider_packaging_zone_title',
						'provider'      => $this->get_name(),
						'shipment_zone' => $zone,
						'shipment_type' => $shipment_type,
						'title'         => wc_stc_get_shipping_shipments_label_zone_title( $zone ),
					);

					$settings[ $shipment_type ][ $zone ] = array_merge( $settings[ $shipment_type ][ $zone ], $label_settings );

					$settings[ $shipment_type ][ $zone ][] = array(
						'id'   => $configuration_set->get_setting_id( 'override' ),
						'type' => 'shipping_provider_packaging_zone_title_close',
					);
				}
			}

			if ( empty( $settings[ $shipment_type ] ) ) {
				unset( $settings[ $shipment_type ] );
			}
		}

		return $settings;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_available_label_services( $shipment ) {
		$services = array();

		foreach ( $this->get_services( array( 'shipment' => $shipment ) ) as $service ) {
			$services[] = $service->get_id();
		}

		return $services;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_available_label_products( $shipment ) {
		return $this->get_products( array( 'shipment' => $shipment ) )->as_options();
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_available_label_print_formats( $shipment ) {
		$products = $this->get_available_label_products( $shipment );

		return $this->get_print_formats(
			array(
				'shipment' => $shipment,
				'products' => array_keys( $products ),
			)
		)->as_options();
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_default_label_product( $shipment ) {
		$product_id = '';
		$available  = $this->get_available_label_products( $shipment );

		if ( $config_set = $shipment->get_label_configuration_set() ) {
			$product_id = $config_set->get_product();
		} else {
			$config_set = $this->get_or_create_configuration_set( $shipment );
			$product_id = $this->get_default_product_for_zone( $config_set );
		}

		$product_id = apply_filters( "{$this->get_hook_prefix()}default_label_product", $product_id, $shipment, $this );

		if ( ! array_key_exists( $product_id, $available ) && ! empty( $available ) ) {
			$original_product_id = $product_id;
			$product_id          = apply_filters( "{$this->get_hook_prefix()}fallback_label_product", array_keys( $available )[0], $original_product_id, $shipment, $available, $this );
		}

		return $product_id;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_default_label_print_format( $shipment ) {
		$product_id = $this->get_default_label_product( $shipment );

		if ( $this->get_setting( "label_print_format_{$product_id}" ) ) {
			return $this->get_setting( "label_print_format_{$product_id}" );
		} elseif ( $this->get_label_print_format() ) {
			return $this->get_label_print_format();
		} else {
			return $this->get_default_label_default_print_format();
		}
	}

	public function get_shipping_method_settings() {
		$method_settings = array();

		foreach ( $this->get_supported_label_config_set_shipment_types() as $shipment_type ) {
			foreach ( $this->get_available_label_zones( $shipment_type ) as $zone ) {
				$configuration_set = new ConfigurationSet(
					array(
						'shipping_provider_name' => $this->get_name(),
						'shipment_type'          => $shipment_type,
						'zone'                   => $zone,
						'setting_type'           => 'shipping_method',
					)
				);
				$settings          = $this->get_label_settings_by_zone( $configuration_set );

				if ( ! empty( $settings ) ) {
					if ( ! isset( $method_settings[ $zone ][ $shipment_type ] ) ) {
						$method_settings[ $zone ][ $shipment_type ] = array();

						$title_id = $configuration_set->get_setting_id( 'override' );

						$method_settings[ $zone ][ $shipment_type ][ $title_id ] = array(
							'id'                => $title_id,
							'default'           => '',
							'type'              => 'shipping_provider_method_zone_override_open',
							'sanitize_callback' => array( '\Vendidero\Shiptastic\ShippingMethod\MethodHelper', 'validate_method_zone_override' ),
							'provider'          => $this->get_name(),
							'shipment_zone'     => $zone,
							'shipment_type'     => $shipment_type,
							'title'             => wc_stc_get_shipping_shipments_label_zone_title( $zone ),
						);
					}

					foreach ( $settings as $setting ) {
						$setting = wp_parse_args(
							$setting,
							array(
								'default'               => '',
								'type'                  => '',
								'id'                    => '',
								'custom_attributes'     => array(),
								'shipment_setting_type' => '',
							)
						);

						if ( ! empty( $setting['custom_attributes'] ) ) {
							foreach ( $setting['custom_attributes'] as $attr => $val ) {
								$new_attr = $attr;

								if ( 'data-show_if_' === substr( $attr, 0, 13 ) ) {
									$new_attr = str_replace( 'label_config_set_', '', $attr );

									unset( $setting['custom_attributes'][ $attr ] );
								}

								$setting['custom_attributes'][ $new_attr ] = $val;
							}
						}

						$setting_id = $configuration_set->get_setting_id( $setting['id'], $setting['shipment_setting_type'] );

						if ( 'sectionend' === $setting['type'] ) {
							$setting['type'] = 'title';
						} elseif ( 'shiptastic_toggle' === $setting['type'] ) {
							$setting['type'] = 'checkbox';
						}

						if ( 'checkbox' === $setting['type'] ) {
							$setting['label'] = $setting['desc'];
						}

						if ( isset( $setting['desc'] ) && 'checkbox' !== $setting['type'] ) {
							$setting['description'] = $setting['desc'];
						}

						$method_settings[ $zone ][ $shipment_type ][ $setting_id ] = $setting;
					}

					$close_id = "label_config_set_override_close_{$this->get_name()}_{$shipment_type}_{$zone}";

					$method_settings[ $zone ][ $shipment_type ][ $close_id ] = array(
						'id'           => $close_id,
						'default'      => '',
						'display_only' => true,
						'provider'     => $this->get_name(),
						'type'         => 'shipping_provider_method_zone_override_close',
					);
				}
			}
		}

		return $method_settings;
	}

	public function get_configuration_set_setting_type() {
		return 'shipping_provider';
	}

	public function get_setting( $key, $default_value = null, $context = 'view' ) {
		$setting_name_clean = $this->unprefix_setting_key( $key );

		if ( $this->is_configuration_set_setting( $setting_name_clean ) ) {
			if ( $configuration_set = $this->get_configuration_set( $setting_name_clean ) ) {
				$value = $configuration_set->get_setting( $setting_name_clean, $default_value );
			} else {
				$value = $default_value;
			}

			return apply_filters( "{$this->get_hook_prefix()}setting_{$setting_name_clean}", $value, $key, $default_value, $context );
		} else {
			return parent::get_setting( $key, $default_value, $context );
		}
	}

	public function update_settings( $section = '', $data = null, $save = true ) {
		if ( 'config_set_' === substr( $section, 0, 11 ) ) {
			$section_parts = explode( '_', substr( $section, 11 ) );
			$shipment_type = $section_parts[0];

			$this->reset_configuration_sets(
				array(
					'shipment_type' => $shipment_type,
				)
			);
		}

		parent::update_settings( $section, $data, $save );
	}

	public function update_setting( $setting, $value ) {
		$setting_name_clean = $this->unprefix_setting_key( $setting );

		if ( $this->is_configuration_set_setting( $setting_name_clean ) ) {
			if ( $configuration_set = $this->get_or_create_configuration_set( $setting_name_clean ) ) {
				$configuration_set->update_setting( $setting_name_clean, $value );
				$this->update_configuration_set( $configuration_set );
			}
		} elseif ( 'label_reference_' === substr( $setting_name_clean, 0, 16 ) ) {
			$label_reference   = explode( '_', substr( $setting_name_clean, 16 ) );
			$ref_shipment_type = $label_reference[0];
			$ref_type          = implode( '_', array_slice( $label_reference, 1 ) );
			$references        = $this->get_label_references( 'edit' );

			if ( ! isset( $references[ $ref_shipment_type ] ) ) {
				$references[ $ref_shipment_type ] = array();
			}

			if ( empty( $value ) ) {
				if ( isset( $references[ $ref_shipment_type ][ $ref_type ] ) ) {
					unset( $references[ $ref_shipment_type ][ $ref_type ] );
				}
			} else {
				$references[ $ref_shipment_type ][ $ref_type ] = $value;
			}

			$this->set_label_references( $references );
		} else {
			parent::update_setting( $setting, $value );
		}
	}

	public function get_supported_label_config_set_shipment_types() {
		return $this->get_supported_shipment_types();
	}
}
