<?php

namespace Vendidero\Shiptastic\Compatibility;

use Vendidero\Shiptastic\Interfaces\Compatibility;
use Vendidero\Shiptastic\ShippingProvider\Helper;
use Vendidero\Shiptastic\ShippingProvider\Simple;

defined( 'ABSPATH' ) || exit;

class WPML implements Compatibility {

	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	public static function init() {
		/**
		 * Register custom strings (e.g. tracking description placeholder) via WPML. These strings might be translated through
		 * the translation dashboard (admin.php?page=wpml-translation-management). Use "Shipping Provider" as a filter/kind for translating.
		 */
		add_action( 'woocommerce_shiptastic_new_shipping_provider', array( __CLASS__, 'register_shipping_provider_strings' ), 10, 2 );
		add_action( 'woocommerce_shiptastic_shipping_provider_updated', array( __CLASS__, 'register_shipping_provider_strings' ), 10, 2 );

		/**
		 * Prevent infinite loop as WPML, by default, saves order items when retrieving items via WC_Order::get_items() which
		 * will trigger a new shipment validation.
		 */
		add_action(
			'woocommerce_shiptastic_before_validate_shipments',
			function () {
				add_filter( 'wcml_should_save_adjusted_order_item_in_language', array( __CLASS__, 'prevent_order_item_translation' ), 99 );
			}
		);

		add_action(
			'woocommerce_shiptastic_after_validate_shipments',
			function () {
				remove_filter( 'wcml_should_save_adjusted_order_item_in_language', array( __CLASS__, 'prevent_order_item_translation' ), 99 );
			}
		);

		/**
		 * The shipping provider filter name depends on the instance name - register filters while loading providers.
		 */
		if ( did_action( 'woocommerce_shiptastic_load_shipping_providers' ) ) {
			self::register_provider_filters();
		} else {
			add_action( 'woocommerce_shiptastic_load_shipping_providers', array( __CLASS__, 'register_provider_filters' ) );
		}

		/**
		 * Translate shipment item name
		 */
		add_filter( 'woocommerce_shiptastic_email_shipment_items_args', array( __CLASS__, 'translate_email_shipment_items' ), 10 );

		add_filter(
			'woocommerce_shiptastic_shipping_method_shipping_classes',
			function ( $shipping_classes ) {
				$shipping_classes = array_map(
					function ( $class_id ) {
						return apply_filters( 'wpml_object_id', $class_id, 'category' );
					},
					$shipping_classes
				);

				return $shipping_classes;
			}
		);
	}

	public static function prevent_order_item_translation() {
		return false;
	}

	public static function translate_email_shipment_items( $args ) {
		$shipment = $args['shipment'];

		if ( $order = wc_get_order( $shipment->get_order_id() ) ) {
			$language = $order->get_meta( 'wpml_language', true );

			foreach ( $args['items'] as $key => $item ) {
				$id = $item->get_product_id();
				$id = apply_filters( 'wpml_object_id', $id, get_post_type( $id ), true, $language );

				if ( $product = wc_get_product( $id ) ) {
					$args['items'][ $key ]->set_name( $product->get_name() );
				}
			}
		}

		return $args;
	}

