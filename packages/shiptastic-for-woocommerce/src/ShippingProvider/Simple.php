<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\ShippingProvider;

use Exception;
use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Interfaces\ShipmentLabel;
use Vendidero\Shiptastic\Interfaces\ShippingProvider;
use Vendidero\Shiptastic\SecretBox;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentError;
use WC_Data;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

class Simple extends WC_Data implements ShippingProvider {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'shipping_provider';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @var object
	 */
	protected $data_store_name = 'shipping-provider';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'shipping_provider';

	/**
	 * @var ServiceList
	 */
	protected $services = null;

	/**
	 * @var ProductList
	 */
	protected $products = null;

	/**
	 * @var PrintFormatList
	 */
	protected $print_formats = null;

	/**
	 * Stores provider data.
	 *
	 * @var array
	 */
	protected $data = array(
		'activated'                  => false,
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
	 * This class should NOT be instantiated, but the `wc_stc_get_shipping_provider` function should be used.
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

	/**
	 * This method overwrites the base class's clone method to make it a no-op. In base class WC_Data, we are unsetting the meta_id to clone.
	 *
	 * @see WC_Abstract_Order::__clone()
	 */
	public function __clone() {}

	public function get_help_link() {
		return '';
	}

	public function get_section_help_link( $section ) {
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
		$base_url = Settings::get_settings_url( 'shipping_provider', $section );
		$base_url = add_query_arg( array( 'provider' => $this->get_name() ), $base_url );

		return esc_url_raw( $base_url );
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
			$this->address_data[ $address_type ] = wc_stc_get_shipment_setting_address_fields( $address_type );
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
		return get_option( 'woocommerce_shiptastic_contact_phone' );
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

	protected function setting_supports_default_update( $setting ) {
		$setting = wp_parse_args(
			$setting,
			array(
				'type'         => 'title',
				'id'           => '',
				'skip_install' => false,
			)
		);

		if ( in_array( $setting['type'], array( 'title', 'sectionend', 'html' ), true ) || empty( $setting['id'] ) || $setting['skip_install'] ) {
			return false;
		}

		return true;
	}

	public function update_settings_with_defaults() {
		foreach ( $this->get_all_settings() as $section => $settings ) {
			foreach ( $settings as $setting ) {
				$default = isset( $setting['default'] ) ? $setting['default'] : null;

				if ( ! $this->setting_supports_default_update( $setting ) ) {
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
		do_action( 'woocommerce_shiptastic_shipping_provider_activated', $this );
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
		do_action( 'woocommerce_shiptastic_shipping_provider_deactivated', $this );
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
		 * Example hook name: woocommerce_shiptastic_shipping_provider_dhl_get_tracking_url
		 *
		 * @param string           $tracking_url The tracking url.
		 * @param Shipment         $shipment The shipment used to build the url.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @package Vendidero/Shiptastic
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
		 * Example hook name: woocommerce_shiptastic_shipping_provider_dhl_get_tracking_description
		 *
		 * @param string           $tracking_url The tracking description.
		 * @param Shipment         $shipment The shipment used to build the url.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @package Vendidero/Shiptastic
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
		 * Example hook name: woocommerce_shiptastic_shipping_provider_dhl_get_tracking_placeholders
		 *
		 * @param array            $placeholders Placeholders in key => value pairs.
		 * @param ShippingProvider $provider The shipping provider.
		 * @param Shipment|bool    $shipment The shipment instance if available.
		 *
		 * @package Vendidero/Shiptastic
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
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->get_general_hook_prefix() . 'get_';
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		$name = sanitize_key( $this->get_name( 'edit' ) );

		if ( empty( $name ) ) {
			return 'woocommerce_shiptastic_shipping_provider_';
		} else {
			return "woocommerce_shiptastic_shipping_provider_{$name}_";
		}
	}

	protected function get_general_settings() {
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
					'type' => 'sectionend',
					'id'   => 'shipping_provider_options',
				),
			)
		);

		if ( $this->is_manual_integration() ) {
			$settings = array_merge( $settings, $this->get_tracking_settings() );
		}

		return $settings;
	}

	protected function get_tracking_settings() {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipping_provider_tracking_options',
			),
			array(
				'title'       => _x( 'Tracking URL', 'shipments', 'woocommerce-germanized' ),
				'desc'        => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Adjust the placeholder used to construct the tracking URL for this shipping provider. You may use on of the following placeholders to insert the tracking id or other dynamic data: %s', 'shipments', 'woocommerce-germanized' ), '<code>' . implode( ', ', array_keys( $this->get_tracking_placeholders() ) ) . '</code>' ) . '</div>',
				'id'          => 'shipping_provider_tracking_url_placeholder',
				'placeholder' => $this->get_default_tracking_url_placeholder(),
				'value'       => $this->get_tracking_url_placeholder( 'edit' ),
				'default'     => $this->get_default_tracking_url_placeholder(),
				'type'        => 'text',
				'css'         => 'width: 100%;',
			),

			array(
				'title'       => _x( 'Tracking description', 'shipments', 'woocommerce-germanized' ),
				'desc'        => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Adjust the placeholder used to construct the tracking description for this shipping provider (e.g. used within notification emails). You may use on of the following placeholders to insert the tracking id or other dynamic data: %s', 'shipments', 'woocommerce-germanized' ), '<code>' . implode( ', ', array_keys( $this->get_tracking_placeholders() ) ) . '</code>' ) . '</div>',
				'id'          => 'shipping_provider_tracking_desc_placeholder',
				'placeholder' => $this->get_default_tracking_desc_placeholder(),
				'value'       => $this->get_tracking_desc_placeholder( 'edit' ),
				'default'     => $this->get_default_tracking_desc_placeholder(),
				'type'        => 'textarea',
				'css'         => 'width: 100%; min-height: 60px; margin-top: 1em;',
			),
		);

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_tracking_options',
				),
			)
		);

		return $settings;
	}

	/**
	 * @param Shipment $shipment
	 * @param $key
	 */
	public function get_shipment_setting( $shipment, $key, $default_value = null ) {
		$value = $this->get_setting( $key, $default_value );

		if ( $config_set = $shipment->get_label_configuration_set() ) {
			if ( $config_set->has_setting( $key ) ) {
				$value = $config_set->get_setting( $key, $default_value );
			}
		}

		return $value;
	}

	public function get_setting( $key, $default_value = null, $context = 'view' ) {
		$clean_key = $this->unprefix_setting_key( $key );
		$getter    = "get_{$clean_key}";
		$value     = $default_value;

		if ( is_callable( array( $this, $getter ) ) ) {
			$value = $this->$getter( $context );

			if ( '' === $value && 'view' === $context && ! is_null( $default_value ) ) {
				$value = $default_value;
			}
		} elseif ( $this->meta_exists( $clean_key ) ) {
			$value = $this->get_meta( $clean_key, true, $context );
		}

		if ( strstr( $key, 'password' ) && ! is_null( $value ) ) {
			$result = SecretBox::decrypt( $value );

			if ( ! is_wp_error( $result ) ) {
				$value = $result;
			}

			$value = $this->retrieve_password( $value );
		}

		return apply_filters( "{$this->get_hook_prefix()}setting_{$clean_key}", $value, $key, $default_value, $context );
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

	public function get_settings( $section = '' ) {
		$settings = array();

		if ( '' === $section || 'general' === $section ) {
			$settings = $this->get_general_settings();
		} elseif ( 'returns' === $section ) {
			$settings = $this->get_return_settings();
		} elseif ( is_callable( array( $this, "get_{$section}_settings" ) ) ) {
			$settings = $this->{"get_{$section}_settings"}();
		}

		/**
		 * This filter returns the admin settings available for a certain shipping provider.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_shiptastic_shipping_provider_dhl_get_settings
		 *
		 * @param array            $settings Available settings.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( $this->get_hook_prefix() . 'settings', $settings, $section, $this );
	}

	protected function get_return_settings() {
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
					'desc'        => _x( 'Allow customers to submit return requests to shipments.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'This option will allow your customers to submit return requests to orders. Return requests will be visible within your <a href="%s">return dashboard</a>. To learn more about return requests by customers and/or guests, please check the <a href="https://vendidero.com/doc/shiptastic/manage-returns">docs</a>.', 'shipments', 'woocommerce-germanized' ), esc_url( admin_url( 'admin.php?page=wc-stc-return-shipments' ) ) ) . '</div>',
					'id'          => 'supports_customer_returns',
					'placeholder' => '',
					'value'       => wc_bool_to_string( $this->get_supports_customer_returns( 'edit' ) ),
					'default'     => 'no',
					'type'        => 'shiptastic_toggle',
				),

				array(
					'title'             => _x( 'Guest returns', 'shipments', 'woocommerce-germanized' ),
					'desc'              => _x( 'Allow guests to submit return requests to shipments.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Guests will need to provide their email address and the order id to receive a one-time link to submit a return request. The placeholder %s might be used to place the request form on your site.', 'shipments', 'woocommerce-germanized' ), '<code>[shiptastic_return_request_form]</code>' ) . '</div>',
					'id'                => 'supports_guest_returns',
					'default'           => 'no',
					'value'             => wc_bool_to_string( $this->get_supports_guest_returns( 'edit' ) ),
					'type'              => 'shiptastic_toggle',
					'custom_attributes' => array(
						'data-show_if_shipping_provider_supports_customer_returns' => '',
					),
				),

				array(
					'title'             => _x( 'Manual confirmation', 'shipments', 'woocommerce-germanized' ),
					'desc'              => _x( 'Return requests need manual confirmation.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . _x( 'By default return request need manual confirmation e.g. a shop manager needs to review return requests which by default are added with the status "requested" after a customer submitted a return request. If you choose to disable this option, customer return requests will be added as "processing" and an email confirmation including instructions will be sent immediately to the customer.', 'shipments', 'woocommerce-germanized' ) . '</div>',
					'id'                => 'return_manual_confirmation',
					'placeholder'       => '',
					'value'             => wc_bool_to_string( $this->get_return_manual_confirmation( 'edit' ) ),
					'default'           => 'yes',
					'type'              => 'shiptastic_toggle',
					'custom_attributes' => array(
						'data-show_if_shipping_provider_supports_customer_returns' => '',
					),
				),

				array(
					'title'             => _x( 'Return instructions', 'shipments', 'woocommerce-germanized' ),
					'desc'              => '<div class="wc-shiptastic-additional-desc">' . _x( 'Provide your customer with instructions on how to return the shipment after a return request has been confirmed e.g. explain how to prepare the return for shipment. In case a label cannot be generated automatically, make sure to provide your customer with information on how to obain a return label.', 'shipments', 'woocommerce-germanized' ) . '</div>',
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

	protected function get_all_settings() {
		$settings = array();
		$sections = array_keys( $this->get_setting_sections() );

		foreach ( $sections as $section ) {
			$settings[ $section ] = $this->get_settings( $section );
		}

		return $settings;
	}

	public function get_shipping_method_settings() {
		return array();
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
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return ShipmentLabel|false
	 */
	public function get_label( $shipment ) {
		return apply_filters( "{$this->get_hook_prefix()}label", false, $shipment, $this );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_label_fields_html( $shipment ) {
		return apply_filters( "{$this->get_hook_prefix()}label_fields_html", '', $shipment, $this );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 * @param mixed $props
	 */
	public function create_label( $shipment, $props = false ) {
		$result = new ShipmentError( 'shipping-provider', _x( 'This shipping provider does not support creating labels.', 'shipments', 'woocommerce-germanized' ) );

		return $result;
	}

	/**
	 * @param $filter_args
	 *
	 * @return ProductList
	 */
	public function get_products( $filter_args = array() ) {
		if ( is_null( $this->products ) ) {
			$this->products = new ProductList();
			$this->register_products();
		}

		$products = $this->products;

		if ( ! empty( $filter_args ) ) {
			return $products->filter( $filter_args );
		}

		return $products;
	}

	/**
	 * @param $id
	 *
	 * @return false|Product
	 */
	public function get_product( $id ) {
		$products = $this->get_products();

		return $products->get( $id );
	}

	protected function register_products() {
	}

	protected function register_product( $maybe_product, $args = array() ) {
		if ( is_null( $this->products ) ) {
			$this->products = new ProductList();
		}

		if ( ! is_a( $maybe_product, 'Vendidero\Shiptastic\ShippingProvider\Product' ) ) {
			try {
				$args['id']    = $maybe_product;
				$maybe_product = new Product( $this, $args );
			} catch ( Exception $e ) {
				return new \WP_Error( 'register-product', $e->getMessage() );
			}
		}

		$this->products->add( $maybe_product );

		return true;
	}

	/**
	 * @param $filter_args
	 *
	 * @return PrintFormatList
	 */
	public function get_print_formats( $filter_args = array() ) {
		if ( is_null( $this->print_formats ) ) {
			$this->print_formats = new PrintFormatList();
			$this->register_print_formats();
		}

		$print_formats = $this->print_formats;

		if ( ! empty( $filter_args ) ) {
			return $print_formats->filter( $filter_args );
		}

		return $print_formats;
	}

	/**
	 * @param $id
	 *
	 * @return false|PrintFormat
	 */
	public function get_print_format( $id ) {
		$print_formats = $this->get_print_formats();

		return $print_formats->get( $id );
	}

	protected function register_print_formats() {
	}

	protected function register_print_format( $id, $args = array() ) {
		if ( is_null( $this->print_formats ) ) {
			$this->print_formats = new PrintFormatList();
		}

		if ( is_a( $id, 'Vendidero\Shiptastic\ShippingProvider\PrintFormat' ) ) {
			$this->print_formats->add( $id );

			return true;
		} else {
			$args['id'] = $id;

			try {
				$this->print_formats->add( new PrintFormat( $this, $args ) );

				return true;
			} catch ( Exception $e ) {
				return new \WP_Error( 'register-print-format', $e->getMessage() );
			}
		}
	}

	/**
	 * @return ServiceList
	 * @param array $filter_args
	 */
	public function get_services( $filter_args = array() ) {
		if ( is_null( $this->services ) ) {
			$this->services = new ServiceList();
			$this->register_services();
		}

		$services = $this->services;

		if ( ! empty( $filter_args ) ) {
			return $services->filter( $filter_args );
		}

		return $services;
	}

	/**
	 * @param $id
	 *
	 * @return false|Service
	 */
	public function get_service( $id ) {
		return $this->get_services()->get( $id );
	}

	protected function register_services() {
	}

	/**
	 * @param string|Service $id
	 * @param $args
	 *
	 * @return true|\WP_Error
	 */
	protected function register_service( $id, $args = array() ) {
		if ( is_null( $this->services ) ) {
			$this->services = new ServiceList();
		}

		if ( is_a( $id, 'Vendidero\Shiptastic\ShippingProvider\Service' ) ) {
			$this->services->add( $id );

			return true;
		} else {
			$args['id'] = $id;

			try {
				$this->services->add( new Service( $this, $args ) );

				return true;
			} catch ( Exception $e ) {
				return new \WP_Error( 'register-service', $e->getMessage() );
			}
		}
	}

	public function get_supported_shipment_types() {
		$shipment_types = array();

		foreach ( wc_stc_get_shipment_types() as $shipment_type ) {
			if ( $this->supports_labels( $shipment_type ) ) {
				$shipment_types[] = $shipment_type;
			}
		}

		return $shipment_types;
	}

	public function save() {
		$id = parent::save();

		if ( $cache = \Vendidero\Shiptastic\Caches\Helper::get_cache_object( 'shipping-providers' ) ) {
			$cache->remove( $this->get_name() );
		}

		return $id;
	}
}
