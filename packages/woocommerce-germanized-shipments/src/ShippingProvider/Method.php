<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Exception;
use Vendidero\Germanized\Shipments\Package;
use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class       WC_GZD_Shipment_Order
 * @version     1.0.0
 * @author      Vendidero
 */
class Method {

	/**
	 * The actual method object
	 *
	 * @var WC_Shipping_Method
	 */
	protected $method = false;

	protected $instance_form_fields = array();

	protected $is_placeholder = false;

	protected $placeholder_instance_id = '';

	protected $placeholder_id = '';

	protected $provider_slug = null;

	/**
	 * @param WC_Shipping_Method|\WC_Shipping_Rate|mixed $method
	 * @param boolean $is_placeholder
	 */
	public function __construct( $method, $is_placeholder = false ) {
		if ( ! $is_placeholder ) {
			$this->method = $method;
			$this->init();
		} else {
			$this->is_placeholder = true;
			$this->init_placeholder( $method );
		}
	}

	protected function init_placeholder( $id ) {
		if ( is_a( $id, 'WC_Shipping_Rate' ) ) {
			$instance_id = $id->get_instance_id();
			$id          = $id->get_id();

			if ( strpos( $id, ':' ) === false ) {
				$id = $id . ':' . $instance_id;
			}
		} elseif ( is_a( $id, 'WC_Shipping_Method' ) ) {
			$instance_id = $id->get_instance_id();
			$id          = $id->id;

			if ( strpos( $id, ':' ) === false ) {
				$id = $id . ':' . $instance_id;
			}
		}

		if ( ! is_numeric( $id ) ) {
			$expl        = explode( ':', $id );
			$instance_id = ( ( ! empty( $expl ) && count( $expl ) > 1 ) ? $expl[1] : 0 );
			$id          = ( ( ! empty( $expl ) && count( $expl ) > 1 ) ? $expl[0] : $id );
		} else {
			$instance_id = $id;
		}

		$this->placeholder_id          = $id;
		$this->placeholder_instance_id = $instance_id;

		$this->instance_form_fields = Package::get_method_settings();
	}

	public function get_fallback_setting_value( $setting_key ) {
		$setting_key   = $this->maybe_prefix_key( $setting_key );
		$setting_value = '';

		/**
		 * In case the setting belongs to the current shipping provider
		 * lets allow overriding the fallback setting with data from the provider.
		 */
		if ( ( $provider = $this->get_provider_instance() ) && $this->setting_belongs_to_provider( $setting_key ) ) {
			$setting_value = $provider->get_setting( $setting_key );
		}

		if ( is_null( $setting_value ) ) {
			$setting_value = Package::get_setting( $setting_key, null );
		}

		/**
		 * Convert booleans to string options
		 */
		if ( is_bool( $setting_value ) ) {
			$setting_value = wc_bool_to_string( $setting_value );
		}

		return apply_filters( "{$this->get_hook_prefix()}setting_fallback_value", $setting_value, $setting_key, $this );
	}

	protected function supports_instance_settings() {
		if ( $this->is_placeholder() ) {
			return false;
		} else {
			$supports_settings = ( $this->method->supports( 'instance-settings' ) ) ? true : false;

			return apply_filters( 'woocommerce_gzd_shipping_provider_method_supports_instance_settings', $supports_settings, $this );
		}
	}

	public function is_placeholder() {
		return true === $this->is_placeholder;
	}