	public static function register_provider_filters() {
		add_filter( 'woocommerce_shiptastic_shipping_provider_get_tracking_desc_placeholder', array( __CLASS__, 'filter_shipping_provider_placeholder' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipping_provider_get_tracking_url_placeholder', array( __CLASS__, 'filter_shipping_provider_url' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipping_provider_get_return_instructions', array( __CLASS__, 'filter_shipping_provider_return_instructions' ), 10, 2 );

		foreach ( Helper::instance()->get_shipping_providers() as $provider ) {
			add_filter( "woocommerce_shiptastic_shipping_provider_{$provider->get_name()}_get_tracking_desc_placeholder", array( __CLASS__, 'filter_shipping_provider_placeholder' ), 10, 2 );
			add_filter( "woocommerce_shiptastic_shipping_provider_{$provider->get_name()}_get_tracking_url_placeholder", array( __CLASS__, 'filter_shipping_provider_url' ), 10, 2 );
			add_filter( "woocommerce_shiptastic_shipping_provider_{$provider->get_name()}_get_return_instructions", array( __CLASS__, 'filter_shipping_provider_return_instructions' ), 10, 2 );
		}
	}

	public static function filter_shipping_provider_return_instructions( $instructions, $provider ) {
		$string_name       = 'return_instructions';
		$translated_string = apply_filters( 'wpml_translate_string', $instructions, self::get_shipping_provider_string_id( $string_name, $provider ), self::get_shipping_provider_string_package( $string_name, $provider ) );

		return $translated_string;
	}

	public static function filter_shipping_provider_url( $placeholder, $provider ) {
		$string_name       = 'tracking_url_placeholder';
		$translated_string = apply_filters( 'wpml_translate_string', $placeholder, self::get_shipping_provider_string_id( $string_name, $provider ), self::get_shipping_provider_string_package( $string_name, $provider ) );

		return $translated_string;
	}

	public static function filter_shipping_provider_placeholder( $placeholder, $provider ) {
		$string_name       = 'tracking_desc_placeholder';
		$translated_string = apply_filters( 'wpml_translate_string', $placeholder, self::get_shipping_provider_string_id( $string_name, $provider ), self::get_shipping_provider_string_package( $string_name, $provider ) );

		return $translated_string;
	}

	/**
	 * @param integer $provider_id
	 * @param Simple $provider
	 */
	public static function register_shipping_provider_strings( $provider_id, $provider ) {
		foreach ( self::get_shipping_provider_strings() as $string_name => $title ) {
			$title  = sprintf( $title, $provider->get_title() );
			$getter = "get_{$string_name}";

			if ( is_callable( array( $provider, $getter ) ) ) {
				$value = $provider->{$getter}();

				do_action( 'wpml_register_string', $value, self::get_shipping_provider_string_id( $string_name, $provider ), self::get_shipping_provider_string_package( $string_name, $provider ), $title, 'AREA' );
			}
		}
	}

	protected static function get_shipping_provider_strings() {
		$strings = array(
			'tracking_desc_placeholder' => _x( '%s tracking description', 'shipments', 'woocommerce-germanized' ),
			'tracking_url_placeholder'  => _x( '%s tracking URL', 'shipments', 'woocommerce-germanized' ),
			'return_instructions'       => _x( '%s return instructions', 'shipments', 'woocommerce-germanized' ),
		);

		return $strings;
	}

	/**
	 * @param $string_name
	 * @param Simple $provider
	 */
	protected static function get_shipping_provider_string_id( $string_name, $provider ) {
		return "woocommerce_shiptastic_shipping_provider_{$provider->get_name()}_{$string_name}";
	}

	/**
	 * @param $string_name
	 * @param Simple $provider
	 */
	protected static function get_shipping_provider_string_package( $string_name, $provider ) {
		$strings = self::get_shipping_provider_strings();
		$package = array();

		if ( array_key_exists( $string_name, $strings ) ) {
			$title = sprintf( $strings[ $string_name ], $provider->get_title() );

			$package = array(
				'kind'      => 'Shipping Provider',
				'name'      => "{$provider->get_name()}_{$string_name}",
				'edit_link' => $provider->get_edit_link(),
				'title'     => $title,
			);
		}

		return $package;
	}

	/**
	 * @param $emails
	 */
	public static function register_emails( $emails ) {
		$emails['WC_STC_Email_Customer_Shipment']                      = 'customer_shipment';
		$emails['WC_STC_Email_Customer_Return_Shipment']               = 'customer_return_shipment';
		$emails['WC_STC_Email_Customer_Return_Shipment_Delivered']     = 'customer_return_shipment_delivered';
		$emails['WC_STC_Email_Customer_Guest_Return_Shipment_Request'] = 'customer_guest_return_shipment_request';

		return $emails;
	}
}
