<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Exception;
use Vendidero\Germanized\Shipments\Admin\Settings;
use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use Vendidero\Germanized\Shipments\Interfaces\ShippingProvider;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentError;
use WC_Data;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

class Simple extends WC_Data implements ShippingProvider {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'shipping_provider';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'shipping-provider';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'shipping_provider';

	/**
	 * Stores provider data.
	 *
	 * @var array
	 */
	protected $data = array(
		'activated'                  => true,
		'title'                      => '',
		'name'                       => '',
		'description'                => '',
		'order'                      => 0,
		'supports_customer_returns'  => false,
		'supports_guest_returns'     => false,
		'return_manual_confirmation' => true,
		'return_instructions'        => '',
		'tracking_url_placeholder'   => '',
		'tracking_desc_placeholder'  => '',
	);

	protected $address_data = array(
		'shipper' => null,
		'return'  => null,
	);

	/**
	 * Get the provider if ID is passed. In case it is an integration, data will be provided through the impl.
	 * This class should NOT be instantiated, but the `wc_gzd_get_shipping_provider` function should be used.
	 *
	 * @param int|object|ShippingProvider $provider Provider to read.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof ShippingProvider ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		} elseif ( is_object( $data ) && isset( $data->shipping_provider_id ) ) {
			$this->set_id( $data->shipping_provider_id );
		}

		$this->data_store = WC_Data_Store::load( $this->data_store_name );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
	}

	public function get_help_link() {
		return '';
	}

	public function get_signup_link() {
		return '';
	}

	public function is_pro() {
		return false;
	}

	/**
	 * Whether or not this instance is a manual integration.
	 * Manual integrations are constructed dynamically from DB and do not support
	 * automatic shipment handling, e.g. label creation.
	 *
	 * @return bool
	 */
	public function is_manual_integration() {
		return true;
	}

	/**
	 * Whether or not this instance supports a certain label type.
	 *
	 * @param string $label_type The label type e.g. simple or return.
	 * @param false|Shipment Shipment instance
	 *
	 * @return bool
	 */
	public function supports_labels( $label_type, $shipment = false ) {
		return false;
	}

