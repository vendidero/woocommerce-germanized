<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\DHL\ShippingProvider;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\ParcelLocator;
use Vendidero\Shiptastic\DHL\ParcelServices;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\AdditionalInsurance;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\CashOnDelivery;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\ClosestDropPoint;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\DHLRetoure;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\IdentCheck;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\PreferredDay;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\PreferredLocation;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\PreferredNeighbour;
use Vendidero\Shiptastic\DHL\ShippingProvider\Services\VisualCheckOfAge;
use Vendidero\Shiptastic\Admin\ProviderSettings;
use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Admin\Tutorial;
use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Auto;

defined( 'ABSPATH' ) || exit;

class DHL extends Auto {

	use PickupDeliveryTrait;

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
			return '\Vendidero\Shiptastic\DHL\Label\DHLReturn';
		} elseif ( 'inlay_return' === $type ) {
			return '\Vendidero\Shiptastic\DHL\Label\DHLInlayReturn';
		} else {
			return '\Vendidero\Shiptastic\DHL\Label\DHL';
		}
	}

	public function get_supported_label_reference_types( $shipment_type = 'simple' ) {
		$reference_types = array();

		if ( 'simple' === $shipment_type ) {
			$reference_types = array(
				'ref_1' => array(
					'label'      => _x( 'Reference 1', 'dhl', 'woocommerce-germanized' ),
					'default'    => _x( '#{shipment_number}, order {order_number}', 'dhl', 'woocommerce-germanized' ),
					'max_length' => 35,
				),
				'inlay' => array(
					'label'      => _x( 'Inlay return reference', 'dhl', 'woocommerce-germanized' ),
					'default'    => _x( 'Return #{shipment_number}, order {order_number}', 'dhl', 'woocommerce-germanized' ),
					'max_length' => 35,
				),
			);
		} elseif ( 'return' === $shipment_type ) {
			$reference_types = array(
				'ref_1' => array(
					'label'      => _x( 'Reference 1', 'dhl', 'woocommerce-germanized' ),
					'default'    => _x( 'Return #{shipment_number}, order {order_number}', 'dhl', 'woocommerce-germanized' ),
					'max_length' => -1,
				),
			);
		}

		return $reference_types;
	}

	public function supports_labels( $label_type, $shipment = false ) {
		$label_types = array( 'simple' );

		if ( $this->enable_retoure() ) {
			$label_types[] = 'return';
		}

		return in_array( $label_type, $label_types, true );
	}

	protected function register_products() {
		$this->register_product(
			'V01PAK',
			array(
				'label'     => _x( 'DHL Paket', 'dhl', 'woocommerce-germanized' ),
				'countries' => array( 'DE' ),
				'zones'     => array( 'dom' ),
			)
		);

		$this->register_product(
			'V01PRIO',
			array(
				'label'     => _x( 'DHL Paket PRIO', 'dhl', 'woocommerce-germanized' ),
				'countries' => array( 'DE' ),
				'zones'     => array( 'dom' ),
			)
		);

		$this->register_product(
			'V06PAK',
			array(
				'label'     => _x( 'DHL Paket Taggleich', 'dhl', 'woocommerce-germanized' ),
				'countries' => array( 'DE' ),
				'zones'     => array( 'dom' ),
			)
		);

		$this->register_product(
			'V62WP',
			array(
				'label'     => _x( 'DHL Warenpost', 'dhl', 'woocommerce-germanized' ),
				'countries' => array( 'DE' ),
				'zones'     => array( 'dom' ),
			)
		);

		$this->register_product(
			'V62KP',
			array(
				'label'     => _x( 'DHL Kleinpaket', 'dhl', 'woocommerce-germanized' ),
				'countries' => array( 'DE' ),
				'zones'     => array( 'dom' ),
			)
		);

		$this->register_product(
			'V66WPI',
			array(
				'label' => _x( 'DHL Warenpost International', 'dhl', 'woocommerce-germanized' ),
				'zones' => array( 'eu', 'int' ),
			)
		);

		$this->register_product(
			'V54EPAK',
			array(
				'label' => _x( 'DHL Europaket (B2B)', 'dhl', 'woocommerce-germanized' ),
				'zones' => array( 'eu' ),
			)
		);

		$this->register_product(
			'V55PAK',
			array(
				'label' => _x( 'DHL Paket Connect', 'dhl', 'woocommerce-germanized' ),
				'zones' => array( 'eu' ),
			)
		);

		$this->register_product(
			'V53WPAK',
			array(
				'label' => _x( 'DHL Paket International', 'dhl', 'woocommerce-germanized' ),
				'zones' => array( 'eu', 'int' ),
			)
		);
	}

	protected function register_services() {
		$this->register_service(
			'GoGreen',
			array(
				'label'       => _x( 'GoGreen', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Ship your parcels climate friendly.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V01PAK', 'V53WPAK', 'V54EPAK', 'V62WP', 'V66WPI', 'V62KP' ),
			)
		);

		$this->register_service(
			'GoGreenPlus',
			array(
				'label'          => _x( 'GoGreen Plus', 'dhl', 'woocommerce-germanized' ),
				'description'    => _x( 'Ship your parcels climate friendly.', 'dhl', 'woocommerce-germanized' ),
				'shipment_types' => array( 'simple', 'return' ),
				'countries'      => array( 'DE' ),
				'zones'          => array( 'dom' ),
			)
		);

		$this->register_service( new PreferredLocation( $this ) );
		$this->register_service( new PreferredNeighbour( $this ) );
		$this->register_service( new PreferredDay( $this ) );
		$this->register_service( new VisualCheckOfAge( $this ) );
		$this->register_service( new IdentCheck( $this ) );
		$this->register_service( new DHLRetoure( $this ) );

		$this->register_service(
			'NoNeighbourDelivery',
			array(
				'label'       => _x( 'No Neighbor', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Do not deliver to neighbors.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V01PAK' ),
				'countries'   => array( 'DE' ),
				'zones'       => array( 'dom' ),
			)
		);

		$this->register_service(
			'signedForByRecipient',
			array(
				'label'       => _x( 'Recipient signature', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Let recipients sign delivery instead of DHL driver.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V01PAK' ),
				'countries'   => array( 'DE' ),
				'zones'       => array( 'dom' ),
			)
		);

		$this->register_service(
			'NamedPersonOnly',
			array(
				'label'       => _x( 'Named person only', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Do only delivery to named person.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V01PAK' ),
				'countries'   => array( 'DE' ),
				'zones'       => array( 'dom' ),
			)
		);

		$this->register_service( new AdditionalInsurance( $this ) );

		$this->register_service(
			'BulkyGoods',
			array(
				'label'       => _x( 'Bulky Goods', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Deliver as bulky goods.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V01PAK', 'V53WPAK', 'V54EPAK' ),
			)
		);

		$this->register_service(
			'ParcelOutletRouting',
			array(
				'label'       => _x( 'Retail Outlet Routing', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Send undeliverable items to nearest retail outlet instead of immediate return.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V01PAK', 'V62WP', 'V62KP' ),
			)
		);

		$this->register_service( new CashOnDelivery( $this ) );

		$this->register_service(
			'Premium',
			array(
				'label'       => _x( 'Premium', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Premium delivery for international shipments.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V53WPAK', 'V66WPI' ),
				'zones'       => array( 'int', 'eu' ),
			)
		);

		$this->register_service(
			'Endorsement',
			array(
				'label'         => _x( 'Endorsement', 'dhl', 'woocommerce-germanized' ),
				'description'   => _x( 'Select how DHL should handle international shipments that could not be delivered.', 'dhl', 'woocommerce-germanized' ),
				'option_type'   => 'select',
				'options'       => wc_stc_dhl_get_endorsement_types(),
				'default_value' => 'return',
				'products'      => array( 'V53WPAK' ),
				'zones'         => array( 'int', 'eu' ),
			)
		);

		$this->register_service(
			'Economy',
			array(
				'label'       => _x( 'Economy', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'Economy delivery for international shipments.', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V53WPAK', 'V66WPI' ),
				'zones'       => array( 'int', 'eu' ),
			)
		);

		$this->register_service( new ClosestDropPoint( $this ) );

		$this->register_service(
			'PostalDeliveryDutyPaid',
			array(
				'label'       => _x( 'Postal Delivery Duty Paid', 'dhl', 'woocommerce-germanized' ),
				'description' => _x( 'DHL takes care of customs clearance and export duties (Postal Delivered Duty Paid).', 'dhl', 'woocommerce-germanized' ),
				'products'    => array( 'V53WPAK' ),
				'countries'   => ParcelServices::get_pddp_countries(),
				'zones'       => array( 'int' ),
			)
		);
	}

	protected function register_print_formats() {
		$this->register_print_format(
			'default',
			array(
				'label'          => _x( 'Default (User configuration)', 'dhl-print-format', 'woocommerce-germanized' ),
				'shipment_types' => array( 'simple' ),
			)
		);

		$available = array(
			'A4'             => _x( 'A4', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-700'    => _x( '910-300-700', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-700-oZ' => _x( '910-300-700-oZ', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-710'    => _x( '910-300-710', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-600'    => _x( '910-300-600', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-610'    => _x( '910-300-610', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-400'    => _x( '910-300-400', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-410'    => _x( '910-300-410', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-300'    => _x( '910-300-300', 'dhl-print-format', 'woocommerce-germanized' ),
			'910-300-300-oz' => _x( '910-300-300-oz', 'dhl-print-format', 'woocommerce-germanized' ),
		);

		foreach ( $available as $print_format => $label ) {
			$this->register_print_format(
				$print_format,
				array(
					'label'          => $label,
					'shipment_types' => array( 'simple' ),
				)
			);
		}

		$this->register_print_format(
			'100x70mm',
			array(
				'label'          => _x( '100x70mm', 'dhl-print-format', 'woocommerce-germanized' ),
				'products'       => array( 'V62WP', 'V66WPI', 'V62KP' ),
				'shipment_types' => array( 'simple' ),
			)
		);
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return string
	 */
	protected function get_default_product_for_zone( $configuration_set ) {
		$default = parent::get_default_product_for_zone( $configuration_set );

		if ( 'simple' === $configuration_set->get_shipment_type() ) {
			if ( 'dom' === $configuration_set->get_zone() ) {
				return 'V01PAK';
			} elseif ( 'eu' === $configuration_set->get_zone() ) {
				return 'V53WPAK';
			} elseif ( 'int' === $configuration_set->get_zone() ) {
				return 'V53WPAK';
			}
		}

		return $default;
	}

	protected function get_config_set_return_label_settings() {
		$settings = array(
			array(
				'title' => _x( 'Retoure', 'dhl', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'dhl_retoure_options',
				'desc'  => sprintf( _x( 'Adjust handling of return shipments through the DHL Retoure API. Make sure that your %s contains DHL Retoure Online.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '">' . _x( 'contract', 'dhl', 'woocommerce-germanized' ) . '</a>' ),
			),

			array(
				'title'   => _x( 'Retoure', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Create retoure labels to return shipments.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . _x( 'By enabling this option you might generate retoure labels for return shipments and send them to your customer via email.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'label_retoure_enable',
				'value'   => wc_bool_to_string( $this->enable_retoure() ),
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'type'         => 'dhl_receiver_ids',
				'value'        => $this->get_setting( 'retoure_receiver_ids', array() ),
				'id'           => 'retoure_receiver_ids',
				'settings_url' => $this->get_edit_link( 'label' ),
				'default'      => array(),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'dhl_retoure_options',
			),
		);

		$settings = array_merge( $settings, parent::get_config_set_return_label_settings() );

		return $settings;
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return mixed
	 */
	protected function get_label_settings_by_zone( $configuration_set ) {
		$settings = parent::get_label_settings_by_zone( $configuration_set );

		if ( 'shipping_provider' === $configuration_set->get_setting_type() ) {
			if ( 'dom' === $configuration_set->get_zone() && 'simple' === $configuration_set->get_shipment_type() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title'    => _x( 'Encodable', 'dhl', 'woocommerce-germanized' ),
							'desc'     => _x( 'Labels will only be created if an address is encodable by DHL.', 'dhl', 'woocommerce-germanized' ),
							'id'       => 'label_address_codeable_only',
							'value'    => $this->get_setting( 'label_address_codeable_only', 'no' ),
							'default'  => 'no',
							'type'     => 'shiptastic_toggle',
							'desc_tip' => _x( 'Choose this option if you want to make sure that by default labels are only generated for encodable addresses.', 'dhl', 'woocommerce-germanized' ),
						),

						array(
							'title'             => _x( 'Sync (IdentCheck)', 'dhl', 'woocommerce-germanized' ),
							'desc'              => _x( 'Verify identity and age if shipment contains applicable items.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Germanized offers an %s to be enabled for certain products and/or product categories. By checking this option labels for shipments with applicable items will automatically have the identity check service enabled.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=age_verification' ) ) . '">' . _x( 'age verification checkbox', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
							'id'                => 'label_auto_age_check_ident_sync',
							'value'             => wc_bool_to_string( $this->get_setting( 'label_auto_age_check_ident_sync', 'no' ) ),
							'default'           => 'no',
							'type'              => 'shiptastic_toggle',
							'custom_attributes' => array(
								'data-show_if_label_config_set_' . $configuration_set->get_id() . '-g-product-n-product' => 'V01PAK',
							),
						),

						array(
							'title'             => _x( 'Sync (Visual Check)', 'dhl', 'woocommerce-germanized' ),
							'desc'              => _x( 'Visually verify age if shipment contains applicable items.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Germanized offers an %s to be enabled for certain products and/or product categories. By checking this option labels for shipments with applicable items will automatically have the visual age check service enabled.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=age_verification' ) ) . '">' . _x( 'age verification checkbox', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
							'id'                => 'label_auto_age_check_sync',
							'value'             => wc_bool_to_string( $this->get_setting( 'label_auto_age_check_sync', 'yes' ) ),
							'default'           => 'yes',
							'type'              => 'shiptastic_toggle',
							'custom_attributes' => array(
								'data-show_if_label_config_set_' . $configuration_set->get_id() . '-g-product-n-product' => 'V01PAK',
							),
						),
					)
				);
			} elseif ( 'int' === $configuration_set->get_zone() && 'simple' === $configuration_set->get_shipment_type() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title'    => _x( 'Default Incoterms', 'dhl', 'woocommerce-germanized' ),
							'type'     => 'select',
							'default'  => 'DDP',
							'id'       => 'label_default_duty',
							'value'    => $this->get_setting( 'label_default_duty', 'DDP' ),
							'desc'     => _x( 'Please select a default incoterms option.', 'dhl', 'woocommerce-germanized' ),
							'desc_tip' => true,
							'options'  => wc_stc_dhl_get_duties(),
							'class'    => 'wc-enhanced-select',
						),
					)
				);
			}
		}

		return $settings;
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
		$sections              = parent::get_setting_sections();
		$sections['preferred'] = _x( 'Preferred delivery', 'dhl', 'woocommerce-germanized' );

		return $sections;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
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
				'options'     => wc_stc_dhl_get_return_receivers(),
				'value'       => isset( $default_args['receiver_slug'] ) ? $default_args['receiver_slug'] : '',
			),
		);
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_simple_label_fields( $shipment ) {
		$settings              = parent::get_simple_label_fields( $shipment );
		$default_args          = $this->get_default_label_props( $shipment );
		$service_supports_args = array(
			'shipment' => $shipment,
			'product'  => $default_args['product_id'],
		);

		if ( $shipment->is_shipping_international() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'duties',
						'label'       => _x( 'Duties', 'dhl', 'woocommerce-germanized' ),
						'description' => '',
						'value'       => isset( $default_args['duties'] ) ? $default_args['duties'] : '',
						'options'     => wc_stc_dhl_get_duties(),
						'type'        => 'select',
					),
				)
			);
		} elseif ( $shipment->is_shipping_domestic() ) {
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
		}

		if ( $this->get_service( 'dhlRetoure' )->supports( $service_supports_args ) ) {
			$settings = array_merge( $settings, $this->get_service( 'dhlRetoure' )->get_label_fields( $shipment ) );
		}

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

		if ( wc_stc_dhl_wp_error_has_errors( $error ) ) {
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
				'product_id' => '',
			)
		);

		$error = new \WP_Error();

		// We don't need duties for non-cross-border shipments
		if ( ! Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
			unset( $args['duties'] );
		} elseif ( ! empty( $args['duties'] ) && ! array_key_exists( $args['duties'], wc_stc_dhl_get_duties() ) ) {
				$error->add( 500, sprintf( _x( '%s duties element does not exist.', 'dhl', 'woocommerce-germanized' ), $args['duties'] ) );
		}

		if ( wc_stc_dhl_wp_error_has_errors( $error ) ) {
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
		$defaults = parent::get_default_label_props( $shipment );

		if ( 'return' === $shipment->get_type() ) {
			$defaults = $this->get_default_return_label_props( $shipment, $defaults );
		} else {
			$defaults = $this->get_default_simple_label_props( $shipment, $defaults );
		}

		return $defaults;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_return_label_props( $shipment, $defaults = array() ) {
		$defaults = wp_parse_args(
			$defaults,
			array(
				'services'      => array(),
				'receiver_slug' => wc_stc_dhl_get_default_return_receiver_slug( $shipment->get_sender_country() ),
			)
		);

		return $defaults;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return string
	 */
	protected function get_incoterms( $shipment ) {
		$incoterms = $this->get_setting( 'label_default_duty' );

		if ( ! empty( $shipment->get_incoterms() ) ) {
			if ( in_array( $shipment->get_incoterms(), array_keys( wc_stc_dhl_get_duties() ), true ) ) {
				$incoterms = $shipment->get_incoterms();
			}
		}

		return $incoterms;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_simple_label_props( $shipment, $defaults = array() ) {
		$dhl_order                   = wc_stc_dhl_get_order( $shipment->get_order() );
		$supports_email_transmission = $dhl_order && $dhl_order->supports_email_notification() ? true : false;
		$defaults                    = wp_parse_args(
			$defaults,
			array(
				'product_id' => '',
				'services'   => array(),
			)
		);

		if ( $shipment->is_shipping_domestic() ) {
			$defaults['codeable_address_only'] = wc_bool_to_string( $this->get_setting( 'label_address_codeable_only', 'no' ) );
		}

		if ( Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
			$defaults['duties'] = $this->get_incoterms( $shipment );

			/**
			 * Cash on delivery for Paket International is only available in combination with Premium
			 */
			if ( in_array( 'CashOnDelivery', $defaults['services'], true ) ) {
				$defaults['services'][] = 'Premium';
			}
		}

		/**
		 * Force home delivery if chosen
		 */
		if ( $this->get_service( 'ClosestDropPoint' )->supports(
			array(
				'shipment' => $shipment,
				'product'  => $defaults['product_id'],
			)
		) && $dhl_order && 'home' === $dhl_order->get_preferred_delivery_type() ) {
			$defaults['services'][] = 'Premium';
			$defaults['services']   = array_diff( $defaults['services'], array( 'ClosestDropPoint' ) );
		}

		/**
		 * Prevent certain service-combinations
		 */
		if ( in_array( 'ClosestDropPoint', $defaults['services'], true ) ) {
			$defaults['services'] = array_diff( $defaults['services'], array( 'Economy', 'Premium' ) );
		}

		if ( in_array( 'Premium', $defaults['services'], true ) ) {
			$defaults['services'] = array_diff( $defaults['services'], array( 'ClosestDropPoint', 'Economy' ) );
		}

		if ( in_array( 'Economy', $defaults['services'], true ) ) {
			$defaults['services'] = array_diff( $defaults['services'], array( 'ClosestDropPoint', 'Premium' ) );
		}

		// Remove duplicates
		$defaults['services'] = array_unique( $defaults['services'] );

		return $defaults;
	}

	protected function label_supports_export_reference_number( $shipment ) {
		return $shipment->is_shipping_international();
	}

	protected function get_available_base_countries() {
		return Package::get_available_countries();
	}

	public function test_connection() {
		$is_sandbox = wc_string_to_bool( $this->get_setting( 'sandbox_mode', 'no' ) );
		$username   = $is_sandbox ? $this->get_setting( 'api_sandbox_username', '' ) : $this->get_setting( 'api_username', '' );

		if ( ( $is_sandbox && Package::use_legacy_soap_api() && empty( $username ) ) || ( ! $is_sandbox && empty( $username ) ) ) {
			return null;
		}

		return Package::get_api()->test_connection();
	}

	protected function get_general_settings() {
		$ref_placeholders     = wc_stc_dhl_get_label_payment_ref_placeholder();
		$ref_placeholders_str = implode( ', ', array_keys( $ref_placeholders ) );
		$has_soap             = Package::supports_soap() ? true : false;

		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'dhl_general_options',
			),

			array(
				'title'             => _x( 'Customer Number (EKP)', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Your 10 digits DHL customer number, also called "EKP". Find your %s in the DHL business portal.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '" target="_blank">' . _x( 'customer number', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
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
				'desc'  => '',
			),
		);

		if ( $has_soap ) {
			$use_soap = ( defined( 'WC_STC_DHL_LEGACY_SOAP' ) ? WC_STC_DHL_LEGACY_SOAP : ( 'yes' === get_option( 'woocommerce_stc_dhl_enable_legacy_soap' ) ) );

			$settings = array_merge(
				$settings,
				array(
					array(
						'title'   => _x( 'API Type', 'dhl', 'woocommerce-germanized' ),
						'desc'    => _x( 'Choose the DHL API to use. Please note: The SOAP API is currently in a legacy mode and will be replaced by the newer REST API in the future.', 'dhl', 'woocommerce-germanized' ),
						'id'      => 'api_type',
						'options' => array(
							'rest' => _x( 'New API (REST)', 'dhl', 'woocommerce-germanized' ),
							'soap' => _x( 'Old API (SOAP)', 'dhl', 'woocommerce-germanized' ),
						),
						'default' => $use_soap ? 'soap' : 'rest',
						'type'    => 'select',
						'value'   => $this->get_setting( 'api_type', '' ),
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => _x( 'Enable Sandbox', 'dhl', 'woocommerce-germanized' ),
					'desc'  => _x( 'Activate Sandbox mode for testing purposes.', 'dhl', 'woocommerce-germanized' ),
					'id'    => 'sandbox_mode',
					'value' => wc_bool_to_string( $this->get_setting( 'sandbox_mode', 'no' ) ),
					'type'  => 'shiptastic_toggle',
				),

				array(
					'title'             => _x( 'Live Username', 'dhl', 'woocommerce-germanized' ),
					'type'              => 'text',
					'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Your username (<strong>not</strong> your email address) to the DHL business customer portal. Please make sure to test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
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
					'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Your password to the DHL business customer portal. Please note the new assignment of the password to 3 (Standard User) or 12 (System User) months and make sure to test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( Package::get_geschaeftskunden_portal_url() ) . '" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
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
					'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Your username (<strong>not</strong> your email address) to the DHL developer portal. Please make sure to test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://entwickler.dhl.de" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
					'id'                => 'api_sandbox_username',
					'value'             => $this->get_setting( 'api_sandbox_username', '' ),
					'custom_attributes' => array(
						'data-show_if_sandbox_mode' => '',
						'data-show_if_api_type'     => 'soap',
						'autocomplete'              => 'new-password',
					),
				),

				array(
					'title'             => _x( 'Sandbox Password', 'dhl', 'woocommerce-germanized' ),
					'type'              => 'password',
					'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Your password for the DHL developer portal. Please test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://entwickler.dhl.de" target = "_blank">' . _x( 'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
					'id'                => 'api_sandbox_password',
					'value'             => $this->get_setting( 'api_sandbox_password', '' ),
					'custom_attributes' => array(
						'data-show_if_sandbox_mode' => '',
						'data-show_if_api_type'     => 'soap',
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
					'id'    => 'dhl_product_options',
					'desc'  => sprintf( _x( 'Learn how to <a href="%1$s" target="_blank">find your participation numbers</a> in your DHL business portal.', 'dhl', 'woocommerce-germanized' ), 'https://vendidero.de/doc/woocommerce-germanized/dhl-integration-einrichten#produkte-und-teilnahmenummern' ),
				),
			)
		);

		$dhl_available_products = $this->get_products()->as_options() + array( 'return' => _x( 'Inlay Returns', 'dhl', 'woocommerce-germanized' ) );
		$dhl_products           = array();

		foreach ( $dhl_available_products as $product => $title ) {
			$dhl_products[] = array(
				'type'    => 'dhl_placeholder',
				'id'      => 'participation_' . $product,
				'default' => '',
				'value'   => $this->get_setting( 'participation_' . $product, '' ),
			);

			$dhl_products[] = array(
				'type'    => 'dhl_placeholder',
				'id'      => 'participation_gogreen_' . $product,
				'default' => '',
				'value'   => $this->get_setting( 'participation_gogreen_' . $product, '' ),
			);
		}

		$settings = array_merge( $settings, $dhl_products );

		$settings = array_merge(
			$settings,
			array(
				array(
					'title'    => _x( 'Participation Numbers', 'dhl', 'woocommerce-germanized' ),
					'type'     => 'dhl_participation_numbers',
					'products' => $dhl_available_products,
					'default'  => '',
					'id'       => 'participation_numbers',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'dhl_product_options',
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
					'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Use these placeholders to add info to the payment reference: %s. This text is limited to 35 characters.', 'dhl', 'woocommerce-germanized' ), '<code>' . esc_html( $ref_placeholders_str ) . '</code>' ) . '</div>',
					'default'           => '{shipment_id}',
				),

				array(
					'title'             => _x( 'Payment Reference 2', 'dhl', 'woocommerce-germanized' ),
					'type'              => 'text',
					'id'                => 'bank_ref_2',
					'custom_attributes' => array( 'maxlength' => '35' ),
					'value'             => $this->get_setting( 'bank_ref_2' ),
					'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Use these placeholders to add info to the payment reference: %s. This text is limited to 35 characters.', 'dhl', 'woocommerce-germanized' ), '<code>' . esc_html( $ref_placeholders_str ) . '</code>' ) . '</div>',
					'default'           => '{email}',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'dhl_bank_account_options',
				),
				array(
					'title' => _x( 'Tracking', 'dhl', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'tracking_options',
				),
			)
		);

		$general_settings = parent::get_general_settings();

		$general_settings = array_merge(
			$general_settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'tracking_options',
				),
			)
		);

		return array_merge( $settings, $general_settings );
	}

	protected function get_pickup_locations_settings() {
		$settings = parent::get_pickup_locations_settings();

		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => '',
					'type'  => 'title',
					'id'    => 'dhl_pickup_options',
				),
				array(
					'title'             => _x( 'Packstation', 'dhl', 'woocommerce-germanized' ),
					'desc'              => _x( 'Enable delivery to Packstation.', 'dhl', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Let customers choose a Packstation as delivery address.', 'dhl', 'woocommerce-germanized' ),
					'id'                => 'parcel_pickup_packstation_enable',
					'value'             => wc_bool_to_string( $this->get_setting( 'parcel_pickup_packstation_enable' ) ),
					'default'           => 'yes',
					'type'              => 'shiptastic_toggle',
					'custom_attributes' => array( 'data-show_if_pickup_locations_enable' => '' ),
				),
				array(
					'title'             => _x( 'Postoffice', 'dhl', 'woocommerce-germanized' ),
					'desc'              => _x( 'Enable delivery to Post Offices.', 'dhl', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Let customers choose a Post Office as delivery address.', 'dhl', 'woocommerce-germanized' ),
					'id'                => 'parcel_pickup_postoffice_enable',
					'value'             => wc_bool_to_string( $this->get_setting( 'parcel_pickup_postoffice_enable' ) ),
					'default'           => 'yes',
					'type'              => 'shiptastic_toggle',
					'custom_attributes' => array( 'data-show_if_pickup_locations_enable' => '' ),
				),
				array(
					'title'             => _x( 'Parcel Shop', 'dhl', 'woocommerce-germanized' ),
					'desc'              => _x( 'Enable delivery to Parcel Shops.', 'dhl', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Let customers choose a Parcel Shop as delivery address.', 'dhl', 'woocommerce-germanized' ),
					'id'                => 'parcel_pickup_parcelshop_enable',
					'value'             => wc_bool_to_string( $this->get_setting( 'parcel_pickup_parcelshop_enable' ) ),
					'default'           => 'yes',
					'type'              => 'shiptastic_toggle',
					'custom_attributes' => array( 'data-show_if_pickup_locations_enable' => '' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'dhl_pickup_options',
				),
			)
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
				'title' => '',
				'type'  => 'title',
				'id'    => 'preferred_options',
			),

			array(
				'title'   => _x( 'Drop-off location', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Enable drop-off location delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . _x( 'Enabling this option will display options for the user to select their preferred delivery location during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'PreferredLocation_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredLocation_enable' ) ),
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'title'   => _x( 'Neighbor', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Enable delivery to a neighbor.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . _x( 'Enabling this option will display options for the user to deliver to their preferred neighbor during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'PreferredNeighbour_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredNeighbour_enable' ) ),
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'title'   => _x( 'Delivery Type (CDP)', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Allow your international customers to choose between home and closest droppoint delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Display options for the user to select their preferred delivery type during checkout. Currently available for <a href="%s">certain countries only</a>.', 'dhl', 'woocommerce-germanized' ), esc_url( 'https://www.dhl.de/de/geschaeftskunden/paket/leistungen-und-services/internationaler-versand/paket-international.html' ) ) . '</div>',
				'id'      => 'PreferredDeliveryType_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredDeliveryType_enable' ) ),
				'default' => 'no',
				'type'    => 'shiptastic_toggle',
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
				'desc'    => _x( 'Enable delivery day delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . _x( 'Enabling this option will display options for the user to select their delivery day of delivery during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'PreferredDay_enable',
				'value'   => wc_bool_to_string( $this->get_setting( 'PreferredDay_enable' ) ),
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
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
				'value'             => $this->get_setting( 'PreferredDay_cutoff_time' ),
				'desc'              => '<div class="wc-shiptastic-additional-desc ">' . _x( 'The cut-off time is the latest possible order time up to which the minimum delivery day (day of order + 2 working days) can be guaranteed. As soon as the time is exceeded, the earliest delivery day displayed in the frontend will be shifted to one day later (day of order + 3 working days).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'default'           => '12:00',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'title'             => _x( 'Preparation days', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'number',
				'id'                => 'PreferredDay_preparation_days',
				'value'             => $this->get_setting( 'PreferredDay_preparation_days' ),
				'desc'              => '<div class="wc-shiptastic-additional-desc ">' . _x( 'If you need more time to prepare your shipments you might want to add a static preparation time to the possible starting date for delivery day delivery.', 'dhl', 'woocommerce-germanized' ) . '</div>',
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
				'type'              => 'shiptastic_toggle',
				'default'           => 'no',
				'checkboxgroup'     => 'start',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Tuesday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_tue',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_tue' ) ),
				'type'              => 'shiptastic_toggle',
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Wednesday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_wed',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_wed' ) ),
				'type'              => 'shiptastic_toggle',
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Thursday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_thu',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_thu' ) ),
				'type'              => 'shiptastic_toggle',
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Friday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_fri',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_fri' ) ),
				'type'              => 'shiptastic_toggle',
				'default'           => 'no',
				'checkboxgroup'     => '',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'desc'              => _x( 'Saturday', 'dhl', 'woocommerce-germanized' ),
				'id'                => 'PreferredDay_exclusion_sat',
				'value'             => wc_bool_to_string( $this->get_setting( 'PreferredDay_exclusion_sat' ) ),
				'type'              => 'shiptastic_toggle',
				'default'           => 'no',
				'checkboxgroup'     => 'end',
				'custom_attributes' => array( 'data-show_if_PreferredDay_enable' => '' ),
			),

			array(
				'title'    => _x( 'Exclude gateways', 'dhl', 'woocommerce-germanized' ),
				'type'     => 'multiselect',
				'desc'     => _x( 'Select payment gateways to be excluded from showing preferred services.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'       => 'preferred_payment_gateways_excluded',
				'value'    => $this->get_setting( 'preferred_payment_gateways_excluded' ),
				'options'  => $wc_gateway_titles,
				'class'    => 'wc-enhanced-select',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'preferred_options',
			),
		);

		return $settings;
	}

	protected function get_label_settings() {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipping_provider_dhl_label_options',
			),

			array(
				'title'   => _x( 'Custom shipper', 'dhl', 'woocommerce-germanized' ),
				'desc'    => _x( 'Use a custom shipper address managed within your DHL business profile.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Choose this option if you want to use a <a href="%s" target="_blank">custom address</a> profile managed within your DHL business profile as shipper reference for your labels.', 'dhl', 'woocommerce-germanized' ), 'https://vendidero.de/doc/woocommerce-germanized/dhl-integration-einrichten#individuelle-absenderreferenz-samt-logo-nutzen' ) . '</div>',
				'id'      => 'label_use_custom_shipper',
				'value'   => $this->get_setting( 'label_use_custom_shipper', 'no' ),
				'default' => 'no',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'title'             => _x( 'Shipper reference', 'dhl', 'woocommerce-germanized' ),
				'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Insert the <a href="%s" target="_blank">address reference</a> you have chosen within the DHL business portal for your custom shipper address.', 'dhl', 'woocommerce-germanized' ), 'https://vendidero.de/doc/woocommerce-germanized/dhl-integration-einrichten#individuelle-absenderreferenz-samt-logo-nutzen' ) . '</div>',
				'id'                => 'label_custom_shipper_reference',
				'value'             => $this->get_setting( 'label_custom_shipper_reference', '' ),
				'default'           => '',
				'type'              => 'text',
				'custom_attributes' => array( 'data-show_if_label_use_custom_shipper' => 'yes' ),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'shipping_provider_dhl_label_options',
			),
		);

		$settings = array_merge( $settings, parent::get_label_settings() );

		return $settings;
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/woocommerce-germanized/dhl-integration-einrichten';
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
						'next_url'     => add_query_arg( array( 'tutorial' => 'yes' ), $this->get_edit_link( 'config_set_simple_label' ) ),
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
		} elseif ( 'config_set_simple_label' === $section ) {
			$pointers = array(
				'pointers' => array(
					'zones' => array(
						'target'       => '#select2-label_config_set_-p-dhl-s-simple-z-dom-g-product-n-product-container',
						'next'         => '',
						'next_url'     => $this->get_edit_link( 'automation' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Zones', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Configure separate service(s) based on your customer\'s location.', 'dhl', 'woocommerce-germanized' ) . '</p>',
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
						'next_url'     => add_query_arg( array( 'tutorial' => 'yes' ), $this->get_edit_link( 'pickup_locations' ) ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Automation', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'You might want to save some time and generate labels automatically as soon as a shipment switches to a certain status.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'pickup_locations' === $section ) {
			$next_url = Tutorial::get_tutorial_url( 'packaging' );

			if ( $tab = Settings::get_tab( 'shipping_provider' ) ) {
				$next_url = $tab->get_next_pointers_link( $this->get_name() );
			}

			$pointers = array(
				'pointers' => array(
					'day' => array(
						'target'       => '#parcel_pickup_packstation_enable-toggle',
						'next'         => '',
						'next_url'     => $next_url,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Packstation', 'dhl', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Allow your customers to ship to a packstation (and/or other DHL location types as configured below).', 'dhl', 'woocommerce-germanized' ) . '</p>',
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

	public function get_supported_label_config_set_shipment_types() {
		return array( 'simple' );
	}

	public function update_settings( $section = '', $data = null, $save = true ) {
		$settings_to_save    = Settings::get_sanitized_settings( $this->get_settings( $section ), $data );
		$has_changed_account = false;

		if ( ! empty( $settings_to_save['api_username'] ) && $settings_to_save['api_username'] !== $this->get_api_username( 'edit' ) ) {
			$has_changed_account = true;
		}

		parent::update_settings( $section, $data, $save );

		if ( $has_changed_account ) {
			if ( $api = Package::get_api()->get_myaccount_api() ) {
				$participation_numbers = $api->get_user_participation_numbers();

				if ( ! empty( $participation_numbers ) ) {
					foreach ( $participation_numbers as $product_id => $billing_numbers ) {
						if ( empty( $billing_numbers['default'] ) ) {
							$billing_numbers['default'] = $billing_numbers['gogreen'];
						}

						if ( ! empty( $billing_numbers['default'] ) ) {
							$this->update_setting( "participation_{$product_id}", substr( $billing_numbers['default'], -2 ) );
							$this->update_setting( "participation_gogreen_{$product_id}", substr( $billing_numbers['gogreen'], -2 ) );
						}
					}

					if ( $save ) {
						$this->save();
					}
				}
			}
		}
	}
}
