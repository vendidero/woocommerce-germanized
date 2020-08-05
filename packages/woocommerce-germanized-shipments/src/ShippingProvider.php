<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

use Exception;
use WC_Data;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

class ShippingProvider extends WC_Data  {

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
		'supports_customer_returns'  => false,
		'supports_guest_returns'     => false,
		'return_manual_confirmation' => true,
		'return_instructions'        => '',
		'tracking_url_placeholder'   => '',
		'tracking_desc_placeholder'  => '',
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
		} elseif( is_object( $data ) && isset( $data->shipping_provider_id ) ) {
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
	 * Whether or not this instance is a manual integration.
	 * Manual integrations are constructed dynamically from DB and do not support
	 * automatic shipment handling, e.g. label creation.
	 *
	 * @return bool
	 */
	public function is_manual_integration() {
		return true;
	}

	public function get_additional_options_url() {
		return '';
	}

	/**
	 * Whether or not this instance supports a certain label type.
	 *
	 * @param string $label_type The label type e.g. simple or return.
	 *
	 * @return bool
	 */
	public function supports_labels( $label_type ) {
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
		return $this->supports_labels( 'return' ) ? true : false;
	}

	public function get_edit_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=provider&provider=' . esc_attr( $this->get_name() ) );
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

	public function supports_customer_returns() {
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
		return $this->get_prop( 'tracking_url_placeholder', $context );
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
		return $this->get_prop( 'tracking_desc_placeholder', $context );
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

	/**
	 * Activate current ShippingProvider instance.
	 */
	public function activate() {
		$this->set_activated( true );
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
	public function get_tracking_desc( $shipment ) {

		$tracking_desc = '';
		$tracking_id   = $shipment->get_tracking_id();

		if ( '' !== $this->get_tracking_desc_placeholder() && ! empty( $tracking_id ) ) {
			$placeholders  = $this->get_tracking_placeholders( $shipment );
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

	protected function set_prop( $prop, $value ) {
		parent::set_prop( $prop, $value );
	}

	/**
	 * @param bool|Shipment $shipment
	 *
	 * @return array
	 */
	public function get_tracking_placeholders( $shipment = false ) {
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
		return apply_filters( "{$this->get_hook_prefix()}tracking_placeholders", array(
			'{shipment_number}'   => $shipment ? $shipment->get_shipment_number() : '',
			'{order_number}'      => $shipment ? $shipment->get_order_number() : '',
			'{tracking_id}'       => $shipment ? $shipment->get_tracking_id() : '',
			'{shipping_provider}' => $this->get_title()
		), $this, $shipment );
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return "woocommerce_gzd_shipping_provider_get_";
	}

	public function get_settings() {
		$desc = '';

		if ( ! $this->is_manual_integration() && $this->get_additional_options_url() ) {
			$desc = sprintf( _x( '%s supports many more options. Explore %s.', 'shipments', 'woocommerce-germanized' ), $this->get_title(), '<a class="" href="' . $this->get_additional_options_url() . '" target="_blank">' . sprintf( _x( '%s specific settings', 'shipments', 'woocommerce-germanized' ), $this->get_title() ) . '</a>' );
		}

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'shipping_provider_options', 'desc' => $desc ),
		);

		if ( $this->is_manual_integration() ) {
			$settings = array_merge( $settings, array(
				array(
					'title' 	        => _x( 'Title', 'shipments', 'woocommerce-germanized' ),
					'desc_tip' 		    => _x( 'Choose a title for the shipping provider.', 'shipments', 'woocommerce-germanized' ),
					'id' 		        => 'shipping_provider_title',
					'value'             => $this->get_title( 'edit' ),
					'default'	        => '',
					'type' 		        => 'text',
				),

				array(
					'title' 	        => _x( 'Description', 'shipments', 'woocommerce-germanized' ),
					'desc_tip' 		    => _x( 'Choose a description for the shipping provider.', 'shipments', 'woocommerce-germanized' ),
					'id' 		        => 'shipping_provider_description',
					'value'             => $this->get_description( 'edit' ),
					'default'	        => '',
					'type' 		        => 'textarea',
					'css'               => 'width: 100%;',
				),
			) );
		}

		$settings = array_merge( $settings, array(
			array(
				'title' 	        => _x( 'Tracking URL', 'shipments', 'woocommerce-germanized' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Adjust the placeholder used to construct the tracking URL for this shipping provider. You may use on of the following placeholders to insert the tracking id or other dynamic data: %s', 'shipments', 'woocommerce-germanized' ), '<code>' . implode( ', ', array_keys( $this->get_tracking_placeholders() ) ) . '</code>' ) . '</div>',
				'id' 		        => 'shipping_provider_tracking_url_placeholder',
				'placeholder'       => 'https://www.dhl.de/privatkunden/pakete-empfangen/verfolgen.html?idc={tracking_id}',
				'value'             => $this->get_tracking_url_placeholder( 'edit' ),
				'default'	        => $this->get_default_tracking_url_placeholder(),
				'type' 		        => 'text',
				'css'               => 'width: 100%;',
			),

			array(
				'title' 	        => _x( 'Tracking description', 'shipments', 'woocommerce-germanized' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Adjust the placeholder used to construct the tracking description for this shipping provider (e.g. used within notification emails). You may use on of the following placeholders to insert the tracking id or other dynamic data: %s', 'shipments', 'woocommerce-germanized' ), '<code>' . implode( ', ', array_keys( $this->get_tracking_placeholders() ) ) . '</code>' ) . '</div>',
				'id' 		        => 'shipping_provider_tracking_desc_placeholder',
				'placeholder'       => '',
				'value'             => $this->get_tracking_desc_placeholder( 'edit' ),
				'default'	        => $this->get_default_tracking_desc_placeholder(),
				'type' 		        => 'textarea',
				'css'               => 'width: 100%; min-height: 60px; margin-top: 1em;',
			),

			array(
				'title' 	        => _x( 'Customer returns', 'shipments', 'woocommerce-germanized' ),
				'desc'              => _x( 'Allow customers to submit return requests to shipments.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'This option will allow your customers to submit return requests to orders. Return requests will be visible within your %s. To learn more about return requests by customers and/or guests, please check the %s.', 'shipments', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-gzd-return-shipments' ) . '">' . _x( 'Return Dashboard', 'shipments', 'woocommerce-germanized' ) . '</a>', '<a href="https://vendidero.de/dokument/retouren-konfigurieren-und-verwalten" target="_blank">' . _x( 'docs', 'shipments', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id' 		        => 'shipping_provider_supports_customer_returns',
				'placeholder'       => '',
				'value'             => $this->get_supports_customer_returns( 'edit' ) ? 'yes' : 'no',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Guest returns', 'shipments', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Allow guests to submit return requests to shipments.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Guests will need to provide their email address and the order id to receive a one-time link to submit a return request. The placeholder %s might be used to place the request form on your site.', 'shipments', 'woocommerce-germanized' ), '<code>[gzd_return_request_form]</code>' ) . '</div>',
				'id' 		        => 'shipping_provider_supports_guest_returns',
				'default'	        => 'no',
				'value'             => $this->get_supports_guest_returns( 'edit' ) ? 'yes' : 'no',
				'type' 		        => 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_shipping_provider_supports_customer_returns' => '',
				),
			),

			array(
				'title' 	        => _x( 'Manual confirmation', 'shipments', 'woocommerce-germanized' ),
				'desc'              => _x( 'Return requests need manual confirmation.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'By default return request need manual confirmation e.g. a shop manager needs to review return requests which by default are added with the status "requested" after a customer submitted a return request. If you choose to disable this option, customer return requests will be added as "processing" and an email confirmation including instructions will be sent immediately to the customer.', 'shipments', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'shipping_provider_return_manual_confirmation',
				'placeholder'       => '',
				'value'             => $this->get_return_manual_confirmation( 'edit' ) ? 'yes' : 'no',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_shipping_provider_supports_customer_returns' => '',
				),
			),

			array(
				'title' 	        => _x( 'Return instructions', 'shipments', 'woocommerce-germanized' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Provide your customer with instructions on how to return the shipment after a return request has been confirmed e.g. explain how to prepare the return for shipment. In case a label cannot be generated automatically, make sure to provide your customer with information on how to obain a return label.', 'shipments', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'shipping_provider_return_instructions',
				'placeholder'       => '',
				'value'             => $this->get_return_instructions( 'edit' ),
				'default'	        => '',
				'type' 		        => 'textarea',
				'css'               => 'width: 100%; min-height: 60px; margin-top: 1em;',
				'custom_attributes' => array(
					'data-show_if_shipping_provider_supports_customer_returns' => '',
				),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_provider_options' ),
		) );

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
		return apply_filters( $this->get_hook_prefix() . 'settings', $settings, $this );
	}

	public function save() {
		return parent::save();
	}
}