	public function supports_customer_return_requests() {
		if ( $this->is_manual_integration() ) {
			return true;
		}

		return false;
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

	public function get_edit_link( $section = '' ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider&provider=' . esc_attr( $this->get_name() ) );
		$url = add_query_arg( array( 'section' => $section ), $url );

		return esc_url_raw( $url );
	}

	/**
	 * Returns whether the shipping provider is active for usage or not.
	 *
	 * @return bool
	 */
	public function is_activated() {
		return $this->get_activated() === true;
	}

	public function needs_manual_confirmation_for_returns() {
		return $this->get_return_manual_confirmation() === true;
	}

	/**
	 * @param false|\WC_Order $order
	 *
	 * @return bool
	 */
	public function supports_customer_returns( $order = false ) {
		return $this->get_supports_customer_returns() === true;
	}

	public function supports_guest_returns() {
		return $this->get_supports_customer_returns() === true && $this->get_supports_guest_returns() === true;
	}

	/**
	 * Returns a title for the shipping provider.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_prop( 'title', $context );
	}

	/**
	 * Returns the provider order.
	 *
	 * @param string $context
	 *
	 * @return int
	 */
	public function get_order( $context = 'view' ) {
		return $this->get_prop( 'order', $context );
	}

	/**
	 * Returns a unique slug/name for the shipping provider.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Returns a description for the provider.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		$desc = $this->get_prop( 'description', $context );

		if ( 'view' === $context && empty( $desc ) ) {
			return '-';
		}

		return $desc;
	}

	/**
	 * Returns whether the shipping provider is activated or not.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_activated( $context = 'view' ) {
		return $this->get_prop( 'activated', $context );
	}

	/**
	 * Returns whether the shipping provider needs manual confirmation for a return.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_return_manual_confirmation( $context = 'view' ) {
		return $this->get_prop( 'return_manual_confirmation', $context );
	}

	/**
	 * Returns whether the shipping provider supports returns added by customers or not.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_supports_customer_returns( $context = 'view' ) {
		return $this->get_prop( 'supports_customer_returns', $context );
	}

	/**
	 * Returns whether the shipping provider supports returns added by guests or not.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_supports_guest_returns( $context = 'view' ) {
		return $this->get_prop( 'supports_guest_returns', $context );
	}

	/**
	 * Returns the tracking url placeholder which is being used to
	 * construct a tracking url.
	 *
	 * @param string $context
	 *
	 * @return mixed
	 */
	public function get_tracking_url_placeholder( $context = 'view' ) {
		$data = $this->get_prop( 'tracking_url_placeholder', $context );

		// In case the option value is not stored in DB yet
		if ( 'view' === $context && empty( $data ) ) {
			$data = $this->get_default_tracking_url_placeholder();
		}

		return $data;
	}

	public function get_default_tracking_url_placeholder() {
		return '';
	}

	/**
	 * Returns the tracking description placeholder which is being used to
	 * construct a tracking description.
	 *
	 * @param string $context
	 *
	 * @return mixed
	 */
	public function get_tracking_desc_placeholder( $context = 'view' ) {
		$data = $this->get_prop( 'tracking_desc_placeholder', $context );

		// In case the option value is not stored in DB yet
		if ( 'view' === $context && empty( $data ) ) {
			$data = $this->get_default_tracking_desc_placeholder();
		}

		return $data;
	}

	public function get_default_tracking_desc_placeholder() {
		return _x( 'Your shipment is being processed by {shipping_provider}. If you want to track the shipment, please use the following tracking number: {tracking_id}. Depending on the chosen shipping method it is possible that the tracking data does not reflect the current status when receiving this email.', 'shipments', 'woocommerce-germanized' );
	}

	/**
	 * Returns the return instructions.
	 *
	 * @param string $context
	 *
	 * @return mixed
	 */
	public function get_return_instructions( $context = 'view' ) {
		return $this->get_prop( 'return_instructions', $context );
	}

	public function has_return_instructions() {
		$instructions = $this->get_return_instructions();

		return empty( $instructions ) ? false : true;
	}

	protected function get_address_props( $address_type = 'shipper' ) {
		if ( is_null( $this->address_data[ $address_type ] ) ) {
			$this->address_data[ $address_type ] = wc_gzd_get_shipment_setting_address_fields( $address_type );
		}

		return $this->address_data[ $address_type ];
	}

	public function get_shipper_address_data() {
		return $this->get_address_props( 'shipper' );
	}

	public function get_address_prop( $prop, $type = 'shipper' ) {
		$address_fields = $this->get_address_props( $type );

		return array_key_exists( $prop, $address_fields ) ? $address_fields[ $prop ] : '';
	}

	public function get_shipper_email() {
		return $this->get_address_prop( 'email' );
	}

	public function get_shipper_phone() {
		return $this->get_address_prop( 'phone' );
	}

	public function get_contact_phone() {
		return get_option( 'woocommerce_gzd_shipments_contact_phone' );
	}

	public function get_shipper_first_name() {
		return $this->get_address_prop( 'first_name' );
	}

	public function get_shipper_last_name() {
		return $this->get_address_prop( 'last_name' );
	}

	public function get_shipper_name() {
		return $this->get_shipper_formatted_full_name();
	}

	public function get_shipper_formatted_full_name() {
		return $this->get_address_prop( 'full_name' );
	}

	public function get_shipper_company() {
		return $this->get_address_prop( 'company' );
	}

	public function get_shipper_address() {
		return $this->get_address_prop( 'address_1' );
	}

	public function get_shipper_address_1() {
		return $this->get_shipper_address();
	}

	public function get_shipper_address_2() {
		return $this->get_address_prop( 'address_2' );
	}

	public function get_shipper_street() {
		return $this->get_address_prop( 'street' );
	}

	public function get_shipper_street_number() {
		return $this->get_address_prop( 'street_number' );
	}

	public function get_shipper_postcode() {
		return $this->get_address_prop( 'postcode' );
	}

	public function get_shipper_city() {
		return $this->get_address_prop( 'city' );
	}

	public function get_shipper_customs_reference_number() {
		return $this->get_address_prop( 'customs_reference_number' );
	}

	public function get_shipper_customs_uk_vat_id() {
		return $this->get_address_prop( 'customs_uk_vat_id' );
	}

	public function get_shipper_country() {
		$country_data = wc_format_country_state_string( $this->get_address_prop( 'country' ) );

		return $country_data['country'];
	}

	public function get_shipper_state() {
		$country_data = wc_format_country_state_string( $this->get_address_prop( 'country' ) );

		return $country_data['state'];
	}

	public function get_return_address_data() {
		return $this->get_address_props( 'return' );
	}

	public function get_return_first_name() {
		return $this->get_address_prop( 'first_name', 'return' );
	}

	public function get_return_last_name() {
		return $this->get_address_prop( 'last_name', 'return' );
	}

	public function get_return_company() {
		return $this->get_address_prop( 'company', 'return' );
	}

	public function get_return_name() {
		return $this->get_return_formatted_full_name();
	}

	public function get_return_formatted_full_name() {
		return $this->get_address_prop( 'full_name', 'return' );
	}

	public function get_return_address() {
		return $this->get_address_prop( 'address_1', 'return' );
	}

	public function get_return_address_2() {
		return $this->get_address_prop( 'address_2', 'return' );
	}

	public function get_return_street() {
		return $this->get_address_prop( 'street', 'return' );
	}

	public function get_return_street_number() {
		return $this->get_address_prop( 'street_number', 'return' );
	}

	public function get_return_postcode() {
		return $this->get_address_prop( 'postcode', 'return' );
	}

	public function get_return_city() {
		return $this->get_address_prop( 'city', 'return' );
	}

	public function get_return_country() {
		$country_data = wc_format_country_state_string( $this->get_address_prop( 'country', 'return' ) );

		return $country_data['country'];
	}

	public function get_return_state() {
		$country_data = wc_format_country_state_string( $this->get_address_prop( 'country', 'return' ) );

		return $country_data['state'];
	}

	public function get_return_email() {
		return $this->get_address_prop( 'email', 'return' );
	}

	public function get_return_phone() {
		return $this->get_address_prop( 'phone', 'return' );
	}

	/**
	 * Set the current shipping provider to active or inactive.
	 *
	 * @param bool $is_activated
	 */
	public function set_activated( $is_activated ) {
		$this->set_prop( 'activated', wc_string_to_bool( $is_activated ) );
	}

	/**
	 * Mark the current shipping provider as manual needed confirmation for returns.
	 *
	 * @param bool $needs_confirmation
	 */
	public function set_return_manual_confirmation( $needs_confirmation ) {
		$this->set_prop( 'return_manual_confirmation', wc_string_to_bool( $needs_confirmation ) );
	}

	/**
	 * Set whether or not the current shipping provider supports customer returns
	 *
	 * @param bool $supports
	 */
	public function set_supports_customer_returns( $supports ) {
		$this->set_prop( 'supports_customer_returns', wc_string_to_bool( $supports ) );
	}

	/**
	 * Set whether or not the current shipping provider supports guest returns
	 *
	 * @param bool $supports
	 */
	public function set_supports_guest_returns( $supports ) {
		$this->set_prop( 'supports_guest_returns', wc_string_to_bool( $supports ) );
	}

	public function update_settings_with_defaults() {
		foreach ( $this->get_all_settings() as $section => $settings ) {
			foreach ( $settings as $setting ) {
				$type    = isset( $setting['type'] ) ? $setting['type'] : 'title';
				$default = isset( $setting['default'] ) ? $setting['default'] : null;

				if ( in_array( $type, array( 'title', 'sectionend', 'html' ), true ) || ! isset( $setting['id'] ) || empty( $setting['id'] ) ) {
					continue;
				}

				$current_value = $this->get_setting( $setting['id'], null, 'edit' );

				/**
				 * Update meta data with default value in case it does not yet exist.
				 */
				if ( is_null( $current_value ) && ! is_null( $default ) ) {
					$this->update_setting( $setting['id'], $default );
				}
			}
		}
	}

	/**
	 * Activate current ShippingProvider instance.
	 */
	public function activate() {
		$this->set_activated( true );
		$this->update_settings_with_defaults();
		$this->save();

		/**
		 * This action fires as soon as a certain shipping provider gets activated.
		 *
		 * @param ShippingProvider $shipping_provider The shipping provider instance.
		 */
		do_action( 'woocommerce_gzd_shipping_provider_activated', $this );
	}

	/**
	 * Deactivate current ShippingProvider instance.
	 */
	public function deactivate() {
		$this->set_activated( false );
		$this->save();

		/**
		 * This action fires as soon as a certain shipping provider gets deactivated.
		 *
		 * @param ShippingProvider $shipping_provider The shipping provider instance.
		 */
		do_action( 'woocommerce_gzd_shipping_provider_deactivated', $this );
	}

	/**
	 * Set the name of the current shipping provider.
	 *
	 * @param string $name
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set the title of the current shipping provider.
	 *
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->set_prop( 'title', $title );
	}

	/**
	 * Set the order of the current shipping provider.
	 *
	 * @param int $order
	 */
	public function set_order( $order ) {
		$this->set_prop( 'order', absint( $order ) );
	}

	/**
	 * Set the description of the current shipping provider.
	 *
	 * @param string $description
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set the return instructions of the current shipping provider.
	 *
	 * @param string $instructions
	 */
	public function set_return_instructions( $instructions ) {
		$this->set_prop( 'return_instructions', $instructions );
	}

	/**
	 * Set the tracking url placeholder of the current shipping provider.
	 *
	 * @param string $placeholder
	 */
	public function set_tracking_url_placeholder( $placeholder ) {
		$this->set_prop( 'tracking_url_placeholder', $placeholder );
	}

	/**
	 * Set the tracking description placeholder of the current shipping provider.
	 *
	 * @param string $placeholder
	 */
	public function set_tracking_desc_placeholder( $placeholder ) {
		$this->set_prop( 'tracking_desc_placeholder', $placeholder );
	}

	/**
	 * Returns the tracking url for a specific shipment.
	 *
	 * @param Shipment $shipment
	 *
	 * @return string
	 */
	public function get_tracking_url( $shipment ) {

		$tracking_url = '';
		$tracking_id  = $shipment->get_tracking_id();

		if ( '' !== $this->get_tracking_url_placeholder() && ! empty( $tracking_id ) ) {
			$placeholders = $this->get_tracking_placeholders( $shipment );
			$tracking_url = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $this->get_tracking_url_placeholder() );
		}

		/**
		 * This filter returns the tracking url provided by the shipping provider for a certain shipment.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_gzd_shipping_provider_dhl_get_tracking_url
		 *
		 * @param string           $tracking_url The tracking url.
		 * @param Shipment         $shipment The shipment used to build the url.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( $this->get_hook_prefix() . 'tracking_url', $tracking_url, $shipment, $this );
	}

	/**
	 * Returns the tracking description for a certain shipment.
	 *
	 * @param Shipment $shipment
	 *
	 * @return string
	 */
	public function get_tracking_desc( $shipment, $plain = false ) {
		$tracking_desc = '';
		$tracking_id   = $shipment->get_tracking_id();

		if ( '' !== $this->get_tracking_desc_placeholder() && ! empty( $tracking_id ) ) {
			$placeholders = $this->get_tracking_placeholders( $shipment );

			if ( ! $plain && apply_filters( "{$this->get_general_hook_prefix()}tracking_id_with_link", true, $shipment ) && $shipment->has_tracking() ) {
				$placeholders['{tracking_id}'] = '<a href="' . esc_url( $shipment->get_tracking_url() ) . '" target="_blank">' . $shipment->get_tracking_id() . '</a>';
			}

			$tracking_desc = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $this->get_tracking_desc_placeholder() );
		}

		/**
		 * This filter returns the tracking description provided by the shipping provider for a certain shipment.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_gzd_shipping_provider_dhl_get_tracking_description
		 *
		 * @param string           $tracking_url The tracking description.
		 * @param Shipment         $shipment The shipment used to build the url.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( $this->get_hook_prefix() . 'tracking_desc', $tracking_desc, $shipment, $this );
	}

	/**
	 * @param bool|Shipment $shipment
	 *
	 * @return array
	 */
	public function get_tracking_placeholders( $shipment = false ) {
		$label = false;

		if ( $shipment ) {
			$label = $shipment->get_label();
		}

		/**
		 * This filter may be used to add or manipulate tracking placeholder data
		 * for a certain shipping provider.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_gzd_shipping_provider_dhl_get_tracking_placeholders
		 *
		 * @param array            $placeholders Placeholders in key => value pairs.
		 * @param ShippingProvider $provider The shipping provider.
		 * @param Shipment|bool    $shipment The shipment instance if available.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters(
			"{$this->get_hook_prefix()}tracking_placeholders",
			array(
				'{shipment_number}'   => $shipment ? $shipment->get_shipment_number() : '',
				'{order_number}'      => $shipment ? $shipment->get_order_number() : '',
				'{tracking_id}'       => $shipment ? $shipment->get_tracking_id() : '',
				'{postcode}'          => $shipment ? $shipment->get_postcode() : '',
				'{date_sent_day}'     => $shipment && $shipment->get_date_sent() ? $shipment->get_date_sent()->format( 'd' ) : '',
				'{date_sent_month}'   => $shipment && $shipment->get_date_sent() ? $shipment->get_date_sent()->format( 'm' ) : '',
				'{date_sent_year}'    => $shipment && $shipment->get_date_sent() ? $shipment->get_date_sent()->format( 'Y' ) : '',
				'{date_day}'          => $shipment && $shipment->get_date_created() ? $shipment->get_date_created()->format( 'd' ) : '',
				'{date_month}'        => $shipment && $shipment->get_date_created() ? $shipment->get_date_created()->format( 'm' ) : '',
				'{date_year}'         => $shipment && $shipment->get_date_created() ? $shipment->get_date_created()->format( 'Y' ) : '',
				'{label_date_day}'    => $label ? $label->get_date_created()->format( 'd' ) : '',
				'{label_date_month}'  => $label ? $label->get_date_created()->format( 'm' ) : '',
				'{label_date_year}'   => $label ? $label->get_date_created()->format( 'Y' ) : '',
				'{shipping_provider}' => $this->get_title(),
			),
			$this,
			$shipment
		);
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->get_general_hook_prefix() . 'get_';
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		$name = sanitize_key( $this->get_name( 'edit' ) );

		if ( empty( $name ) ) {
			return 'woocommerce_gzd_shipping_provider_';
		} else {
			return "woocommerce_gzd_shipping_provider_{$name}_";
		}
	}

	protected function get_general_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipping_provider_options',
			),
		);

		if ( $this->is_manual_integration() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title'    => _x( 'Title', 'shipments', 'woocommerce-germanized' ),
						'desc_tip' => _x( 'Choose a title for the shipping provider.', 'shipments', 'woocommerce-germanized' ),
						'id'       => 'shipping_provider_title',
						'value'    => $this->get_title( 'edit' ),
						'default'  => '',
						'type'     => 'text',
					),

					array(
						'title'    => _x( 'Description', 'shipments', 'woocommerce-germanized' ),
						'desc_tip' => _x( 'Choose a description for the shipping provider.', 'shipments', 'woocommerce-germanized' ),
						'id'       => 'shipping_provider_description',
						'value'    => $this->get_description( 'edit' ),
						'default'  => '',
						'type'     => 'textarea',
						'css'      => 'width: 100%;',
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'title'       => _x( 'Tracking URL', 'shipments', 'woocommerce-germanized' ),
					'desc'        => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Adjust the placeholder used to construct the tracking URL for this shipping provider. You may use on of the following placeholders to insert the tracking id or other dynamic data: %s', 'shipments', 'woocommerce-germanized' ), '<code>' . implode( ', ', array_keys( $this->get_tracking_placeholders() ) ) . '</code>' ) . '</div>',
					'id'          => 'shipping_provider_tracking_url_placeholder',
					'placeholder' => $this->get_default_tracking_url_placeholder(),
					'value'       => $this->get_tracking_url_placeholder( 'edit' ),
					'default'     => $this->get_default_tracking_url_placeholder(),
					'type'        => 'text',
					'css'         => 'width: 100%;',
				),

				array(
					'title'       => _x( 'Tracking description', 'shipments', 'woocommerce-germanized' ),
					'desc'        => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Adjust the placeholder used to construct the tracking description for this shipping provider (e.g. used within notification emails). You may use on of the following placeholders to insert the tracking id or other dynamic data: %s', 'shipments', 'woocommerce-germanized' ), '<code>' . implode( ', ', array_keys( $this->get_tracking_placeholders() ) ) . '</code>' ) . '</div>',
					'id'          => 'shipping_provider_tracking_desc_placeholder',
					'placeholder' => $this->get_default_tracking_desc_placeholder(),
					'value'       => $this->get_tracking_desc_placeholder( 'edit' ),
					'default'     => $this->get_default_tracking_desc_placeholder(),
					'type'        => 'textarea',
					'css'         => 'width: 100%; min-height: 60px; margin-top: 1em;',
				),
			)
		);

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_options',
				),
			)
		);

		return $settings;
	}

	/**
	 * @param Shipment $shipment
	 * @param $key
	 */
	public function get_shipment_setting( $shipment, $key, $default = null ) {
		$value = $this->get_setting( $key, $default );

		if ( $method = $shipment->get_shipping_method_instance() ) {
			$prefixed_key = $this->get_name() . '_' . $key;

			/**
			 * Do only allow overriding settings in case the shipping provider
			 * selected for the shipping method matches the current shipping provider.
			 */
			if ( $method->get_provider() === $this->get_name() && $method->has_option( $prefixed_key ) ) {
				$method_value = $method->get_option( $prefixed_key );

				if ( ! is_null( $method_value ) && $value !== $method_value ) {
					$value = $method_value;
				}
			}
		}

		return $value;
	}

	public function get_setting( $key, $default = null, $context = 'view' ) {
		$clean_key = $this->unprefix_setting_key( $key );
		$getter    = "get_{$clean_key}";
		$value     = $default;

		if ( is_callable( array( $this, $getter ) ) ) {
			$value = $this->$getter( $context );
		} elseif ( $this->meta_exists( $clean_key ) ) {
			$value = $this->get_meta( $clean_key, true, $context );
		}

		if ( strstr( $key, 'password' ) && ! is_null( $value ) ) {
			if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
				$result = \WC_GZD_Secret_Box_Helper::decrypt( $value );

				if ( ! is_wp_error( $result ) ) {
					$value = $result;
				}
			}

			$value = $this->retrieve_password( $value );
		}

		return $value;
	}

	protected function retrieve_password( $value ) {
		return is_null( $value ) ? $value : stripslashes( $value );
	}

	protected function unprefix_setting_key( $key ) {
		$prefixes = array(
			'shipping_provider_',
			$this->get_name() . '_',
		);

		foreach ( $prefixes as $prefix ) {
			if ( substr( $key, 0, strlen( $prefix ) ) === $prefix ) {
				$key = substr( $key, strlen( $prefix ) );
			}
		}

		return $key;
	}

	public function update_settings( $section = '', $data = null, $save = true ) {
		$settings_to_save = Settings::get_sanitized_settings( $this->get_settings( $section ), $data );

		foreach ( $settings_to_save as $option_name => $value ) {
			$this->update_setting( $option_name, $value );
		}

		if ( $save ) {
			$this->save();
		}
	}

	public function update_setting( $setting, $value ) {
		$setting_name_clean = $this->unprefix_setting_key( $setting );
		$setter             = 'set_' . $setting_name_clean;

		try {
			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->update_meta_data( $setting_name_clean, $value );
			}
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	public function get_settings( $section = '', $for_shipping_method = false ) {
		$settings = array();

		if ( '' === $section || 'general' === $section ) {
			$settings = $this->get_general_settings( $for_shipping_method );
		} elseif ( 'returns' === $section ) {
			$settings = $this->get_return_settings( $for_shipping_method );
		} elseif ( is_callable( array( $this, "get_{$section}_settings" ) ) ) {
			$settings = $this->{"get_{$section}_settings"}( $for_shipping_method );
		}

		/**
		 * This filter returns the admin settings available for a certain shipping provider.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_gzd_shipping_provider_dhl_get_settings
		 *
		 * @param array            $settings Available settings.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( $this->get_hook_prefix() . 'settings', $settings, $section, $this, $for_shipping_method );
	}

	protected function get_return_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipping_provider_return_options',
			),
		);

		$settings = array_merge(
			$settings,
			array(
				array(
					'title'       => _x( 'Customer returns', 'shipments', 'woocommerce-germanized' ),
					'desc'        => _x( 'Allow customers to submit return requests to shipments.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'This option will allow your customers to submit return requests to orders. Return requests will be visible within your %1$s. To learn more about return requests by customers and/or guests, please check the %2$s.', 'shipments', 'woocommerce-germanized' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-gzd-return-shipments' ) ) . '">' . _x( 'Return Dashboard', 'shipments', 'woocommerce-germanized' ) . '</a>', '<a href="https://vendidero.de/dokument/retouren-konfigurieren-und-verwalten" target="_blank">' . _x( 'docs', 'shipments', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
					'id'          => 'supports_customer_returns',
					'placeholder' => '',
					'value'       => wc_bool_to_string( $this->get_supports_customer_returns( 'edit' ) ),
					'default'     => 'no',
					'type'        => 'gzd_toggle',
				),

				array(
					'title'             => _x( 'Guest returns', 'shipments', 'woocommerce-germanized' ),
					'desc'              => _x( 'Allow guests to submit return requests to shipments.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Guests will need to provide their email address and the order id to receive a one-time link to submit a return request. The placeholder %s might be used to place the request form on your site.', 'shipments', 'woocommerce-germanized' ), '<code>[gzd_return_request_form]</code>' ) . '</div>',
					'id'                => 'supports_guest_returns',
					'default'           => 'no',
					'value'             => wc_bool_to_string( $this->get_supports_guest_returns( 'edit' ) ),
					'type'              => 'gzd_toggle',
					'custom_attributes' => array(
						'data-show_if_shipping_provider_supports_customer_returns' => '',
					),
				),

				array(
					'title'             => _x( 'Manual confirmation', 'shipments', 'woocommerce-germanized' ),
					'desc'              => _x( 'Return requests need manual confirmation.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'By default return request need manual confirmation e.g. a shop manager needs to review return requests which by default are added with the status "requested" after a customer submitted a return request. If you choose to disable this option, customer return requests will be added as "processing" and an email confirmation including instructions will be sent immediately to the customer.', 'shipments', 'woocommerce-germanized' ) . '</div>',
					'id'                => 'return_manual_confirmation',
					'placeholder'       => '',
					'value'             => wc_bool_to_string( $this->get_return_manual_confirmation( 'edit' ) ),
					'default'           => 'yes',
					'type'              => 'gzd_toggle',
					'custom_attributes' => array(
						'data-show_if_shipping_provider_supports_customer_returns' => '',
					),
				),

				array(
					'title'             => _x( 'Return instructions', 'shipments', 'woocommerce-germanized' ),
					'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Provide your customer with instructions on how to return the shipment after a return request has been confirmed e.g. explain how to prepare the return for shipment. In case a label cannot be generated automatically, make sure to provide your customer with information on how to obain a return label.', 'shipments', 'woocommerce-germanized' ) . '</div>',
					'id'                => 'return_instructions',
					'placeholder'       => '',
					'value'             => $this->get_return_instructions( 'edit' ),
					'default'           => '',
					'type'              => 'textarea',
					'css'               => 'width: 100%; min-height: 60px; margin-top: 1em;',
					'custom_attributes' => array(
						'data-show_if_shipping_provider_supports_customer_returns' => '',
					),
				),
			)
		);

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_return_options',
				),
			)
		);

		return $settings;
	}

	protected function get_all_settings( $for_shipping_method = false ) {
		$settings = array();
		$sections = array_keys( $this->get_setting_sections() );

		foreach ( $sections as $section ) {
			$settings[ $section ] = $this->get_settings( $section, $for_shipping_method );
		}

		return $settings;
	}

	public function get_shipping_method_settings() {
		$settings = $this->get_all_settings( true );
		$sections = $this->get_setting_sections();

		$method_settings         = array();
		$include_current_section = false;

		foreach ( $settings as $section => $section_settings ) {
			$global_settings_url = $this->get_edit_link( $section );
			$default_title       = $sections[ $section ];

			foreach ( $section_settings as $setting ) {
				$include = false;
				$setting = wp_parse_args(
					$setting,
					array(
						'allow_override'    => ( $include_current_section && ! in_array( $setting['type'], array( 'title', 'sectionend' ), true ) ) ? true : false,
						'type'              => '',
						'id'                => '',
						'value'             => '',
						'title_method'      => '',
						'title'             => '',
						'custom_attributes' => array(),
					)
				);

				if ( true === $setting['allow_override'] ) {
					$include = true;

					if ( 'title' === $setting['type'] ) {
						$include_current_section = true;
					}
				} elseif ( $include_current_section && ! in_array( $setting['type'], array( 'title', 'sectionend' ), true ) && false !== $setting['allow_override'] ) {
					$include = true;
				} elseif ( in_array( $setting['type'], array( 'title', 'sectionend' ), true ) ) {
					$include_current_section = false;
				}

				if ( $include ) {
					$new_setting                      = array();
					$new_setting['id']                = $this->get_name() . '_' . $setting['id'];
					$new_setting['type']              = str_replace( 'gzd_toggle', 'checkbox', $setting['type'] );
					$new_setting['default']           = $setting['value'];
					$new_setting['custom_attributes'] = array();

					if ( ! empty( $setting['custom_attributes'] ) ) {
						foreach ( $setting['custom_attributes'] as $attr => $val ) {
							$new_attr = $attr;

							if ( 'data-show_if_' === substr( $attr, 0, 13 ) ) {
								$new_attr = 'data-show_if_' . $this->get_name() . '_' . substr( $attr, 13, strlen( $attr ) );
							}

							$new_setting['custom_attributes'][ $new_attr ] = $val;
						}
					}

					if ( 'checkbox' === $new_setting['type'] ) {
						$new_setting['label'] = $setting['desc'];
					} elseif ( isset( $setting['desc'] ) ) {
						$new_setting['description'] = $setting['desc'];
					}

					$copy = array( 'options', 'title', 'desc_tip' );

					foreach ( $copy as $cp ) {
						if ( isset( $setting[ $cp ] ) ) {
							$new_setting[ $cp ] = $setting[ $cp ];
						}
					}

					if ( 'title' === $new_setting['type'] ) {
						$new_setting['description'] = sprintf( _x( 'These settings override your <a href="%1$s">global %2$s options</a>. Do only adjust these settings in case you would like to specifically adjust them for this specific shipping method.', 'shipments', 'woocommerce-germanized' ), esc_url( $global_settings_url ), $this->get_title() );

						if ( empty( $setting['title'] ) ) {
							$new_setting['title'] = $default_title;
						}

						if ( ! empty( $setting['title_method'] ) ) {
							$new_setting['title'] = $setting['title_method'];
						}
					}

					$method_settings[ $new_setting['id'] ] = $new_setting;
				}
			}
		}

		return $method_settings;
	}

	public function get_setting_sections() {
		$sections = array(
			'' => _x( 'General', 'shipments', 'woocommerce-germanized' ),
		);

		if ( $this->supports_customer_return_requests() ) {
			$sections['returns'] = _x( 'Return Requests', 'shipments', 'woocommerce-germanized' );
		}

		return $sections;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return ShipmentLabel|false
	 */
	public function get_label( $shipment ) {
		return apply_filters( "{$this->get_hook_prefix()}label", false, $shipment, $this );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields_html( $shipment ) {
		return apply_filters( "{$this->get_hook_prefix()}label_fields_html", '', $shipment, $this );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 * @param mixed $props
	 */
	public function create_label( $shipment, $props = false ) {
		$result = new ShipmentError( 'shipping-provider', _x( 'This shipping provider does not support creating labels.', 'shipments', 'woocommerce-germanized' ) );

		return $result;
	}
}