	/**
	 * Get all available shipping method settings. This method (re-) loads all
	 * the settings available across every registered shipping provider.
	 * Call the cached version instead for performance improvements.
	 *
	 * @see Package::get_method_settings()
	 *
	 * @return mixed|void
	 */
	public static function get_admin_settings() {
		/**
		 * Filter to adjust admin settings added to the shipment method instance specifically for shipping providers.
		 *
		 * @param array $settings Admin setting fields.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		$settings = apply_filters(
			'woocommerce_gzd_shipping_provider_method_admin_settings',
			array(
				'shipping_provider_title' => array(
					'title'       => _x( 'Shipping Provider Settings', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'title',
					'default'     => '',
					'description' => _x( 'Adjust shipping provider settings used for managing shipments.', 'shipments', 'woocommerce-germanized' ),
				),
				'shipping_provider'       => array(
					'title'       => _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'select',
					/**
					 * Filter to adjust default shipping provider pre-selected within shipping provider method settings.
					 *
					 * @param string $provider_name The shipping provider name e.g. dhl.
					 *
					 * @since 3.0.6
					 * @package Vendidero/Germanized/Shipments
					 */
					'default'     => apply_filters( 'woocommerce_gzd_shipping_provider_method_default_provider', '' ),
					'options'     => wc_gzd_get_shipping_provider_select(),
					'description' => _x( 'Choose a shipping provider which will be selected by default for an eligible shipment.', 'shipments', 'woocommerce-germanized' ),
				),
			)
		);

		foreach ( wc_gzd_get_shipping_providers() as $provider ) {
			if ( ! $provider->is_activated() ) {
				continue;
			}

			$additional_settings = $provider->get_shipping_method_settings();
			$settings            = array_merge( $settings, $additional_settings );
		}

		/**
		 * Append a stop title to make sure the table is closed within settings.
		 */
		$settings = array_merge(
			$settings,
			array(
				'shipping_provider_stop_title' => array(
					'title'   => '',
					'type'    => 'title',
					'default' => '',
				),
			)
		);

		return apply_filters( 'woocommerce_gzd_shipping_provider_method_admin_settings_wrapped', $settings );
	}

	protected function init() {
		$this->instance_form_fields = Package::get_method_settings();

		if ( ! array_key_exists( 'shipping_provider', $this->get_method()->instance_form_fields ) ) {
			$this->get_method()->instance_form_fields = array_merge( $this->get_method()->instance_form_fields, $this->instance_form_fields );
		}

		// Refresh instance settings in case they were already loaded
		if ( ! empty( $this->get_method()->instance_settings ) ) {
			$this->get_method()->init_instance_settings();
		}
	}

	protected function get_hook_prefix() {
		$prefix = 'woocommerce_gzd_shipping_provider_method_';

		return $prefix;
	}

	/**
	 * Returns the Woo WC_Shipping_Method original object
	 *
	 * @return object|WC_Shipping_Method
	 */
	public function get_method() {
		return $this->method;
	}

	public function get_id() {
		if ( ! $this->is_placeholder() ) {
			return $this->method->id;
		} else {
			return $this->placeholder_id;
		}
	}

	public function get_instance_id() {
		if ( ! $this->is_placeholder() ) {
			return $this->method->get_instance_id();
		} else {
			return $this->placeholder_instance_id;
		}
	}

	public function has_option( $key ) {
		$fields     = $this->instance_form_fields;
		$key        = $this->maybe_prefix_key( $key );
		$has_option = ( array_key_exists( $key, $fields ) && $this->setting_belongs_to_provider( $key ) ) ? true : false;

		/**
		 * Filter that allows checking whether a shipping provider method has a specific option or not.
		 *
		 * @param boolean $has_option Whether or not the option exists.
		 * @param string  $key The setting key.
		 * @param Method  $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( "{$this->get_hook_prefix()}setting_prefix", $has_option, $key, $this );
	}

	public function setting_belongs_to_provider( $setting_key, $provider = '' ) {
		$prefix = $this->get_custom_setting_prefix_key();

		if ( ! empty( $provider ) ) {
			$prefix = $provider . '_';
		}

		$belongs_to_provider = false;

		if ( ! empty( $prefix ) && substr( $setting_key, 0, strlen( $prefix ) ) === $prefix ) {
			$belongs_to_provider = true;
		}

		return $belongs_to_provider;
	}

	public function is_provider_enabled( $provider ) {
		return ( $this->get_provider() === $provider ) ? true : false;
	}

	public function set_provider( $provider_name ) {
		$this->provider_slug = $provider_name;
	}

	public function get_provider() {
		$id = sanitize_key( $this->get_id() );

		if ( is_null( $this->provider_slug ) ) {
			$provider_slug = $this->method ? $this->method->get_option( 'shipping_provider' ) : '';

			if ( ! empty( $provider_slug ) ) {
				if ( $provider = wc_gzd_get_shipping_provider( $provider_slug ) ) {
					if ( ! $provider->is_activated() ) {
						$provider_slug = '';
					}
				}
			}

			if ( empty( $provider_slug ) ) {
				$provider_slug = wc_gzd_get_default_shipping_provider();
			}

			/**
			 * Filter that allows adjusting the shipping provider chosen for a specific shipping method.
			 *
			 * @param string $provider_slug The shipping provider.
			 * @param string $method_id The shipping method id.
			 * @param Method $method The method instance.
			 *
			 * @since 3.0.6
			 * @package Vendidero/Germanized/Shipments
			 */
			$this->provider_slug = apply_filters( 'woocommerce_gzd_shipping_provider_method_provider', $provider_slug, $this->get_id(), $this );
		}

		/**
		 * Filter that allows choosing a shipping provider for a specific shipping method.
		 *
		 * The dynamic portion of this hook, `$id` refers to the shipping method id.
		 *
		 * Example hook name: `woocommerce_gzd_shipping_provider_method_flat_rate_provider`
		 *
		 * @param string $provider_slug The shipping provider name to be used.
		 * @param Method $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( "{$this->get_hook_prefix()}{$id}_provider", $this->provider_slug, $this );
	}

	public function get_provider_instance() {
		$provider_slug = $this->get_provider();

		if ( ! empty( $provider_slug ) ) {
			return wc_gzd_get_shipping_provider( $provider_slug );
		}

		return false;
	}

	protected function get_custom_setting_prefix_key() {
		$prefix = '';

		if ( $provider = $this->get_provider_instance() ) {
			$prefix = $provider->get_name() . '_';
		}

		return apply_filters( "{$this->get_hook_prefix()}custom_setting_prefix", $prefix, $this );
	}

	protected function maybe_prefix_key( $key ) {
		$fields  = $this->instance_form_fields;
		$prefix  = $this->get_custom_setting_prefix_key();
		$new_key = $key;

		// Do only prefix if the prefix does not yet exist.
		if ( ! array_key_exists( $new_key, $fields ) ) {
			if ( substr( $key, 0, strlen( $prefix ) ) !== $prefix ) {
				$new_key = $prefix . $key;
			}
		}

		/**
		 * Filter that allows prefixing the setting key used for a shipping provider method.
		 *
		 * @param string $new_key The prefixed setting key.
		 * @param string $key The original setting key.
		 * @param Method $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( "{$this->get_hook_prefix()}setting_key_prefixed", $new_key, $key, $this );
	}

	public function get_option( $key ) {
		$key          = $this->maybe_prefix_key( $key );
		$option_value = $this->get_fallback_setting_value( $key );

		if ( ! $this->is_placeholder() ) {
			if ( $this->has_option( $key ) && $this->supports_instance_settings() ) {
				$option_type = isset( $this->instance_form_fields[ $key ]['type'] ) ? $this->instance_form_fields[ $key ]['type'] : 'text';

				// Do only use method settings if the method is not a placeholder and method supports settings
				$option_value = $this->method->get_option( $key, $option_value );

				if ( in_array( $option_type, array( 'checkbox', 'radio' ), true ) ) {
					$option_value = wc_string_to_bool( $option_value );

					if ( $option_value ) {
						$option_value = 'yes';
					} else {
						$option_value = 'no';
					}
				}
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}setting_value", $option_value, $key, $this );
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {

		if ( method_exists( $this->method, $method ) ) {
			return call_user_func_array( array( $this->method, $method ), $args );
		}

		return false;
	}
}
