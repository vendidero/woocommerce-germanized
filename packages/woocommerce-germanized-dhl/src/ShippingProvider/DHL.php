<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\DHL\ShippingProvider;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelServices;
use Vendidero\Germanized\Shipments\Admin\ProviderSettings;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShippingProvider\Auto;

defined( 'ABSPATH' ) || exit;

class DHL extends Auto {

	protected function get_default_label_default_shipment_weight() {
		return 0.5;
	}

	public function get_title( $context = 'view' ) {
		return _x( 'DHL', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name( $context = 'view' ) {
		return 'dhl';
	}

	public function get_description( $context = 'view' ) {
		return _x( 'Complete DHL integration supporting labels and preferred delivery.', 'dhl', 'woocommerce-germanized' );
	}

	public function get_default_tracking_url_placeholder() {
		return 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?lang=de&idc={tracking_id}&rfn=&extendedSearch=true';
	}

	public function is_sandbox() {
		return 'yes' === $this->get_meta( 'sandbox_mode', true );
	}

	public function get_customer_number() {
		return $this->get_meta( 'account_number', true );
	}

	public function get_label_classname( $type ) {
		if ( 'return' === $type ) {
			return '\Vendidero\Germanized\DHL\Label\DHLReturn';
		} elseif ( 'inlay_return' === $type ) {
			return '\Vendidero\Germanized\DHL\Label\DHLInlayReturn';
		} else {
			return '\Vendidero\Germanized\DHL\Label\DHL';
		}
	}

	public function supports_labels( $label_type, $shipment = false ) {
		$label_types = array( 'simple' );

		if ( $this->enable_retoure() ) {
			$label_types[] = 'return';
		}

		return in_array( $label_type, $label_types, true );
	}

	public function supports_customer_return_requests() {
		return $this->enable_retoure();
	}

	/**
	 * Some providers (e.g. DHL) create return labels automatically and the return
	 * address is chosen dynamically depending on the country. For that reason the return address
	 * might not show up within emails or in customer panel.
	 *
	 * @return bool
	 */
	public function hide_return_address() {
		return false;
	}

	public function get_api_username( $context = 'view' ) {
		return $this->get_meta( 'api_username', true, $context );
	}

	public function set_api_username( $username ) {
		$this->update_meta_data( 'api_username', strtolower( $username ) );
	}

	public function get_label_retoure_enable( $context = 'view' ) {
		return wc_string_to_bool( $this->get_meta( 'label_retoure_enable', true, $context ) );
	}

	public function set_label_retoure_enable( $enable ) {
		$this->update_meta_data( 'label_retoure_enable', wc_bool_to_string( $enable ) );
	}

	public function get_label_custom_shipper_reference( $context = 'view' ) {
		return $this->get_meta( 'label_custom_shipper_reference', true, $context );
	}

	public function set_label_custom_shipper_reference( $ref ) {
		$this->update_meta_data( 'label_custom_shipper_reference', $ref );
	}

	public function has_custom_shipper_reference() {
		$ref     = $this->get_label_custom_shipper_reference();
		$has_ref = wc_string_to_bool( $this->get_meta( 'label_use_custom_shipper', true ) );

		return $has_ref && ! empty( $ref );
	}

	public function get_retoure_receiver_ids( $context = 'view' ) {
		$ids = (array) $this->get_meta( 'retoure_receiver_ids', true, $context );

		return array_filter( $ids );
	}

	public function set_retoure_receiver_ids( $ids ) {
		$this->update_meta_data( 'retoure_receiver_ids', array_filter( (array) $ids ) );
	}

	public function get_api_sandbox_username( $context = 'view' ) {
		return $this->get_meta( 'api_sandbox_username', true, $context );
	}

	public function set_api_sandbox_username( $username ) {
		$this->update_meta_data( 'api_sandbox_username', strtolower( $username ) );
	}

	public function get_setting_sections() {
		$sections = parent::get_setting_sections();

		$sections['pickup']    = _x( 'Parcel Pickup', 'dhl', 'woocommerce-germanized' );
		$sections['preferred'] = _x( 'Preferred delivery', 'dhl', 'woocommerce-germanized' );

		return $sections;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_return_label_fields( $shipment ) {
		$default_args = $this->get_default_label_props( $shipment );

		return array(
			array(
				'id'          => 'receiver_slug',
				'label'       => _x( 'Receiver', 'dhl', 'woocommerce-germanized' ),
				'description' => '',
				'type'        => 'select',
				'options'     => wc_gzd_dhl_get_return_receivers(),
				'value'       => isset( $default_args['receiver_slug'] ) ? $default_args['receiver_slug'] : '',
			),
		);
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_simple_label_fields( $shipment ) {
		$settings     = parent::get_simple_label_fields( $shipment );
		$dhl_order    = wc_gzd_dhl_get_order( $shipment->get_order() );
		$default_args = $this->get_default_label_props( $shipment );

		if ( $dhl_order && $dhl_order->has_cod_payment() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'cod_total',
						'class'       => 'wc_input_decimal',
						'label'       => _x( 'COD Amount', 'dhl', 'woocommerce-germanized' ),
						'placeholder' => '',
						'description' => '',
						'value'       => isset( $default_args['cod_total'] ) ? wc_format_localized_decimal( $default_args['cod_total'] ) : '',
						'type'        => 'text',
					),
				)
			);
		}

		if ( Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'duties',
						'label'       => _x( 'Duties', 'dhl', 'woocommerce-germanized' ),
						'description' => '',
						'value'       => isset( $default_args['duties'] ) ? $default_args['duties'] : '',
						'options'     => wc_gzd_dhl_get_duties(),
						'type'        => 'select',
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'id'            => 'codeable_address_only',
					'label'         => _x( 'Valid address only', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'type'          => 'checkbox',
					'value'         => isset( $default_args['codeable_address_only'] ) ? wc_bool_to_string( $default_args['codeable_address_only'] ) : 'no',
					'wrapper_class' => 'form-field-checkbox',
				),
			)
		);

		$services = array(
			array(
				'id'                => 'service_GoGreen',
				'label'             => _x( 'GoGreen', 'dhl', 'woocommerce-germanized' ),
				'description'       => '',
				'type'              => 'checkbox',
				'value'             => in_array( 'GoGreen', $default_args['services'], true ) ? 'yes' : 'no',
				'wrapper_class'     => 'form-field-checkbox',
				'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'GoGreen', $shipment ),
			),
			array(
				'id'                => 'service_AdditionalInsurance',
				'label'             => _x( 'Additional insurance', 'dhl', 'woocommerce-germanized' ),
				'description'       => '',
				'type'              => 'checkbox',
				'value'             => in_array( 'AdditionalInsurance', $default_args['services'], true ) ? 'yes' : 'no',
				'wrapper_class'     => 'form-field-checkbox',
				'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'AdditionalInsurance', $shipment ),
			),
		);

		if ( Package::is_shipping_domestic( $shipment->get_country(), $shipment->get_postcode() ) ) {
			$preferred_days = array();

			try {
				$preferred_day_options = Package::get_api()->get_preferred_available_days( $shipment->get_postcode() );

				if ( $preferred_day_options ) {
					$preferred_days = $preferred_day_options;
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}

			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'preferred_day',
						'label'       => _x( 'Delivery day', 'dhl', 'woocommerce-germanized' ),
						'description' => '',
						'value'       => isset( $default_args['preferred_day'] ) ? $default_args['preferred_day'] : '',
						'options'     => wc_gzd_dhl_get_preferred_days_select_options( $preferred_days, ( isset( $default_args['preferred_day'] ) ? $default_args['preferred_day'] : '' ) ),
						'type'        => 'select',
					),
				)
			);

