<?php

namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

class Shipments {

	public static function init() {
		self::setup_integration();
		self::setup_backwards_compatibility();
	}

	protected static function setup_backwards_compatibility() {
		add_filter( 'woocommerce_shiptastic_shipping_provider_class_names', array( __CLASS__, 'legacy_filter_callback' ), 10, 1 );
		add_filter( 'woocommerce_shiptastic_order_shipping_statuses', array( __CLASS__, 'legacy_filter_callback' ), 10, 1 );
		add_filter( 'woocommerce_shiptastic_return_shipment_reasons', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_editable_statuses', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_shipment_sent_statuses', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_default_shipping_provider', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_order_is_returnable_by_customer', array( __CLASS__, 'legacy_filter_callback' ), 10, 3 );
		add_filter( 'woocommerce_shiptastic_get_order_shipping_provider', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_additional_costs_include_tax', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_shipment_get_shipment_number', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );

		add_action( 'woocommerce_shiptastic_init', array( __CLASS__, 'legacy_action_callback' ), 10 );
		add_action( 'woocommerce_shiptastic_shipment_created_label', array( __CLASS__, 'legacy_action_callback' ), 10, 2 );
		add_action( 'woocommerce_shiptastic_return_shipment_created_label', array( __CLASS__, 'legacy_action_callback' ), 10, 2 );
		add_action( 'woocommerce_shiptastic_shipment_item_meta', array( __CLASS__, 'legacy_action_callback' ), 10, 4 );

		add_filter(
			'woocommerce_shiptastic_shipment_statuses',
			function ( $statuses ) {
				$gzd_additional_statuses = apply_filters( 'woocommerce_gzd_shipment_statuses', $statuses );

				foreach ( $gzd_additional_statuses as $status_key => $status_title ) {
					$statuses[ self::remove_gzd_status_prefix( $status_key ) ] = $status_title;
				}

				return $statuses;
			}
		);

		add_filter(
			'woocommerce_shiptastic_order_return_statuses',
			function ( $statuses ) {
				$gzd_additional_statuses = apply_filters( 'woocommerce_gzd_order_return_statuses', $statuses );

				foreach ( $gzd_additional_statuses as $status_key => $status_title ) {
					$statuses[ self::remove_gzd_status_prefix( $status_key ) ] = $status_title;
				}

				return $statuses;
			}
		);
	}

	protected static function remove_gzd_status_prefix( $status ) {
		return 'gzd-' === substr( $status, 0, 4 ) ? substr( $status, 4 ) : $status;
	}

	public static function legacy_filter_callback( ...$args ) {
		$filter_name        = current_filter();
		$legacy_filter_name = str_replace( 'woocommerce_shiptastic_', 'woocommerce_gzd_', $filter_name );

		return apply_filters( "{$legacy_filter_name}", ...$args );
	}

	public static function legacy_action_callback( ...$args ) {
		$filter_name = current_filter();

		if ( in_array( $filter_name, array( 'woocommerce_shiptastic_init' ), true ) ) {
			$legacy_filter_name = str_replace( 'woocommerce_shiptastic_', 'woocommerce_gzd_shipments_', $filter_name );
		} else {
			$legacy_filter_name = str_replace( 'woocommerce_shiptastic_', 'woocommerce_gzd_', $filter_name );
		}

		do_action( "{$legacy_filter_name}", ...$args );
	}

	protected static function setup_integration() {
		add_filter(
			'woocommerce_shiptastic_parse_shipment_status',
			function ( $status ) {
				return self::remove_gzd_status_prefix( $status );
			},
			10
		);

		add_filter(
			'woocommerce_shiptastic_is_provider_integration_active',
			function ( $is_active, $provider_name ) {
				if ( in_array( $provider_name, array( 'dhl', 'deutsche_post' ), true ) ) {
					$is_active = true;
				}

				return $is_active;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_shiptastic_additional_costs_include_tax',
			function () {
				return wc_gzd_additional_costs_include_tax();
			}
		);

		add_filter(
			'woocommerce_shiptastic_template_path',
			function () {
				return Package::get_template_path();
			}
		);

		add_filter(
			'woocommerce_gzd_dhl_get_i18n_path',
			function () {
				return Package::get_language_path();
			}
		);

		add_filter(
			'woocommerce_shiptastic_get_i18n_path',
			function () {
				return Package::get_language_path();
			}
		);

		add_filter(
			'woocommerce_gzd_dhl_get_i18n_textdomain',
			function () {
				return 'woocommerce-germanized';
			}
		);

		add_filter(
			'woocommerce_shiptastic_get_i18n_textdomain',
			function () {
				return 'woocommerce-germanized';
			}
		);

		add_filter(
			'woocommerce_shiptastic_shipment_order_supports_email_transmission',
			function ( $supports_email_transmission, $order ) {
				if ( wc_gzd_order_supports_parcel_delivery_reminder( $order->get_id() ) ) {
					$supports_email_transmission = true;
				}

				return $supports_email_transmission;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_shiptastic_last_tutorial_url',
			function () {
				return admin_url( 'admin.php?page=wc-settings&tab=germanized-emails&tutorial=yes' );
			}
		);

		add_filter(
			'woocommerce_shiptastic_encryption_key_constant',
			function () {
				return 'WC_GZD_ENCRYPTION_KEY';
			}
		);
	}
}
