<?php

namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

class Shiptastic {

	public static function init() {
		self::setup_integration();
		self::setup_backwards_compatibility();

		include_once Package::get_path() . '/includes/wc-gzd-shipments-legacy-functions.php';
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
		add_filter( 'woocommerce_shiptastic_is_provider_integration_active', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_is_pro', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_shipments_table_actions', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipments_table_bulk_actions', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_table_bulk_action_handlers', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_shipments_table_columns', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_shipping_provider_get_tracking_placeholders', array( __CLASS__, 'legacy_filter_callback' ), 10, 3 );
		add_filter( 'woocommerce_shiptastic_order_completed_status', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_order_completed_status', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_local_pickup_shipping_methods', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_find_available_packaging_for_shipment', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_label_supports_third_party_email_notification', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_return_label_supports_third_party_email_notification', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_order_needs_shipping', array( __CLASS__, 'legacy_filter_callback' ), 10, 3 );
		add_filter( 'woocommerce_shiptastic_enable_rucksack_packaging', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_embed_shipment_details_in_notification', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_order_supports_email_transmission', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipping_provider_dhl_get_label_default_shipment_weight', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_order_shippable_items', array( __CLASS__, 'legacy_filter_callback' ), 10, 3 );
		add_filter( 'woocommerce_shiptastic_enable_pickup_delivery', array( __CLASS__, 'legacy_filter_callback' ), 10 );

		add_action( 'woocommerce_shiptastic_init', array( __CLASS__, 'legacy_action_callback' ), 10 );
		add_action( 'woocommerce_shiptastic_shipment_created_label', array( __CLASS__, 'legacy_action_callback' ), 10, 2 );
		add_action( 'woocommerce_shiptastic_return_shipment_created_label', array( __CLASS__, 'legacy_action_callback' ), 10, 2 );
		add_action( 'woocommerce_shiptastic_shipment_item_meta', array( __CLASS__, 'legacy_action_callback' ), 10, 4 );
		add_action( 'woocommerce_shiptastic_meta_box_shipment_after_right_column', array( __CLASS__, 'legacy_action_callback' ), 10, 1 );
		add_action( 'woocommerce_shiptastic_shipment_deleted', array( __CLASS__, 'legacy_action_callback' ), 10, 1 );
		add_action( 'woocommerce_shiptastic_return_shipment_deleted', array( __CLASS__, 'legacy_action_callback' ), 10, 1 );
		add_action( 'woocommerce_shiptastic_shipment_before_status_change', array( __CLASS__, 'legacy_action_callback' ), 10, 3 );
		add_action( 'woocommerce_shiptastic_shipment_status_changed', array( __CLASS__, 'legacy_action_callback' ), 10, 4 );
		add_action( 'woocommerce_shiptastic_shipments_table_custom_column', array( __CLASS__, 'legacy_action_callback' ), 10, 2 );

		/**
		 * E-Mail Tracking in legacy templates
		 */
		add_action( 'woocommerce_gzd_email_shipment_details', array( __CLASS__, 'shiptastic_action_callback' ), 10, 4 );

		/**
		 * DHL Hooks
		 */
		add_filter( 'woocommerce_shiptastic_dhl_label_custom_format', array( __CLASS__, 'legacy_filter_callback' ), 10, 3 );
		add_filter( 'woocommerce_shiptastic_dhl_label_get_email_notification', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_dhl_label_get_weight', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_dhl_label_api_shipper_reference', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipping_provider_dhl_get_label_default_shipment_weight', array( __CLASS__, 'legacy_filter_callback' ), 10 );
		add_filter( 'woocommerce_shiptastic_dhl_label_api_communication_phone', array( __CLASS__, 'legacy_filter_callback' ), 10, 2 );

		/**
		 * Valid status name (remove gzd- prefix)
		 */
		add_filter( 'woocommerce_shiptastic_shipment_valid_status_slug', array( __CLASS__, 'remove_gzd_prefix_from_status' ), 10 );
		add_filter( 'woocommerce_shiptastic_return_shipment_valid_status_slug', array( __CLASS__, 'remove_gzd_prefix_from_status' ), 10 );

		/**
		 *  Status hooks
		 *
		 * @note: Return a legacy shipment object as PayPal Payments has a compatibility script which uses strict typing.
		 */
		add_action(
			'init',
			function () {
				foreach ( wc_stc_get_shipment_statuses() as $status_name => $title ) {
					add_action(
						"woocommerce_shiptastic_shipment_status_{$status_name}",
						function ( $shipment_id, $shipment ) {
							self::legacy_action_callback( $shipment_id, \Vendidero\Germanized\Shipments\Shipment::from_shiptastic( $shipment ) );
						},
						10,
						2
					);

					add_action(
						"woocommerce_shiptastic_return_shipment_status_{$status_name}",
						function ( $shipment_id, $shipment ) {
							self::legacy_action_callback( $shipment_id, \Vendidero\Germanized\Shipments\Shipment::from_shiptastic( $shipment ) );
						},
						10,
						2
					);
				}
			}
		);

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

		/**
		 * Shortcodes
		 */
		add_action(
			'init',
			function () {
				add_shortcode(
					'gzd_return_request_form',
					function ( $args = array() ) {
						return \Vendidero\Shiptastic\Package::return_request_form( $args );
					}
				);
			}
		);
	}

	public static function remove_gzd_prefix_from_status( $new_status ) {
		$new_status = 'gzd-' === substr( $new_status, 0, 4 ) ? substr( $new_status, 4 ) : $new_status;

		return $new_status;
	}

	public static function legacy_shipment_item_classname( $item_class, $item_id, $item_type ) {
		$item_class = 'Vendidero\Germanized\Shipments\ShipmentItem';

		if ( 'return' === $item_type ) {
			$item_class = 'Vendidero\Germanized\Shipments\ShipmentReturnItem';
		}

		return $item_class;
	}

	protected static function remove_gzd_status_prefix( $status ) {
		return 'gzd-' === substr( $status, 0, 4 ) ? substr( $status, 4 ) : $status;
	}

	public static function legacy_filter_callback( ...$args ) {
		$filter_name = self::get_legacy_hook_name( current_filter() );

		return apply_filters( "{$filter_name}", ...$args );
	}

	public static function legacy_action_callback( ...$args ) {
		$filter_name = self::get_legacy_hook_name( current_filter() );

		do_action( "{$filter_name}", ...$args );
	}

	public static function shiptastic_action_callback( ...$args ) {
		$filter_name = self::get_shiptastic_hook_name( current_filter() );

		do_action( "{$filter_name}", ...$args );
	}

	protected static function get_legacy_filters_with_prefix() {
		return array(
			'woocommerce_shiptastic_init',
			'woocommerce_shiptastic_is_provider_integration_active',
			'woocommerce_shiptastic_is_pro',
			'woocommerce_shiptastic_meta_box_shipment_after_right_column',
			'woocommerce_shiptastic_table_bulk_action_handlers',
			'woocommerce_shiptastic_enable_pickup_delivery',
		);
	}

	protected static function get_legacy_hook_name( $hook ) {
		if ( in_array( $hook, self::get_legacy_filters_with_prefix(), true ) ) {
			$hook = str_replace( 'woocommerce_shiptastic_', 'woocommerce_gzd_shipments_', $hook );
		} else {
			$hook = str_replace( 'woocommerce_shiptastic_', 'woocommerce_gzd_', $hook );
		}

		return $hook;
	}

	protected static function get_shiptastic_hook_name( $hook ) {
		$hook = str_replace( 'woocommerce_gzd_', 'woocommerce_shiptastic_', $hook );

		return $hook;
	}

	public static function get_shipping_provider_integrations_for_pro() {
		return array(
			'dpd'    => array(
				'title'               => _x( 'DPD', 'shipments', 'woocommerce-germanized' ),
				'countries_supported' => array( 'DE', 'AT' ),
				'is_builtin'          => false,
				'is_pro'              => true,
				'extension_name'      => 'dpd-for-shiptastic',
				'help_url'            => 'https://vendidero.de/woocommerce-germanized/features#providers',
			),
			'gls'    => array(
				'title'               => _x( 'GLS', 'shipments', 'woocommerce-germanized' ),
				'countries_supported' => array( 'DE', 'AT', 'CH', 'BE', 'LU', 'FR', 'IE', 'ES' ),
				'is_builtin'          => false,
				'is_pro'              => true,
				'extension_name'      => 'gls-for-shiptastic',
				'help_url'            => 'https://vendidero.de/woocommerce-germanized/features#providers',
			),
			'hermes' => array(
				'title'               => _x( 'Hermes', 'shipments', 'woocommerce-germanized' ),
				'countries_supported' => array( 'DE' ),
				'is_builtin'          => false,
				'is_pro'              => true,
				'extension_name'      => 'hermes-for-shiptastic',
				'help_url'            => 'https://vendidero.de/woocommerce-germanized/features#providers',
			),
		);
	}

	protected static function setup_integration() {
		/**
		 * Use this tweak to allow installing extensions which rely on Shiptastic as
		 * Shiptastic is currently bundled within the Germanized installation package.
		 *
		 * @TODO remove when updating to Germanized 4.0.0
		 */
		add_filter(
			'wp_plugin_dependencies_slug',
			function ( $slug ) {
				if ( 'shiptastic-for-woocommerce' === $slug ) {
					$slug = '';
				}

				return $slug;
			}
		);

		add_filter(
			'woocommerce_shiptastic_available_shipping_provider_integrations',
			function ( $integrations ) {
				$integrations = array_merge(
					$integrations,
					self::get_shipping_provider_integrations_for_pro()
				);

				return $integrations;
			}
		);

		add_filter(
			'woocommerce_shiptastic_shipment_order_min_age',
			function ( $min_age, $order ) {
				$custom_age = wc_gzd_get_order_min_age( $order->get_id() );

				if ( false !== $custom_age ) {
					$min_age = $custom_age;
				}

				return $min_age;
			},
			10,
			2
		);

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
			'woocommerce_shiptastic_dhl_get_i18n_path',
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
			'woocommerce_shiptastic_dhl_get_i18n_textdomain',
			function () {
				return 'woocommerce-germanized';
			}
		);

		add_filter( 'woocommerce_shiptastic_is_debug_mode', 'wc_gzd_is_extended_debug_mode_enabled', 5 );

		add_filter(
			'woocommerce_shiptastic_get_i18n_textdomain',
			function () {
				return 'woocommerce-germanized';
			}
		);

		add_filter(
			'woocommerce_gzd_wpml_email_ids',
			function ( $emails_ids ) {
				if ( is_callable( array( '\Vendidero\Shiptastic\Compatibility\WPML', 'register_emails' ) ) ) {
					$emails_ids = \Vendidero\Shiptastic\Compatibility\WPML::register_emails( $emails_ids );
				}

				return $emails_ids;
			},
			10
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

		add_filter(
			'woocommerce_shiptastic_dhl_preferred_fields_output_hook',
			function ( $hook_name ) {
				if ( function_exists( 'wc_gzd_checkout_adjustments_disabled' ) && ! wc_gzd_checkout_adjustments_disabled() ) {
					$hook_name = 'woocommerce_review_order_after_payment';
				}

				return $hook_name;
			}
		);

		add_filter(
			'woocommerce_gzd_replace_email_title_for_textdomain',
			function ( $replace_email_title, $textdomain ) {
				if ( 'shiptastic-for-woocommerce' === $textdomain ) {
					$replace_email_title = true;
				}

				return $replace_email_title;
			},
			10,
			2
		);
	}
}