			if ( $dhl_order && $dhl_order->has_preferred_location() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'id'                => 'preferred_location',
							'label'             => _x( 'Drop-off location', 'dhl', 'woocommerce-germanized' ),
							'placeholder'       => '',
							'description'       => '',
							'value'             => isset( $default_args['preferred_location'] ) ? $default_args['preferred_location'] : '',
							'custom_attributes' => array( 'maxlength' => '80' ),
							'type'              => 'text',
						),
					)
				);
			}

			if ( $dhl_order && $dhl_order->has_preferred_neighbor() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'id'                => 'preferred_neighbor',
							'label'             => _x( 'Neighbor', 'dhl', 'woocommerce-germanized' ),
							'placeholder'       => '',
							'description'       => '',
							'value'             => isset( $default_args['preferred_neighbor'] ) ? $default_args['preferred_neighbor'] : '',
							'custom_attributes' => array( 'maxlength' => '80' ),
							'type'              => 'text',
						),
					)
				);
			}

			$settings = array_merge(
				$settings,
				array(
					array(
						'id'                => 'has_inlay_return',
						'label'             => _x( 'Create inlay return label', 'dhl', 'woocommerce-germanized' ),
						'class'             => 'checkbox show-if-trigger',
						'custom_attributes' => array( 'data-show-if' => '.show-if-has-return' ),
						'desc_tip'          => true,
						'value'             => isset( $default_args['has_inlay_return'] ) ? wc_bool_to_string( $default_args['has_inlay_return'] ) : 'no',
						'wrapper_class'     => 'form-field-checkbox',
						'type'              => 'checkbox',
					),
					array(
						'id'            => 'return_address[name]',
						'label'         => _x( 'Name', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'value'         => isset( $default_args['return_address']['name'] ) ? $default_args['return_address']['name'] : '',
						'type'          => 'text',
						'wrapper_class' => 'show-if-has-return',
					),
					array(
						'id'            => 'return_address[company]',
						'label'         => _x( 'Company', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'wrapper_class' => 'show-if-has-return',
						'type'          => 'text',
						'value'         => isset( $default_args['return_address']['company'] ) ? $default_args['return_address']['company'] : '',
					),
					array(
						'id'   => '',
						'type' => 'columns',
					),
					array(
						'id'            => 'return_address[street]',
						'label'         => _x( 'Street', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'type'          => 'text',
						'wrapper_class' => 'show-if-has-return column col-9',
						'value'         => isset( $default_args['return_address']['street'] ) ? $default_args['return_address']['street'] : '',
					),
					array(
						'id'            => 'return_address[street_number]',
						'label'         => _x( 'Street No', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'type'          => 'text',
						'wrapper_class' => 'show-if-has-return column col-3',
						'value'         => isset( $default_args['return_address']['street_number'] ) ? $default_args['return_address']['street_number'] : '',
					),
					array(
						'id'   => '',
						'type' => 'columns_end',
					),
					array(
						'id'   => '',
						'type' => 'columns',
					),
					array(
						'id'            => 'return_address[postcode]',
						'label'         => _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'type'          => 'text',
						'wrapper_class' => 'show-if-has-return column col-6',
						'value'         => isset( $default_args['return_address']['postcode'] ) ? $default_args['return_address']['postcode'] : '',
					),
					array(
						'id'            => 'return_address[city]',
						'label'         => _x( 'City', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'type'          => 'text',
						'wrapper_class' => 'show-if-has-return column col-6',
						'value'         => isset( $default_args['return_address']['city'] ) ? $default_args['return_address']['city'] : '',
					),
					array(
						'id'   => '',
						'type' => 'columns_end',
					),
					array(
						'id'   => '',
						'type' => 'columns',
					),
					array(
						'id'            => 'return_address[phone]',
						'label'         => _x( 'Phone', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'type'          => 'text',
						'wrapper_class' => 'show-if-has-return column col-6',
						'value'         => isset( $default_args['return_address']['phone'] ) ? $default_args['return_address']['phone'] : '',
					),
					array(
						'id'            => 'return_address[email]',
						'label'         => _x( 'Email', 'dhl', 'woocommerce-germanized' ),
						'placeholder'   => '',
						'description'   => '',
						'type'          => 'text',
						'wrapper_class' => 'show-if-has-return column col-6',
						'value'         => isset( $default_args['return_address']['email'] ) ? $default_args['return_address']['email'] : '',
					),
					array(
						'id'   => '',
						'type' => 'columns_end',
					),
				)
			);

			$services = array_merge(
				$services,
				array(
					array(
						'id'                => 'visual_min_age',
						'label'             => _x( 'Age check', 'dhl', 'woocommerce-germanized' ),
						'description'       => '',
						'type'              => 'select',
						'value'             => isset( $default_args['visual_min_age'] ) ? $default_args['visual_min_age'] : '',
						'options'           => wc_gzd_dhl_get_visual_min_ages(),
						'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'VisualCheckOfAge', $shipment ),
					),
				)
			);

			if ( $dhl_order && $dhl_order->supports_email_notification() ) {
				$services = array_merge(
					$services,
					array(
						array(
							'id'                => 'service_ParcelOutletRouting',
							'label'             => _x( 'Retail outlet routing', 'dhl', 'woocommerce-germanized' ),
							'description'       => '',
							'type'              => 'checkbox',
							'value'             => in_array( 'ParcelOutletRouting', $default_args['services'], true ) ? 'yes' : 'no',
							'wrapper_class'     => 'form-field-checkbox',
							'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'ParcelOutletRouting', $shipment ),
						),
					)
				);
			}

			if ( $dhl_order && ! $dhl_order->has_preferred_neighbor() ) {
				$services = array_merge(
					$services,
					array(
						array(
							'id'                => 'service_NoNeighbourDelivery',
							'label'             => _x( 'No neighbor', 'dhl', 'woocommerce-germanized' ),
							'description'       => '',
							'type'              => 'checkbox',
							'value'             => in_array( 'NoNeighbourDelivery', $default_args['services'], true ) ? 'yes' : 'no',
							'wrapper_class'     => 'form-field-checkbox',
							'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'NoNeighbourDelivery', $shipment ),
						),
					)
				);
			}

			$services = array_merge(
				$services,
				array(
					array(
						'id'                => 'service_NamedPersonOnly',
						'label'             => _x( 'Named person only', 'dhl', 'woocommerce-germanized' ),
						'description'       => '',
						'type'              => 'checkbox',
						'value'             => in_array( 'NamedPersonOnly', $default_args['services'], true ) ? 'yes' : 'no',
						'wrapper_class'     => 'form-field-checkbox',
						'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'NamedPersonOnly', $shipment ),
					),
					array(
						'id'                => 'service_BulkyGoods',
						'label'             => _x( 'Bulky goods', 'dhl', 'woocommerce-germanized' ),
						'description'       => '',
						'type'              => 'checkbox',
						'value'             => in_array( 'BulkyGoods', $default_args['services'], true ) ? 'yes' : 'no',
						'wrapper_class'     => 'form-field-checkbox',
						'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'BulkyGoods', $shipment ),
					),
					array(
						'id'                => 'service_IdentCheck',
						'label'             => _x( 'Identity check', 'dhl', 'woocommerce-germanized' ),
						'description'       => '',
						'type'              => 'checkbox',
						'class'             => 'checkbox show-if-trigger',
						'value'             => in_array( 'IdentCheck', $default_args['services'], true ) ? 'yes' : 'no',
						'custom_attributes' => array_merge( array( 'data-show-if' => '.show-if-ident-check' ), wc_gzd_dhl_get_service_product_attributes( 'IdentCheck', $shipment ) ),
						'wrapper_class'     => 'form-field-checkbox',
					),
					array(
						'id'    => '',
						'type'  => 'columns',
						'class' => 'show-if-ident-check show-if',
					),
					array(
						'id'                => 'ident_date_of_birth',
						'label'             => _x( 'Date of Birth', 'dhl', 'woocommerce-germanized' ),
						'placeholder'       => '',
						'description'       => '',
						'value'             => isset( $default_args['ident_date_of_birth'] ) ? $default_args['ident_date_of_birth'] : '',
						'custom_attributes' => array(
							'pattern'   => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
							'maxlength' => 10,
						),
						'class'             => 'short date-picker',
						'wrapper_class'     => 'column col-6',
						'type'              => 'text',
					),
					array(
						'id'            => 'ident_min_age',
						'label'         => _x( 'Minimum age', 'dhl', 'woocommerce-germanized' ),
						'description'   => '',
						'wrapper_class' => 'column col-6',
						'type'          => 'select',
						'value'         => isset( $default_args['ident_min_age'] ) ? $default_args['ident_min_age'] : '',
						'options'       => wc_gzd_dhl_get_ident_min_ages(),
					),
					array(
						'id'   => '',
						'type' => 'columns_end',
					),
				)
			);
		} else {
			/**
			 * Premium service is only available for non-domestic shipments (e.g. Paket International, WaPo International)
			 */
			$services = array_merge(
				$services,
				array(
					array(
						'id'                => 'service_Premium',
						'label'             => _x( 'Premium', 'dhl', 'woocommerce-germanized' ),
						'description'       => '',
						'value'             => in_array( 'Premium', $default_args['services'], true ) ? 'yes' : 'no',
						'wrapper_class'     => 'form-field-checkbox',
						'type'              => 'checkbox',
						'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'Premium', $shipment ),
					),
					array(
						'id'                => 'service_Economy',
						'label'             => _x( 'Economy', 'dhl', 'woocommerce-germanized' ),
						'description'       => '',
						'value'             => in_array( 'Economy', $default_args['services'], true ) ? 'yes' : 'no',
						'wrapper_class'     => 'form-field-checkbox',
						'type'              => 'checkbox',
						'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'Economy', $shipment ),
					),
				)
			);

			if ( ParcelServices::is_pddp_available( $shipment->get_country(), $shipment->get_postcode() ) ) {
				$services = array_merge(
					$services,
					array(
						array(
							'id'                => 'service_PDDP',
							'label'             => _x( 'PDDP (Postal Delivered Duty Paid)', 'dhl', 'woocommerce-germanized' ),
							'description'       => '',
							'value'             => in_array( 'PDDP', $default_args['services'], true ) ? 'yes' : 'no',
							'wrapper_class'     => 'form-field-checkbox',
							'type'              => 'checkbox',
							'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'PDDP', $shipment ),
						),
					)
				);
			}

			if ( ( $dhl_order && $dhl_order->has_cdp_delivery() ) || ParcelServices::is_cdp_available( $shipment->get_country() ) ) {
				$services = array_merge(
					$services,
					array(
						array(
							'id'                => 'service_CDP',
							'label'             => _x( 'CDP (Closest Droppoint)', 'dhl', 'woocommerce-germanized' ),
							'description'       => '',
							'value'             => in_array( 'CDP', $default_args['services'], true ) ? 'yes' : 'no',
							'wrapper_class'     => 'form-field-checkbox',
							'type'              => 'checkbox',
							'custom_attributes' => wc_gzd_dhl_get_service_product_attributes( 'CDP', $shipment ),
						),
					)
				);
			}
		}

		$settings[] = array(
			'type'         => 'services_start',
			'hide_default' => ! empty( $default_args['services'] ) ? false : true,
			'id'           => '',
		);

		$settings = array_merge( $settings, $services );

		return $settings;
	}

	public function get_participation_number( $product ) {
		return $this->get_setting( 'participation_' . $product, '' );
	}

	public function enable_retoure() {
		return $this->get_label_retoure_enable();
	}

	/**
	 * @param Shipment $shipment
	 * @param $props
	 *
	 * @return \WP_Error|mixed
	 */
	protected function validate_label_request( $shipment, $props ) {
		if ( 'return' === $shipment->get_type() ) {
			$props = $this->validate_return_label_args( $shipment, $props );
		} else {
			$props = $this->validate_simple_label_args( $shipment, $props );
		}

		return $props;
	}

	/**
	 * @param Shipment $shipment
	 * @param $args
	 *
	 * @return \WP_Error|mixed
	 */
	protected function validate_return_label_args( $shipment, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'receiver_slug' => '',
			)
		);

		$error = new \WP_Error();

		$args['receiver_slug'] = sanitize_key( $args['receiver_slug'] );

		if ( empty( $args['receiver_slug'] ) ) {
			$error->add( 500, _x( 'Receiver is missing or does not exist.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return $args;
	}

	/**
	 * @param Shipment $shipment
	 * @param $args
	 *
	 * @return \WP_Error|mixed
	 */
	protected function validate_simple_label_args( $shipment, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'preferred_day'       => '',
				'preferred_location'  => '',
				'preferred_neighbor'  => '',
				'ident_date_of_birth' => '',
				'ident_min_age'       => '',
				'visual_min_age'      => '',
				'has_inlay_return'    => 'no',
				'cod_total'           => 0,
				'product_id'          => '',
				'duties'              => '',
				'services'            => array(),
				'return_address'      => array(),
			)
		);

		$error     = new \WP_Error();
		$dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() );

		// Do only allow valid services
		if ( ! empty( $args['services'] ) ) {
			$args['services'] = array_intersect( $args['services'], wc_gzd_dhl_get_services() );

			foreach ( $args['services'] as $key => $service ) {
				/**
				 * Remove services that are not supported for this product
				 */
				if ( ! wc_gzd_dhl_product_supports_service( $args['product_id'], $service, $shipment ) ) {
					unset( $args['services'][ $key ] );
				}
			}

			$args['services'] = array_values( $args['services'] );
		}

		// Check if return address has empty mandatory fields
		if ( 'yes' === $args['has_inlay_return'] ) {
			$args['return_address'] = wp_parse_args(
				$args['return_address'],
				array(
					'name'          => '',
					'company'       => '',
					'street'        => '',
					'street_number' => '',
					'postcode'      => '',
					'city'          => '',
					'state'         => '',
					'country'       => Package::get_setting( 'return_country' ),
				)
			);

			$mandatory = array(
				'street'   => _x( 'Street', 'dhl', 'woocommerce-germanized' ),
				'postcode' => _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
				'city'     => _x( 'City', 'dhl', 'woocommerce-germanized' ),
			);

			foreach ( $mandatory as $mand => $title ) {
				if ( empty( $args['return_address'][ $mand ] ) ) {
					$error->add( 500, sprintf( _x( '%s of the return address is a mandatory field.', 'dhl', 'woocommerce-germanized' ), $title ) );
				}
			}

			if ( empty( $args['return_address']['name'] ) && empty( $args['return_address']['company'] ) ) {
				$error->add( 500, _x( 'Please either add a return company or name.', 'dhl', 'woocommerce-germanized' ) );
			}
		} else {
			unset( $args['return_address'] );
			unset( $args['has_inlay_return'] );
		}

		// No cash on delivery available
		if ( ( $dhl_order && ! empty( $args['cod_total'] ) && ! $dhl_order->has_cod_payment() ) || empty( $args['cod_total'] ) ) {
			unset( $args['cod_total'] );
		}

		if ( $dhl_order && ! empty( $args['cod_total'] ) && $dhl_order->has_cod_payment() && wc_gzd_dhl_product_supports_service( $args['product_id'], 'CashOnDelivery', $shipment ) ) {
			$args['services'] = array_merge( $args['services'], array( 'CashOnDelivery' ) );
		}

		if ( ! empty( $args['preferred_day'] ) && wc_gzd_dhl_is_valid_datetime( $args['preferred_day'], 'Y-m-d' ) ) {
			$args['services'] = array_merge( $args['services'], array( 'PreferredDay' ) );
		} else {
			if ( ! empty( $args['preferred_day'] ) && ! wc_gzd_dhl_is_valid_datetime( $args['preferred_day'], 'Y-m-d' ) ) {
				$error->add( 500, _x( 'Error while parsing delivery day.', 'dhl', 'woocommerce-germanized' ) );
			}

			$args['services'] = array_diff( $args['services'], array( 'PreferredDay' ) );

			unset( $args['preferred_day'] );
		}

		if ( ! empty( $args['preferred_location'] ) ) {
			$args['services'] = array_merge( $args['services'], array( 'PreferredLocation' ) );
		} else {
			$args['services'] = array_diff( $args['services'], array( 'PreferredLocation' ) );
			unset( $args['preferred_location'] );
		}

		if ( ! empty( $args['preferred_neighbor'] ) ) {
			$args['services'] = array_merge( $args['services'], array( 'PreferredNeighbour' ) );
		} else {
			$args['services'] = array_diff( $args['services'], array( 'PreferredNeighbour' ) );
			unset( $args['preferred_neighbor'] );
		}

		if ( wc_gzd_dhl_product_supports_service( $args['product_id'], 'VisualCheckOfAge', $shipment ) ) {
			if ( ! empty( $args['visual_min_age'] ) && wc_gzd_dhl_is_valid_visual_min_age( $args['visual_min_age'] ) ) {
				$args['services'] = array_merge( $args['services'], array( 'VisualCheckOfAge' ) );
			} else {
				if ( ! empty( $args['visual_min_age'] ) && ! wc_gzd_dhl_is_valid_visual_min_age( $args['visual_min_age'] ) ) {
					$error->add( 500, _x( 'The visual min age check is invalid.', 'dhl', 'woocommerce-germanized' ) );
				}

				$args['services'] = array_diff( $args['services'], array( 'VisualCheckOfAge' ) );
				unset( $args['visual_min_age'] );
			}
		} else {
			unset( $args['visual_min_age'] );
		}

		// In case order does not support email notification - remove parcel outlet routing
		if ( in_array( 'ParcelOutletRouting', $args['services'], true ) ) {
			if ( ! $dhl_order || ! $dhl_order->supports_email_notification() ) {
				$args['services'] = array_diff( $args['services'], array( 'ParcelOutletRouting' ) );
			}
		}

		if ( in_array( 'IdentCheck', $args['services'], true ) && wc_gzd_dhl_product_supports_service( $args['product_id'], 'IdentCheck', $shipment ) ) {
			if ( ! empty( $args['ident_min_age'] ) && ! array_key_exists( $args['ident_min_age'], wc_gzd_dhl_get_ident_min_ages() ) ) {
				$error->add( 500, _x( 'The ident min age check is invalid.', 'dhl', 'woocommerce-germanized' ) );
			}

			if ( ! empty( $args['ident_date_of_birth'] ) ) {
				if ( ! wc_gzd_dhl_is_valid_datetime( $args['ident_date_of_birth'], 'Y-m-d' ) ) {
					$error->add( 500, _x( 'There was an error parsing the date of birth for the identity check.', 'dhl', 'woocommerce-germanized' ) );
				}
			}

			if ( empty( $args['ident_date_of_birth'] ) && empty( $args['ident_min_age'] ) ) {
				$error->add( 500, _x( 'Either a minimum age or a date of birth must be added to the ident check.', 'dhl', 'woocommerce-germanized' ) );
			}
		} else {
			$args['services'] = array_diff( $args['services'], array( 'IdentCheck' ) );

			unset( $args['ident_min_age'] );
			unset( $args['ident_date_of_birth'] );
		}

		// We don't need duties for non-cross-border shipments
		if ( ! Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
			unset( $args['duties'] );
		}

		if ( ! empty( $args['duties'] ) && ! array_key_exists( $args['duties'], wc_gzd_dhl_get_duties() ) ) {
			$error->add( 500, sprintf( _x( '%s duties element does not exist.', 'dhl', 'woocommerce-germanized' ), $args['duties'] ) );
		}

		if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return $args;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_label_props( $shipment ) {
		if ( 'return' === $shipment->get_type() ) {
			$dhl_defaults = $this->get_default_return_label_props( $shipment );
		} else {
			$dhl_defaults = $this->get_default_simple_label_props( $shipment );
		}

		$defaults = parent::get_default_label_props( $shipment );

		return array_replace_recursive( $defaults, $dhl_defaults );
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_return_label_props( $shipment ) {
		$defaults = array(
			'services'       => array(),
			'receiver_slug'  => wc_gzd_dhl_get_default_return_receiver_slug( $shipment->get_sender_country() ),
			'sender_address' => $shipment->get_sender_address(),
		);

		$defaults['sender_address'] = array_merge(
			$defaults['sender_address'],
			array(
				'name'            => $shipment->get_formatted_sender_full_name(),
				'street'          => $shipment->get_sender_address_street(),
				'street_number'   => $shipment->get_sender_address_street_number(),
				'street_addition' => $shipment->get_sender_address_street_addition(),
			)
		);

		return $defaults;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_default_label_product( $shipment ) {
		if ( 'simple' === $shipment->get_type() ) {
			if ( Package::is_shipping_domestic( $shipment->get_country(), $shipment->get_postcode() ) ) {
				return $this->get_shipment_setting( $shipment, 'label_default_product_dom' );
			} elseif ( Package::is_eu_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
				$product = $this->get_shipment_setting( $shipment, 'label_default_product_eu' );

				if ( ! empty( $product ) && ! in_array( $product, array_keys( wc_gzd_dhl_get_products_eu() ), true ) ) {
					$product = 'V53WPAK';
				}

				return $product;
			} else {
				$product = $this->get_shipment_setting( $shipment, 'label_default_product_int' );

				if ( ! empty( $product ) && ! in_array( $product, array_keys( wc_gzd_dhl_get_products_international() ), true ) ) {
					$product = 'V53WPAK';
				}

				return $product;
			}
		}

		return '';
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_simple_label_props( $shipment ) {
		$dhl_order  = wc_gzd_dhl_get_order( $shipment->get_order() );
		$product_id = $this->get_default_label_product( $shipment );

		$defaults = array(
			'services'              => array(),
			'codeable_address_only' => $this->get_shipment_setting( $shipment, 'label_address_codeable_only' ),
		);

		if ( $dhl_order && $dhl_order->supports_email_notification() ) {
			$defaults['email_notification'] = 'yes';
		}

		if ( $dhl_order && $dhl_order->has_cod_payment() && wc_gzd_dhl_product_supports_service( $product_id, 'CashOnDelivery', $shipment ) ) {
			$defaults['cod_total'] = $shipment->get_total();

			/**
			 * This check is necessary to make sure only one label per order
			 * has the additional total (shipping total, fee total) added to the COD amount.
			 */
			$shipments              = wc_gzd_get_shipments_by_order( $shipment->get_order_id() );
			$needs_additional_total = true;

			foreach ( $shipments as $shipment ) {
				if ( $existing_label = $shipment->get_label() ) {
					if ( is_a( $existing_label, '\Vendidero\Germanized\DHL\Label\DHL' ) ) {
						if ( $existing_label->cod_includes_additional_total() ) {
							$needs_additional_total = false;
							break;
						}
					}
				}
			}

			if ( $needs_additional_total ) {
				$defaults['cod_total']                    += round( $shipment->get_additional_total(), wc_get_price_decimals() );
				$defaults['cod_includes_additional_total'] = true;
			}
		}

		if ( Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {

			$defaults['duties'] = $this->get_shipment_setting( $shipment, 'label_default_duty' );

		} elseif ( Package::is_shipping_domestic( $shipment->get_country(), $shipment->get_postcode() ) ) {

			if ( Package::base_country_supports( 'services' ) ) {

				if ( $dhl_order && $dhl_order->has_preferred_day() ) {
					$defaults['preferred_day'] = $dhl_order->get_preferred_day()->format( 'Y-m-d' );
				}

				if ( $dhl_order && $dhl_order->has_preferred_location() ) {
					$defaults['preferred_location'] = $dhl_order->get_preferred_location();
				}

				if ( $dhl_order && $dhl_order->has_preferred_neighbor() ) {
					$defaults['preferred_neighbor'] = $dhl_order->get_preferred_neighbor_formatted_address();
				}

				if ( wc_gzd_dhl_product_supports_service( $product_id, 'VisualCheckOfAge', $shipment ) ) {
					$visual_min_age = $this->get_shipment_setting( $shipment, 'label_visual_min_age' );

					if ( wc_gzd_dhl_is_valid_visual_min_age( $visual_min_age ) ) {
						$defaults['services'][]     = 'VisualCheckOfAge';
						$defaults['visual_min_age'] = $visual_min_age;
					}

					if ( $dhl_order && $dhl_order->needs_age_verification() && 'yes' === $this->get_shipment_setting( $shipment, 'label_auto_age_check_sync' ) ) {
						$defaults['services'][]     = 'VisualCheckOfAge';
						$defaults['visual_min_age'] = $dhl_order->get_min_age();
					}
				}

				if ( wc_gzd_dhl_product_supports_service( $product_id, 'IdentCheck', $shipment ) ) {
					$ident_min_age = $this->get_shipment_setting( $shipment, 'label_ident_min_age' );

					if ( wc_gzd_dhl_is_valid_ident_min_age( $ident_min_age ) ) {
						$defaults['services'][]    = 'IdentCheck';
						$defaults['ident_min_age'] = $ident_min_age;
					}

					/**
					 * Sync with order data but only in case no visual age has been synced already.
					 */
					if ( ! in_array( 'VisualCheckOfAge', $defaults['services'], true ) ) {
						if ( $dhl_order && $dhl_order->needs_age_verification() && 'yes' === $this->get_shipment_setting( $shipment, 'label_auto_age_check_ident_sync' ) ) {
							$defaults['services'][]    = 'IdentCheck';
							$defaults['ident_min_age'] = $dhl_order->get_min_age();
						}
					}
				}

				foreach ( wc_gzd_dhl_get_services() as $service ) {
					if ( ! wc_gzd_dhl_product_supports_service( $product_id, $service, $shipment ) ) {
						continue;
					}

					// Combination is not available
					if ( ( ! empty( $defaults['visual_min_age'] ) || ! empty( $defaults['ident_min_age'] ) ) && 'NamedPersonOnly' === $service ) {
						continue;
					}

					if ( 'yes' === $this->get_shipment_setting( $shipment, 'label_service_' . $service ) ) {
						$defaults['services'][] = $service;
					}
				}

				// Demove duplicates
				$defaults['services'] = array_unique( $defaults['services'] );
			}

			if ( Package::base_country_supports( 'returns' ) ) {

				$defaults['return_address'] = array(
					'name'          => Package::get_setting( 'return_name' ),
					'company'       => Package::get_setting( 'return_company' ),
					'street'        => Package::get_setting( 'return_street' ),
					'street_number' => Package::get_setting( 'return_street_number' ),
					'postcode'      => Package::get_setting( 'return_postcode' ),
					'city'          => Package::get_setting( 'return_city' ),
					'phone'         => Package::get_setting( 'return_phone' ),
					'email'         => Package::get_setting( 'return_email' ),
				);

				if ( 'yes' === $this->get_shipment_setting( $shipment, 'label_auto_inlay_return_label' ) ) {
					$defaults['has_inlay_return'] = 'yes';
				}
			}
		}

		if ( ! Package::is_shipping_domestic( $shipment->get_country(), $shipment->get_postcode() ) ) {
			foreach ( wc_gzd_dhl_get_international_services() as $service ) {
				if ( ! wc_gzd_dhl_product_supports_service( $product_id, $service, $shipment ) ) {
					continue;
				}

				if ( 'yes' === $this->get_shipment_setting( $shipment, 'label_service_' . $service ) ) {
					$defaults['services'][] = $service;
				}
			}

			if ( ! ParcelServices::is_cdp_available( $shipment->get_country() ) ) {
				$defaults['services'] = array_diff( $defaults['services'], array( 'CCP' ) );
			}

			if ( ! ParcelServices::is_pddp_available( $shipment->get_country(), $shipment->get_postcode() ) ) {
				$defaults['services'] = array_diff( $defaults['services'], array( 'PDDP' ) );
			}

			/**
			 * Book CDP or home delivery (Premium) service in case customer has chosen it via checkout.
			 */
			if ( $dhl_order ) {
				if ( $dhl_order->has_cdp_delivery() ) {
					$defaults['services'][] = 'CDP';
				} elseif ( 'home' === $dhl_order->get_preferred_delivery_type() ) {
					$defaults['services'][] = 'Premium';
				}
			}

			if ( in_array( 'CDP', $defaults['services'], true ) ) {
				$defaults['services'] = array_diff( $defaults['services'], array( 'Economy', 'Premium' ) );
			}

			if ( in_array( 'Premium', $defaults['services'], true ) ) {
				$defaults['services'] = array_diff( $defaults['services'], array( 'CDP', 'Economy' ) );
			}

			if ( in_array( 'Economy', $defaults['services'], true ) ) {
				$defaults['services'] = array_diff( $defaults['services'], array( 'CDP', 'Premium' ) );
			}

			// Remove duplicates
			$defaults['services'] = array_unique( $defaults['services'] );
		}

		return $defaults;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_products( $shipment ) {
		return wc_gzd_dhl_get_products( $shipment->get_country(), $shipment->get_postcode() );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_services( $shipment ) {
		return wc_gzd_dhl_get_services();
	}

	protected function get_available_base_countries() {
		return Package::get_available_countries();
	}

	protected function get_connection_status_html() {
		$username = wc_string_to_bool( $this->get_setting( 'sandbox_mode', 'no' ) ) ? $this->get_setting( 'api_sandbox_username', '' ) : $this->get_setting( 'api_username', '' );

		if ( empty( $username ) ) {
			return '';
		}

		$response  = Package::get_api()->test_connection();
		$has_error = is_wp_error( $response ) ? true : false;

		return '<span class="wc-gzd-shipment-api-connection-status ' . ( $has_error ? 'connection-status-error' : 'connection-status-success' ) . '">' . ( sprintf( _x( 'Status: %1$s', 'dhl', 'woocommerce-germanized' ), ( $has_error ? $response->get_error_message() : _x( 'Connected', 'dhl', 'woocommerce-germanized' ) ) ) ) . '</span>';
	}

	protected function get_general_settings( $for_shipping_method = false ) {
		$connection_status_html = ( ! $for_shipping_method && $this->is_activated() ) ? $this->get_connection_status_html() : '';

		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'dhl_general_options',
			),

			array(
				'title'             => _x( 'Customer Number (EKP)', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your 10 digits DHL customer number, also called "EKP". Find your %s in the DHL business portal.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '" target="_blank">' . _x( 'customer number', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id'                => 'account_number',
				'value'             => $this->get_setting( 'account_number', '' ),
				'placeholder'       => '1234567890',
				'custom_attributes' => array( 'maxlength' => '10' ),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'dhl_general_options',
			),

			array(
				'title' => _x( 'API', 'dhl', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'dhl_api_options',
				'desc'  => $connection_status_html,
			),

			array(
				'title' => _x( 'Enable Sandbox', 'dhl', 'woocommerce-germanized' ),
				'desc'  => _x( 'Activate Sandbox mode for testing purposes.', 'dhl', 'woocommerce-germanized' ),
				'id'    => 'sandbox_mode',
				'value' => wc_bool_to_string( $this->get_setting( 'sandbox_mode', 'no' ) ),
				'type'  => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Live Username', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your username (<strong>not</strong> your email address) to the DHL business customer portal. Please make sure to test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id'                => 'api_username',
				'default'           => '',
				'value'             => $this->get_setting( 'api_username', '' ),
				'custom_attributes' => array(
					'data-show_if_sandbox_mode' => 'no',
					'autocomplete'              => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Live Password', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'password',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your password to the DHL business customer portal. Please note the new assignment of the password to 3 (Standard User) or 12 (System User) months and make sure to test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id'                => 'api_password',
				'value'             => $this->get_setting( 'api_password', '' ),
				'custom_attributes' => array(
					'data-show_if_sandbox_mode' => 'no',
					'autocomplete'              => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Sandbox Username', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your username (<strong>not</strong> your email address) to the DHL developer portal. Please make sure to test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://entwickler.dhl.de" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id'                => 'api_sandbox_username',
				'value'             => $this->get_setting( 'api_sandbox_username', '' ),
				'custom_attributes' => array(
					'data-show_if_sandbox_mode' => '',
					'autocomplete'              => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Sandbox Password', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'password',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your password for the DHL developer portal. Please test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://entwickler.dhl.de" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id'                => 'api_sandbox_password',
				'value'             => $this->get_setting( 'api_sandbox_password', '' ),
				'custom_attributes' => array(
					'data-show_if_sandbox_mode' => '',
					'autocomplete'              => 'new-password',
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'dhl_api_options',
			),

			array(
				'title' => _x( 'Products and Participation Numbers', 'dhl', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'dhl_api_options',
			),
		);

		$dhl_products = array();

		foreach ( ( wc_gzd_dhl_get_products_domestic() + wc_gzd_dhl_get_products_eu() + wc_gzd_dhl_get_products_international() ) as $product => $title ) {
			$dhl_products[] = array(
				'title'             => $title,
				'type'              => 'text',
				'id'                => 'participation_' . $product,
				'default'           => '',
				'value'             => $this->get_setting( 'participation_' . $product, '' ),
				'custom_attributes' => array(
					'maxlength' => 14,
					'minlength' => 2,
				),
			);
		}

		$dhl_products[] = array(
			'title'             => _x( 'Inlay Returns', 'dhl', 'woocommerce-germanized' ),
			'type'              => 'text',
			'default'           => '',
			'id'                => 'participation_return',
			'value'             => $this->get_setting( 'participation_return', '' ),
			'custom_attributes' => array( 'maxlength' => '2' ),
		);

		$settings = array_merge( $settings, $dhl_products );

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'dhl_product_options',
				),
				array(
					'title' => _x( 'Tracking', 'dhl', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'tracking_options',
				),
			)
		);

		$general_settings = parent::get_general_settings( $for_shipping_method );

		return array_merge( $settings, $general_settings );
	}

	protected function get_pickup_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title'          => '',
				'type'           => 'title',
				'id'             => 'dhl_pickup_options',
				'allow_override' => true,
			),

			array(
				'title'    => _x( 'Packstation', 'dhl', 'woocommerce-germanized' ),
				'desc'     => _x( 'Enable delivery to Packstation.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Let customers choose a Packstation as delivery address.', 'dhl', 'woocommerce-germanized' ),
				'id'       => 'parcel_pickup_packstation_enable',
				'value'    => wc_bool_to_string( $this->get_setting( 'parcel_pickup_packstation_enable' ) ),
				'default'  => 'yes',
				'type'     => 'gzd_toggle',
			),

			array(
				'title'    => _x( 'Postoffice', 'dhl', 'woocommerce-germanized' ),
				'desc'     => _x( 'Enable delivery to Post Offices.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Let customers choose a Post Office as delivery address.', 'dhl', 'woocommerce-germanized' ),
				'id'       => 'parcel_pickup_postoffice_enable',
				'value'    => wc_bool_to_string( $this->get_setting( 'parcel_pickup_postoffice_enable' ) ),
				'default'  => 'yes',
				'type'     => 'gzd_toggle',
			),

			array(
				'title'    => _x( 'Parcel Shop', 'dhl', 'woocommerce-germanized' ),
				'desc'     => _x( 'Enable delivery to Parcel Shops.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Let customers choose a Parcel Shop as delivery address.', 'dhl', 'woocommerce-germanized' ),
				'id'       => 'parcel_pickup_parcelshop_enable',
				'value'    => wc_bool_to_string( $this->get_setting( 'parcel_pickup_parcelshop_enable' ) ),
				'default'  => 'yes',
				'type'     => 'gzd_toggle',
			),

			array(
				'title'          => _x( 'Map', 'dhl', 'woocommerce-germanized' ),
				'desc'           => _x( 'Let customers find a DHL location on a map.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'Enable this option to let your customers choose a pickup option from a map within the checkout. If this option is disabled a link to the DHL website is placed instead.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'             => 'parcel_pickup_map_enable',
				'value'          => wc_bool_to_string( $this->get_setting( 'parcel_pickup_map_enable' ) ),
				'default'        => 'no',
				'type'           => 'gzd_toggle',
				'allow_override' => false,
			),

			array(
				'title'             => _x( 'Google Maps Key', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'password',
				'id'                => 'parcel_pickup_map_api_password',
				'custom_attributes' => array( 'data-show_if_parcel_pickup_map_enable' => '' ),
				'value'             => $this->get_setting( 'parcel_pickup_map_api_password' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'To integrate a map within your checkout you\'ll need a valid API key for Google Maps. You may %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">' . _x( 'retrieve a new one', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'default'           => '',
				'allow_override'    => false,
			),

			array(
				'title'             => _x( 'Limit results', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'number',
				'id'                => 'parcel_pickup_map_max_results',
				'custom_attributes' => array( 'data-show_if_parcel_pickup_map_enable' => '' ),
				'value'             => $this->get_setting( 'parcel_pickup_map_max_results' ),
				'desc_tip'          => _x( 'Limit the number of DHL locations shown on the map', 'dhl', 'woocommerce-germanized' ),
				'default'           => 20,
				'css'               => 'max-width: 60px;',
				'allow_override'    => false,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'dhl_pickup_options',
			),
		);

		return $settings;
	}

	protected function get_preferred_settings( $for_shipping_method = false ) {
		$wc_gateway_titles = array();

		/**
		 * Calling  WC()->payment_gateways()->payment_gateways() could potentially lead to problems
		 * in case the shipping methods are being loaded from within a gateway constructor (e.g. WC Cash on Pickup)
		 */
		if ( ! $for_shipping_method && function_exists( 'WC' ) && WC()->payment_gateways() ) {
			$wc_payment_gateways = WC()->payment_gateways()->payment_gateways();
			$wc_gateway_titles   = wp_list_pluck( $wc_payment_gateways, 'method_title', 'id' );
		}

		$settings = array(
			array(
				'title'          => '',
				'type'           => 'title',
				'id'             => 'preferred_options',
				'allow_override' => true,
			),

			array(
				'title'   => _x( 'Drop-off location', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Enable drop-off location delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'Enabling this option will display options for the user to select their preferred delivery location during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'PreferredLocation_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredLocation_enable' ) ),
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),

			array(
				'title'   => _x( 'Neighbor', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Enable delivery to a neighbor.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'Enabling this option will display options for the user to deliver to their preferred neighbor during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'PreferredNeighbour_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredNeighbour_enable' ) ),
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),

			array(
				'title'   => _x( 'Delivery Type (CDP)', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Allow your international customers to choose between home and closest droppoint delivery. ', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Display options for the user to select their preferred delivery type during checkout. Currently available for <a href="%s">certain countries only</a>.', 'dhl', 'woocommerce-germanized' ), esc_url( 'https://www.dhl.de/de/geschaeftskunden/paket/leistungen-und-services/internationaler-versand/paket-international.html' ) ) . '</div>',
				'id'      => 'PreferredDeliveryType_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredDeliveryType_enable' ) ),
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Default Delivery Type', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'desc'              => _x( 'Select the default delivery type presented to the customer during checkout.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => 'preferred_default_delivery_type',
				'value'             => $this->get_setting( 'preferred_default_delivery_type' ),
				'options'           => ParcelServices::get_preferred_delivery_types(),
				'default'           => 'shop',
				'class'             => 'wc-enhanced-select',
				'custom_attributes' => array( 'data-show_if_PreferredDeliveryType_enable' => '' ),
			),

			array(
				'title'             => _x( 'Home Delivery Fee', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => _x( 'Insert gross value as surcharge for home deliveries for countries which support closest droppoint deliveries. Insert 0 to offer service for free.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => 'preferred_home_delivery_cost',
				'value'             => wc_format_localized_decimal( $this->get_setting( 'preferred_home_delivery_cost' ) ),
				'default'           => '0',
				'css'               => 'max-width: 60px;',
				'class'             => 'wc_input_decimal',
				'custom_attributes' => array( 'data-show_if_PreferredDeliveryType_enable' => '' ),
			),

			array(
				'title'   => _x( 'Delivery day', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Enable delivery day delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'Enabling this option will display options for the user to select their delivery day of delivery during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'PreferredDay_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredDay_enable' ) ),
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Fee', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => _x( 'Insert gross value as surcharge for delivery day delivery. Insert 0 to offer service for free.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => 'PreferredDay_cost',
				'value'             => wc_format_localized_decimal( $this->get_setting( 'PreferredDay_cost' ) ),
				'default'           => '1.2',
				'css'               => 'max-width: 60px;',
				'class'             => 'wc_input_decimal',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'title'             => _x( 'Cut-off time', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'time',
				'id'                => 'PreferredDay_cutoff_time',
				'allow_override'    => false,
				'value'             => $this->get_setting( 'PreferredDay_cutoff_time' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'The cut-off time is the latest possible order time up to which the minimum delivery day (day of order + 2 working days) can be guaranteed. As soon as the time is exceeded, the earliest delivery day displayed in the frontend will be shifted to one day later (day of order + 3 working days).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'default'           => '12:00',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'title'             => _x( 'Preparation days', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'number',
				'id'                => 'PreferredDay_preparation_days',
				'allow_override'    => false,
				'value'             => $this->get_setting( 'PreferredDay_preparation_days' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'If you need more time to prepare your shipments you might want to add a static preparation time to the possible starting date for delivery day delivery.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'default'           => '0',
				'css'               => 'max-width: 60px',
				'custom_attributes' => array(
					'data-show_if_PreferredDay_enable' => '',
					'min'                              => 0,
					'max'                              => 3,
				),
			),

			array(
				'title'             => _x( 'Exclude days of transfer', 'dhl', 'woocommerce-germanized' ),
				'desc'              => _x( 'Monday', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Exclude days from transferring shipments to DHL.', 'dhl', 'woocommerce-germanized' ),
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_mon' ) ),
				'id'                => 'PreferredDay_exclusion_mon',
				'allow_override'    => false,
				'type'              => 'gzd_toggle',
				'default'           => 'no',
				'checkboxgroup'     => 'start',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Tuesday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_tue',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_tue' ) ),
				'type'              => 'gzd_toggle',
				'allow_override'    => false,
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Wednesday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_wed',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_wed' ) ),
				'type'              => 'gzd_toggle',
				'allow_override'    => false,
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Thursday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_thu',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_thu' ) ),
				'type'              => 'gzd_toggle',
				'allow_override'    => false,
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Friday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_fri',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_fri' ) ),
				'type'              => 'gzd_toggle',
				'allow_override'    => false,
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Saturday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_sat',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_sat' ) ),
				'type'              => 'gzd_toggle',
				'allow_override'    => false,
				'default'           => 'no',
				'checkboxgroup'     => 'end',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'title'          => _x( 'Exclude gateways', 'dhl', 'woocommerce-germanized' ),
				'type'           => 'multiselect',
				'desc'           => _x( 'Select payment gateways to be excluded from showing preferred services.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'       => true,
				'allow_override' => false,
				'id'             => 'preferred_payment_gateways_excluded',
				'value'          => $this->get_setting( 'preferred_payment_gateways_excluded' ),
				'options'        => $wc_gateway_titles,
				'class'          => 'wc-enhanced-select',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'preferred_options',
			),
		);

		return $settings;
	}

	protected function get_label_settings( $for_shipping_method = false ) {
		$select_dhl_product_dom = wc_gzd_dhl_get_products_domestic();
		$select_dhl_product_int = wc_gzd_dhl_get_products_international();
		$select_dhl_product_eu  = wc_gzd_dhl_get_products_eu();
		$duties                 = wc_gzd_dhl_get_duties();
		$ref_placeholders       = wc_gzd_dhl_get_label_payment_ref_placeholder();
		$ref_placeholders_str   = implode( ', ', array_keys( $ref_placeholders ) );

		$settings = array(
			array(
				'title'          => '',
				'title_method'   => _x( 'Products', 'dhl', 'woocommerce-germanized' ),
				'type'           => 'title',
				'id'             => 'shipping_provider_dhl_label_options',
				'allow_override' => true,
			),

			array(
				'title'   => _x( 'Domestic Default Service', 'dhl', 'woocommerce-germanized' ),
				'type'    => 'select',
				'id'      => 'label_default_product_dom',
				'default' => 'V01PAK',
				'value'   => $this->get_setting( 'label_default_product_dom', 'V01PAK' ),
				'desc'    => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default DHL shipping service for domestic shipments that you want to offer to your customers (you can always change this within each individual shipment afterwards).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'options' => $select_dhl_product_dom,
				'class'   => 'wc-enhanced-select',
			),

			array(
				'title'   => _x( 'EU Default Service', 'dhl', 'woocommerce-germanized' ),
				'type'    => 'select',
				'default' => 'V53WPAK',
				'value'   => $this->get_setting( 'label_default_product_eu', 'V53WPAK' ),
				'id'      => 'label_default_product_eu',
				'desc'    => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default DHL shipping service for EU shipments that you want to offer to your customers (you can always change this within each individual shipment afterwards).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'options' => $select_dhl_product_eu,
				'class'   => 'wc-enhanced-select',
			),

			array(
				'title'   => _x( 'Int. Default Service', 'dhl', 'woocommerce-germanized' ),
				'type'    => 'select',
				'default' => 'V53WPAK',
				'value'   => $this->get_setting( 'label_default_product_int', 'V53WPAK' ),
				'id'      => 'label_default_product_int',
				'desc'    => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default DHL shipping service for cross-border shipments that you want to offer to your customers (you can always change this within each individual shipment afterwards).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'options' => $select_dhl_product_int,
				'class'   => 'wc-enhanced-select',
			),

			array(
				'title'    => _x( 'Default Duty', 'dhl', 'woocommerce-germanized' ),
				'type'     => 'select',
				'default'  => 'DDP',
				'id'       => 'label_default_duty',
				'value'    => $this->get_setting( 'label_default_duty', 'DDP' ),
				'desc'     => _x( 'Please select a default duty type.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'options'  => $duties,
				'class'    => 'wc-enhanced-select',
			),

			array(
				'title'          => _x( 'Codeable', 'dhl', 'woocommerce-germanized' ),
				'desc'           => _x( 'Generate label only if address can be automatically retrieved DHL.', 'dhl', 'woocommerce-germanized' ),
				'id'             => 'label_address_codeable_only',
				'value'          => $this->get_setting( 'label_address_codeable_only', 'no' ),
				'default'        => 'no',
				'type'           => 'gzd_toggle',
				'allow_override' => false,
				'desc_tip'       => _x( 'Choose this option if you want to make sure that by default labels are only generated for codeable addresses.', 'dhl', 'woocommerce-germanized' ),
			),

			array(
				'title'          => _x( 'Force email', 'dhl', 'woocommerce-germanized' ),
				'desc'           => _x( 'Force transferring customer email to DHL.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'By default the customer email address is only transferred in case explicit consent has been given via a checkbox during checkout. You may force to transfer the customer email address during label creation to make sure your customers receive <a href="%s" target="_blank" rel="noopener noreferrer">email notifications by DHL</a>. Make sure to check your privacy policy and seek advice by a lawyer in case of doubt.', 'dhl', 'woocommerce-germanized' ), 'https://www.dhl.de/de/geschaeftskunden/paket/versandsoftware/dhl-paketankuendigung/formular.html' ) . '</div>',
				'id'             => 'label_force_email_transfer',
				'value'          => $this->get_setting( 'label_force_email_transfer', 'no' ),
				'default'        => 'no',
				'allow_override' => false,
				'type'           => 'gzd_toggle',
			),

			array(
				'title'          => _x( 'Custom shipper', 'dhl', 'woocommerce-germanized' ),
				'desc'           => _x( 'Use a custom shipper address managed within your DHL business profile.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Choose this option if you want to use a <a href="%s" target="_blank">custom address</a> profile managed within your DHL business profile as shipper reference for your labels.', 'dhl', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/dhl-integration-einrichten#individuelle-absenderreferenz-samt-logo-nutzen' ) . '</div>',
				'id'             => 'label_use_custom_shipper',
				'value'          => $this->get_setting( 'label_use_custom_shipper', 'no' ),
				'default'        => 'no',
				'allow_override' => false,
				'type'           => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Shipper reference', 'dhl', 'woocommerce-germanized' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Insert the <a href="%s" target="_blank">address reference</a> you have chosen within the DHL business portal for your custom shipper address.', 'dhl', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/dhl-integration-einrichten#individuelle-absenderreferenz-samt-logo-nutzen' ) . '</div>',
				'id'                => 'label_custom_shipper_reference',
				'value'             => $this->get_setting( 'label_custom_shipper_reference', '' ),
				'default'           => '',
				'allow_override'    => false,
				'type'              => 'text',
				'custom_attributes' => array( 'data-show_if_label_use_custom_shipper' => 'yes' ),
			),

			array(
				'title'   => _x( 'Inlay Returns', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Additionally create inlay return labels for shipments that support returns.', 'dhl', 'woocommerce-germanized' ),
				'id'      => 'label_auto_inlay_return_label',
				'value'   => $this->get_setting( 'label_auto_inlay_return_label', 'no' ),
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'shipping_provider_dhl_label_options',
			),
		);

		$settings = array_merge( $settings, parent::get_label_settings( $for_shipping_method ) );

		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => _x( 'Retoure', 'dhl', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'dhl_retoure_options',
					'desc'  => sprintf( _x( 'Adjust handling of return shipments through the DHL Retoure API. Make sure that your %s contains DHL Retoure Online.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '">' . _x( 'contract', 'dhl', 'woocommerce-germanized' ) . '</a>' ),
				),

				array(
					'title'   => _x( 'Retoure', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Create retoure labels to return shipments.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'By enabling this option you might generate retoure labels for return shipments and send them to your customer via email.', 'dhl', 'woocommerce-germanized' ) . '</div>',
					'id'      => 'label_retoure_enable',
					'value'   => wc_bool_to_string( $this->enable_retoure() ),
					'default' => 'yes',
					'type'    => 'gzd_toggle',
				),

				array(
					'type'    => 'dhl_receiver_ids',
					'value'   => $this->get_setting( 'retoure_receiver_ids', array() ),
					'id'      => 'retoure_receiver_ids',
					'default' => array(),
				),

				array(
					'type' => 'sectionend',
					'id'   => 'dhl_retoure_options',
				),

				array(
					'title'          => _x( 'Default Services', 'dhl', 'woocommerce-germanized' ),
					'allow_override' => true,
					'type'           => 'title',
					'id'             => 'dhl_label_default_services_options',
					'desc'           => sprintf( _x( 'Adjust services to be added to your labels by default. Find out more about these <a href="%s" target="_blank">services</a>.', 'dhl', 'woocommerce-germanized' ), 'https://www.dhl.de/de/geschaeftskunden/paket/leistungen-und-services/services/service-loesungen.html' ),
				),
				array(
					'title'   => _x( 'GoGreen', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Enable the GoGreen Service by default.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_GoGreen',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_GoGreen', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'Additional Insurance', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Add an additional insurance to labels.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_AdditionalInsurance',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_AdditionalInsurance', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'Retail Outlet Routing', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Send undeliverable items to nearest retail outlet instead of immediate return.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_ParcelOutletRouting',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_ParcelOutletRouting', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'No Neighbor', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Do not deliver to neighbors.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_NoNeighbourDelivery',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_NoNeighbourDelivery', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'Named person only', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Do only delivery to named person.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_NamedPersonOnly',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_NamedPersonOnly', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'Bulky Goods', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Deliver as bulky goods.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_BulkyGoods',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_BulkyGoods', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'    => _x( 'Minimum age (Visual check)', 'dhl', 'woocommerce-germanized' ),
					'id'       => 'label_visual_min_age',
					'type'     => 'select',
					'default'  => '0',
					'value'    => $this->get_setting( 'label_visual_min_age', '0' ),
					'options'  => wc_gzd_dhl_get_visual_min_ages(),
					'desc_tip' => _x( 'Choose this option if you want to let DHL check your customer\'s age.', 'dhl', 'woocommerce-germanized' ),
				),
				array(
					'title'   => _x( 'Sync (Visual Check)', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Visually verify age if shipment contains applicable items.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Germanized offers an %s to be enabled for certain products and/or product categories. By checking this option labels for shipments with applicable items will automatically have the visual age check service enabled.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=age_verification' ) ) . '">' . _x( 'age verification checkbox', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
					'id'      => 'label_auto_age_check_sync',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_auto_age_check_sync', 'yes' ) ),
					'default' => 'yes',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'    => _x( 'Minimum age (Ident check)', 'dhl', 'woocommerce-germanized' ),
					'id'       => 'label_ident_min_age',
					'type'     => 'select',
					'default'  => '0',
					'value'    => $this->get_setting( 'label_ident_min_age', '0' ),
					'options'  => wc_gzd_dhl_get_ident_min_ages(),
					'desc_tip' => _x( 'Choose this option if you want to let DHL check your customer\'s identity and age.', 'dhl', 'woocommerce-germanized' ),
				),
				array(
					'title'   => _x( 'Sync (Ident Check)', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Verify identity and age if shipment contains applicable items.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Germanized offers an %s to be enabled for certain products and/or product categories. By checking this option labels for shipments with applicable items will automatically have the identity check service enabled.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=age_verification' ) ) . '">' . _x( 'age verification checkbox', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
					'id'      => 'label_auto_age_check_ident_sync',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_auto_age_check_ident_sync', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'Premium', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Premium delivery for international shipments.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_Premium',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_Premium', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'Economy', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'Economy delivery for international shipments.', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_Economy',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_Economy', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'title'   => _x( 'PDDP', 'dhl', 'woocommerce-germanized' ),
					'desc'    => _x( 'DHL takes care of customs clearance and export duties (Postal Delivered Duty Paid).', 'dhl', 'woocommerce-germanized' ),
					'id'      => 'label_service_PDDP',
					'value'   => wc_bool_to_string( $this->get_setting( 'label_service_PDDP', 'no' ) ),
					'default' => 'no',
					'type'    => 'gzd_toggle',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'dhl_label_default_services_options',
				),

				array(
					'title' => _x( 'Bank Account', 'dhl', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'dhl_bank_account_options',
					'desc'  => _x( 'Enter your bank details needed for services that use COD.', 'dhl', 'woocommerce-germanized' ),
				),

				array(
					'title'   => _x( 'Holder', 'dhl', 'woocommerce-germanized' ),
					'type'    => 'text',
					'id'      => 'bank_holder',
					'value'   => $this->get_setting( 'bank_holder' ),
					'default' => Package::get_default_bank_account_data( 'name' ),
				),

				array(
					'title'   => _x( 'Bank Name', 'dhl', 'woocommerce-germanized' ),
					'type'    => 'text',
					'id'      => 'bank_name',
					'value'   => $this->get_setting( 'bank_name' ),
					'default' => Package::get_default_bank_account_data( 'bank_name' ),
				),

				array(
					'title'   => _x( 'IBAN', 'dhl', 'woocommerce-germanized' ),
					'type'    => 'text',
					'id'      => 'bank_iban',
					'value'   => $this->get_setting( 'bank_iban' ),
					'default' => Package::get_default_bank_account_data( 'iban' ),
				),

				array(
					'title'   => _x( 'BIC', 'dhl', 'woocommerce-germanized' ),
					'type'    => 'text',
					'id'      => 'bank_bic',
					'value'   => $this->get_setting( 'bank_bic' ),
					'default' => Package::get_default_bank_account_data( 'bic' ),
				),

				array(
					'title'             => _x( 'Payment Reference', 'dhl', 'woocommerce-germanized' ),
					'type'              => 'text',
					'id'                => 'bank_ref',
					'custom_attributes' => array( 'maxlength' => '35' ),
					'value'             => $this->get_setting( 'bank_ref' ),
					'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Use these placeholders to add info to the payment reference: %s. This text is limited to 35 characters.', 'dhl', 'woocommerce-germanized' ), '<code>' . esc_html( $ref_placeholders_str ) . '</code>' ) . '</div>',
					'default'           => '{shipment_id}',
				),

				array(
					'title'             => _x( 'Payment Reference 2', 'dhl', 'woocommerce-germanized' ),
					'type'              => 'text',
					'id'                => 'bank_ref_2',
					'custom_attributes' => array( 'maxlength' => '35' ),
					'value'             => $this->get_setting( 'bank_ref_2' ),
					'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Use these placeholders to add info to the payment reference: %s. This text is limited to 35 characters.', 'dhl', 'woocommerce-germanized' ), '<code>' . esc_html( $ref_placeholders_str ) . '</code>' ) . '</div>',
					'default'           => '{email}',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'dhl_bank_account_options',
				),
			)
		);

		return $settings;
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokument/dhl-integration-einrichten';
	}

	public function get_signup_link() {
		return 'https://www.dhl.de/dhl-kundewerden?source=woocommercegermanized&cid=c_dhloka_de_woocommercegermanized';
	}

	public function get_settings_help_pointers( $section = '' ) {
		$pointers = array();

		if ( '' === $section ) {
			$pointers = array(
				'pointers' => array(
					'account' => array(
						'target'       => '#account_number',
						'next'         => 'api',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Customer Number', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Insert your DHL business customer number (EKP) here. If you are not yet a business customer you might want to create a new account first.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'api'     => array(
						'target'       => Package::is_debug_mode() ? '#api_sandbox_username' : '#api_username',
						'next'         => '',
						'next_url'     => add_query_arg( array( 'tutorial' => 'yes' ), $this->get_edit_link( 'label' ) ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'API Access', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'To create labels and embed DHL services, our software needs access to the API. You will need to fill out the username and password fields accordingly.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'label' === $section ) {
			$pointers = array(
				'pointers' => array(
					'inlay'     => array(
						'target'       => '#label_auto_inlay_return_label-toggle',
						'next'         => 'retoure',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Inlay Returns', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'If you want to provide your customers with inlay return labels for your shipments you might enable this feature by default here.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'retoure'   => array(
						'target'       => '#label_retoure_enable-toggle',
						'next'         => 'age_check',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Retoure', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'If you want to create DHL labels to returns you should activate this feature. Make sure that you have DHL Online Retoure activated in your contract.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'age_check' => array(
						'target'       => '#label_auto_age_check_sync-toggle',
						'next'         => '',
						'next_url'     => add_query_arg( array( 'tutorial' => 'yes' ), $this->get_edit_link( 'automation' ) ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Age verification', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Use this feature to sync the Germanized age verification checkbox with the DHL visual minimum age verification service. As soon as applicable products are contained within the shipment, the service will be booked by default.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'automation' === $section ) {
			$pointers = array(
				'pointers' => array(
					'auto' => array(
						'target'       => '#label_auto_enable-toggle',
						'next'         => '',
						'next_url'     => add_query_arg( array( 'tutorial' => 'yes' ), $this->get_edit_link( 'preferred' ) ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Automation', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'You might want to save some time and let Germanized generate labels automatically as soon as a shipment switches to a certain status.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'preferred' === $section ) {
			$pointers = array(
				'pointers' => array(
					'day'      => array(
						'target'       => '#PreferredDay_enable-toggle',
						'next'         => 'fee',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Delivery day', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Let your customers choose a delivery day (if the service is available at the customer\'s location) of delivery within your checkout.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'fee'      => array(
						'target'       => '#PreferredDay_cost',
						'next'         => 'location',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Fee', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Optionally charge your customers an additional fee for preferred services like delivery day.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'location' => array(
						'target'       => '#PreferredLocation_enable-toggle',
						'next'         => '',
						'next_url'     => add_query_arg( array( 'tutorial' => 'yes' ), $this->get_edit_link( 'pickup' ) ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Drop-off location', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Allow your customers to send their parcels to a drop-off location e.g. a neighbor. This service is free of charge for DHL shipments.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'pickup' === $section ) {
			$pointers = array(
				'pointers' => array(
					'day' => array(
						'target'       => '#parcel_pickup_packstation_enable-toggle',
						'next'         => 'map',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Packstation', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Allow your customers to choose packstation (and/or other DHL location types as configured below) as shipping address.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'map' => array(
						'target'       => '#parcel_pickup_map_enable-toggle',
						'next'         => '',
						'next_url'     => ProviderSettings::get_next_pointers_link( $this->get_name() ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Map', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'This option adds a map overlay view to let your customers choose a DHL location from a map nearby. You\'ll need a valid Google Maps API key to enable the map view.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}
}
