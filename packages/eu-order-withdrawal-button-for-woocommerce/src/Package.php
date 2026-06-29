<?php

namespace Vendidero\OrderWithdrawalButton;

use Vendidero\OrderWithdrawalButton\Admin\Admin;
use Vendidero\OrderWithdrawalButton\Admin\Privacy;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {
	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '2.3.1';

	protected static $localized_scripts = array();

	/**
	 * Init the package
	 */
	public static function init() {
		if ( ! self::has_dependencies() ) {
			return;
		}

		self::init_hooks();
		self::includes();

		/**
		 * Defer loading compatibilities until the plugins_loaded hooks has "fully" traversed.
		 */
		if ( doing_action( 'plugins_loaded' ) ) {
			add_action( 'plugins_loaded', array( __CLASS__, 'load_compatibilities' ), 9999 );
		} else {
			self::load_compatibilities();
		}

		do_action( 'eu_owb_woocommerce_init' );
	}

	protected static function init_hooks() {
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_feature_compatibility' ) );

		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'check_version' ), 10 );
		add_action( 'init', array( __CLASS__, 'register_plugin_links' ) );
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'filter_templates' ), 50, 3 );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'register_order_statuses' ) );
		add_action( 'init', array( __CLASS__, 'register_post_statuses' ) );
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_emails' ), 20 );
		add_action( 'init', array( __CLASS__, 'email_hooks' ), 10 );
		add_filter( 'woocommerce_email_styles', array( __CLASS__, 'email_styles' ), 20 );
		add_filter( 'woocommerce_template_directory', array( __CLASS__, 'set_woocommerce_template_dir' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );

		add_action( 'init', array( __CLASS__, 'maybe_embed' ) );

		add_action( 'woocommerce_after_register_post_type', array( __CLASS__, 'register_order_type' ) );
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ) );
		add_filter( 'woocommerce_get_order_item_classname', array( __CLASS__, 'register_order_item_classname' ), 10, 3 );
		add_action( 'init', array( __CLASS__, 'register_order_status_cache' ) );
		add_filter( 'wp_untrash_post_status', array( __CLASS__, 'wp_untrash_post_status' ), 10, 3 );

		add_action( 'woocommerce_prepare_email_for_preview', array( __CLASS__, 'prepare_email_for_preview' ) );
		add_action( 'eu_owb_migrate_withdrawals', array( __CLASS__, 'migrate_withdrawals' ) );

		add_action(
			'eu_owb_woocommerce_return_request_form_start',
			function () {
				add_filter( 'woocommerce_form_field', array( __CLASS__, 'force_div_form_field_filter' ), 10, 1 );
			}
		);

		add_action(
			'eu_owb_woocommerce_return_request_form_end',
			function () {
				remove_filter( 'woocommerce_form_field', array( __CLASS__, 'force_div_form_field_filter' ), 10 );
			}
		);

		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'maybe_link_refund' ), 10, 2 );
		add_filter( 'woocommerce_pre_delete_order_refund', array( __CLASS__, 'maybe_remove_refund' ), 5, 3 );
	}

	/**
	 * @param null $result
	 * @param \WC_Order_Refund $refund
	 * @param boolean $force_delete
	 *
	 * @return null
	 */
	public static function maybe_remove_refund( $result, $refund, $force_delete ) {
		if ( ! $force_delete ) {
			return $result;
		}

		if ( $order = wc_get_order( $refund->get_parent_id() ) ) {
			$withdrawals = eu_owb_get_order_withdrawals( $order, array( 'status' => 'confirmed' ) );
			$refund_map  = array();

			if ( ! empty( $withdrawals ) ) {
				foreach ( $refund->get_items() as $item ) {
					$original_item_id = absint( $item->get_meta( '_refunded_item_id' ) );
					$refunded_qty     = $item->get_quantity() * - 1;

					$refund_map[ $original_item_id ] = $refunded_qty;
				}
			}

			if ( ! empty( $refund_map ) ) {
				foreach ( $withdrawals as $withdrawal ) {
					$has_removed_refund = false;

					foreach ( $withdrawal->get_items() as $withdrawal_item ) {
						if ( $withdrawal_item->get_refunded_quantity() <= 0 ) {
							continue;
						}

						$order_item_id = $withdrawal_item->get_parent_id();

						if ( array_key_exists( $order_item_id, $refund_map ) ) {
							$max_qty = min( $refund_map[ $order_item_id ], $withdrawal_item->get_refunded_quantity() );

							if ( $max_qty > 0 ) {
								$withdrawal_item->set_refunded_quantity( max( 0, ( $withdrawal_item->get_refunded_quantity() - $max_qty ) ) );
								$has_removed_refund = true;

								$refund_map[ $order_item_id ] -= $max_qty;

								if ( $refund_map[ $order_item_id ] <= 0 ) {
									unset( $refund_map[ $order_item_id ] );
								}
							}
						}
					}

					if ( $has_removed_refund ) {
						$withdrawal->set_refund_id( 0 );
						$withdrawal->save();
					}
				}
			}
		}

		return $result;
	}

	public static function maybe_link_refund( $order_id, $refund_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			$withdrawals = eu_owb_get_order_withdrawals( $order, array( 'status' => 'confirmed' ) );
			$refund_map  = array();

			if ( ! empty( $withdrawals ) ) {
				if ( $refund = wc_get_order( $refund_id ) ) {
					foreach ( $refund->get_items() as $item ) {
						$original_item_id = absint( $item->get_meta( '_refunded_item_id' ) );
						$refunded_qty     = $item->get_quantity() * - 1;

						$refund_map[ $original_item_id ] = $refunded_qty;
					}
				}
			}

			if ( ! empty( $refund_map ) ) {
				foreach ( $withdrawals as $withdrawal ) {
					$has_linked_refund = false;

					foreach ( $withdrawal->get_refundable_items() as $order_item_id => $withdrawal_item ) {
						if ( array_key_exists( $order_item_id, $refund_map ) ) {
							$max_qty = min( $refund_map[ $order_item_id ], $withdrawal_item['quantity'] );

							if ( $max_qty > 0 ) {
								$withdrawal_item['item']->set_refunded_quantity( $withdrawal_item['item']->get_refunded_quantity() + $max_qty );
								$has_linked_refund = true;

								$refund_map[ $order_item_id ] -= $max_qty;

								if ( $refund_map[ $order_item_id ] <= 0 ) {
									unset( $refund_map[ $order_item_id ] );
								}
							}
						}
					}

					if ( $has_linked_refund ) {
						$withdrawal->set_refund_id( $refund_id );
						$withdrawal->save();
					}
				}
			}
		}
	}

	public static function load_compatibilities() {
		$compatibilities = apply_filters(
			'eu_owb_woocommerce_compatibilities',
			array(
				'wpml' => '\Vendidero\OrderWithdrawalButton\Compatibility\WPML',
			)
		);

		foreach ( $compatibilities as $compatibility ) {
			if ( is_a( $compatibility, '\Vendidero\OrderWithdrawalButton\Compatibility\Compatibility', true ) ) {
				if ( $compatibility::is_active() ) {
					$compatibility::init();
				}
			}
		}
	}

	public static function force_div_form_field() {
		return apply_filters( 'eu_owb_woocommerce_force_div_form_field', true );
	}

	public static function force_div_form_field_filter( $field_html ) {
		if ( self::force_div_form_field() ) {
			$field_html = str_replace( array( '<p', '</p>' ), array( '<div', '</div>' ), $field_html );
		}

		return $field_html;
	}

	public static function get_additional_admin_notification_recipient() {
		$recipient = '';

		if ( $email = self::get_setting( 'contact_email_address' ) ) {
			$email = apply_filters( 'eu_owb_get_contact_support_email', sanitize_email( $email ) );

			if ( ! empty( $email ) ) {
				$recipient = $email;
			}
		}

		return apply_filters( 'eu_owb_woocommerce_additional_admin_notification_recipient', $recipient );
	}

	public static function substr( $str, $start, $length = null ) {
		if ( is_array( $str ) ) {
			return array_map( array( __CLASS__, 'substr' ), $str );
		} elseif ( is_scalar( $str ) ) {
			if ( function_exists( 'mb_substr' ) ) {
				$str = mb_substr( $str, $start, $length );
			} else {
				$str = substr( $str, $start, $length );
			}

			return $str;
		} else {
			return $str;
		}
	}

	public static function get_form_field_maxlength( $field_name ) {
		$max_length = -1;

		if ( 'order_number' === $field_name ) {
			$max_length = 20;
		} elseif ( 'first_name' === $field_name || 'last_name' === $field_name ) {
			$max_length = 40;
		} elseif ( 'additional_information' === $field_name ) {
			$max_length = 150;
		}

		return apply_filters( 'eu_owb_woocommerce_form_field_maxlength', $max_length, $field_name );
	}

	public static function get_form_field_required( $form_field ) {
		$required         = false;
		$mandatory_fields = (array) self::get_setting( 'mandatory_fields', array() );

		if ( 'email' === $form_field ) {
			$required = true;
		} elseif ( in_array( $form_field, $mandatory_fields, true ) ) {
			$required = true;
		}

		if ( 'additional_information' === $form_field && $required && ! eu_owb_enable_additional_information_field() ) {
			$required = false;
		}

		return apply_filters( 'eu_owb_woocommerce_form_field_required', $required, $form_field );
	}

	public static function migrate_withdrawals( $date_created_after ) {
		$orders    = Install::legacy_withdrawal_query( $date_created_after );
		$last_date = 0;

		if ( ! empty( $orders ) ) {
			/**
			 * Make sure to check whether we actually found legacy withdrawal data
			 */
			$has_found_withdrawals = false;

			foreach ( $orders as $order ) {
				$request     = $order->get_meta( '_withdrawal_request', true );
				$withdrawals = $order->get_meta( '_withdrawals', true );

				if ( ! empty( $request ) ) {
					$has_found_withdrawals = true;
					$withdrawal            = self::get_withdrawal_from_legacy_order_meta( $order, $request, true );
					$existing              = array();
					$result                = false;

					if ( $withdrawal->get_withdrawal_number() ) {
						$existing = eu_owb_get_order_withdrawals( $order, array( 'withdrawal_number' => $withdrawal->get_withdrawal_number() ) );
					}

					if ( empty( $existing ) ) {
						$result = $withdrawal->save();
					}

					if ( $result || ! empty( $existing ) ) {
						$order->delete_meta_data( '_withdrawal_request' );
						$order->update_meta_data( '_imported_withdrawal_request', $request );
						$order->save();
					}
				}

				if ( ! empty( $withdrawals ) ) {
					$has_found_withdrawals = true;
					$withdrawals           = (array) $withdrawals;

					foreach ( $withdrawals as $order_withdrawal ) {
						$withdrawal = self::get_withdrawal_from_legacy_order_meta( $order, $order_withdrawal );

						if ( $withdrawal->get_withdrawal_number() ) {
							$existing = eu_owb_get_order_withdrawals( $order, array( 'withdrawal_number' => $withdrawal->get_withdrawal_number() ) );

							if ( ! empty( $existing ) ) {
								continue;
							}
						}

						$withdrawal->save();
					}

					$order->delete_meta_data( '_withdrawals' );
					$order->update_meta_data( '_imported_withdrawals', $withdrawals );
					$order->save();
				}

				if ( $order->get_date_created() ) {
					$last_date = $order->get_date_created()->getTimestamp();
				}
			}

			if ( count( $orders ) >= 10 && $last_date > 0 && $has_found_withdrawals ) {
				if ( $queue = WC()->queue() ) {
					$queue->schedule_single(
						time() + 120,
						'eu_owb_migrate_withdrawals',
						array( 'date_created_after' => $last_date ),
						'eu_order_withdrawal_button'
					);
				}
			}
		} elseif ( $queue = WC()->queue() ) {
			$queue->cancel_all( 'eu_owb_migrate_withdrawals' );
		}
	}

	/**
	 * @param \WC_Order $order
	 * @param array $order_withdrawal
	 * @param bool $is_request
	 *
	 * @return WithdrawalOrder
	 */
	protected static function get_withdrawal_from_legacy_order_meta( $order, $order_withdrawal, $is_request = false ) {
		$legacy_default_args = array(
			'id'                 => md5( uniqid( '', true ) ),
			'date_received'      => time(),
			'date_confirmed'     => null,
			'date_rejected'      => null,
			'request_email'      => '',
			'original_status'    => '',
			'status'             => '',
			'items'              => array(),
			'meta'               => array(),
			'rejection_reason'   => '',
			'is_partial'         => 'no',
			'has_verified_email' => 'yes',
			'is_update'          => 'no',
			'is_guest'           => 'yes',
			'has_refund'         => 'no',
			'refund_id'          => 0,
		);

		$order_withdrawal = wp_parse_args( (array) $order_withdrawal, $legacy_default_args );

		$withdrawal = new WithdrawalOrder();

		$withdrawal->set_date_received( $order_withdrawal['date_received'] );
		$withdrawal->set_email( empty( $order_withdrawal['request_email'] ) ? $order->get_billing_email() : $order_withdrawal['request_email'] );
		$withdrawal->set_original_status( $order_withdrawal['original_status'] );
		$withdrawal->set_is_partial( $order_withdrawal['is_partial'] );
		$withdrawal->set_is_update( $order_withdrawal['is_update'] );
		$withdrawal->set_is_guest( $order_withdrawal['is_guest'] );
		$withdrawal->set_has_verified_email( $order_withdrawal['has_verified_email'] );
		$withdrawal->set_refund_id( $order_withdrawal['refund_id'] );
		$withdrawal->set_rejection_reason( $order_withdrawal['rejection_reason'] );
		$withdrawal->set_withdrawal_number( $order_withdrawal['id'] );

		try {
			$withdrawal->set_parent_id( $order->get_id() );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		$withdrawal->set_order_number( $order->get_order_number() );

		if ( $is_request ) {
			$withdrawal->set_status( 'requested' );
		} else {
			$withdrawal->set_status( $order_withdrawal['status'] );

			if ( $withdrawal->has_status( 'confirmed' ) && ! empty( $order_withdrawal['date_confirmed'] ) ) {
				$withdrawal->set_date_confirmed( $order_withdrawal['date_confirmed'] );
			} elseif ( $withdrawal->has_status( 'rejected' ) && ! empty( $order_withdrawal['date_rejected'] ) ) {
				$withdrawal->set_date_rejected( $order_withdrawal['date_rejected'] );
			}
		}

		foreach ( $order_withdrawal['meta'] as $meta_key => $value ) {
			if ( 'has_multiple_matching_orders' === $meta_key ) {
				$withdrawal->update_meta_data( '_has_multiple_matching_orders', $value );
			} elseif ( 'first_name' === $meta_key ) {
				$withdrawal->set_first_name( $value );
			} elseif ( 'last_name' === $meta_key ) {
				$withdrawal->set_last_name( $value );
			}
		}

		foreach ( $order_withdrawal['items'] as $item_id => $item_data ) {
			$item_data = wp_parse_args(
				(array) $item_data,
				array(
					'quantity' => 1,
				)
			);

			if ( $item = $order->get_item( $item_id, false ) ) {
				if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
					continue;
				}

				$withdrawal_item = new WithdrawalItem();
				$withdrawal_item->from_order_item( $item );
				$withdrawal_item->set_quantity( $item_data['quantity'] );

				$withdrawal->add_item( $withdrawal_item );
			}
		}

		return $withdrawal;
	}

	public static function prepare_email_for_preview( $email_instance ) {
		$returns = array(
			'EU_OWB_Email_New_Withdrawal_Request',
			'EU_OWB_Email_Deleted_Withdrawal_Request',
			'EU_OWB_Email_Customer_Withdrawal_Request_Rejected',
			'EU_OWB_Email_Customer_Withdrawal_Request_Received',
			'EU_OWB_Email_Customer_Withdrawal_Request_Confirmed',
		);

		if ( in_array( get_class( $email_instance ), $returns, true ) ) {
			$withdrawal = new WithdrawalOrder();
			$withdrawal->set_order_number( '1234' );
			$withdrawal->set_date_received( time() );
			$withdrawal->set_is_partial( false );
			$withdrawal->set_is_guest( true );
			$withdrawal->set_withdrawal_number( '12345678' );
			$withdrawal->set_first_name( _x( 'John', 'owb-email-preview', 'woocommerce-germanized' ) );
			$withdrawal->set_last_name( _x( 'Doe', 'owb-email-preview', 'woocommerce-germanized' ) );
			$withdrawal->set_email( 'test@test.com' );
			$withdrawal->set_has_verified_email( false );
			$withdrawal->set_rejection_reason( _x( 'An example rejection reason.', 'owb-email-preview', 'woocommerce-germanized' ) );

			$email_instance->withdrawal = $withdrawal;
		}

		return $email_instance;
	}

	/**
	 * Ensure statuses are correctly reassigned when restoring orders and products.
	 *
	 * @param string $new_status      The new status of the post being restored.
	 * @param int    $post_id         The ID of the post being restored.
	 * @param string $previous_status The status of the post at the point where it was trashed.
	 * @return string
	 */
	public static function wp_untrash_post_status( $new_status, $post_id, $previous_status ) {
		$post_types = array( 'shop_order_withdraw' );

		if ( in_array( get_post_type( $post_id ), $post_types, true ) ) {
			$new_status = $previous_status;
		}

		return $new_status;
	}

	public static function get_withdrawal_statuses() {
		return array(
			'wc-owb-requested' => _x( 'Requested', 'owb', 'woocommerce-germanized' ),
			'wc-owb-confirmed' => _x( 'Confirmed', 'owb', 'woocommerce-germanized' ),
			'wc-owb-rejected'  => _x( 'Rejected', 'owb', 'woocommerce-germanized' ),
		);
	}

	public static function get_withdrawal_status_name( $status ) {
		$status = self::maybe_remove_withdrawal_order_status_prefix( $status );

		if ( 'trash' === $status ) {
			return get_post_status_object( 'trash' )->label;
		}

		$status   = 'wc-owb-' . $status;
		$statuses = self::get_withdrawal_statuses();

		return array_key_exists( $status, $statuses ) ? $statuses[ $status ] : '';
	}

	public static function register_order_status_cache() {
		try {
			$cache = wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCountCacheService::class );

			add_action( 'eu_owb_woocommerce_new_withdrawal_order', array( $cache, 'update_on_new_order' ), 10, 2 );
			add_action( 'eu_owb_woocommerce_withdrawal_order_status_changed', array( $cache, 'update_on_order_status_changed' ), 10, 4 );
			add_action( 'eu_owb_woocommerce_before_delete_withdrawal', array( $cache, 'update_on_order_deleted' ), 10, 2 );
			add_action( 'eu_owb_woocommerce_before_trash_withdrawal', array( $cache, 'update_on_order_trashed' ), 10, 2 );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	public static function register_data_stores( $stores ) {
		$stores['order-withdrawal']      = 'Vendidero\OrderWithdrawalButton\DataStores\WithdrawalOrderCPT';
		$stores['order-item-withdrawal'] = 'Vendidero\OrderWithdrawalButton\DataStores\WithdrawalItem';

		/**
		 * Backwards compatibility for older Woo wc_orders_count implementation
		 * requesting count via static order type data store names.
		 */
		$stores['shop_order_withdraw'] = 'Vendidero\OrderWithdrawalButton\DataStores\WithdrawalOrderCPT';

		if ( self::is_hpos_enabled() ) {
			try {
				$meta    = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStoreMeta::class );
				$db_util = wc_get_container()->get( \Automattic\WooCommerce\Internal\Utilities\DatabaseUtil::class );
				$proxy   = wc_get_container()->get( \Automattic\WooCommerce\Proxies\LegacyProxy::class );

				$data_store = new \Vendidero\OrderWithdrawalButton\DataStores\WithdrawalOrder();
				$data_store->init( $meta, $db_util, $proxy );

				$stores['order-withdrawal']    = $data_store;
				$stores['shop_order_withdraw'] = $data_store;
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		return $stores;
	}

	public static function register_order_item_classname( $classname, $item_type, $id ) {
		if ( in_array( $item_type, array( 'withdrawal_item', 'withdrawal' ), true ) ) {
			$classname = '\Vendidero\OrderWithdrawalButton\WithdrawalItem';
		}

		return $classname;
	}

	public static function register_order_type() {
		wc_register_order_type(
			'shop_order_withdraw', // max 20 chars
			array(
				'label'                            => _x( 'Withdrawals', 'owb', 'woocommerce-germanized' ),
				'capability_type'                  => 'shop_order',
				'public'                           => false,
				'show_ui'                          => false,
				'hierarchical'                     => false,
				'supports'                         => false,
				'add_order_meta_boxes'             => false,
				'exclude_from_order_count'         => false,
				'exclude_from_order_views'         => true,
				'exclude_from_order_reports'       => true,
				'exclude_from_order_sales_reports' => true,
				'class_name'                       => '\Vendidero\OrderWithdrawalButton\WithdrawalOrder',
				'rewrite'                          => false,
			)
		);
	}

	public static function maybe_remove_withdrawal_order_status_prefix( $status ) {
		if ( 'wc-owb-' === substr( $status, 0, 7 ) ) {
			$status = substr( $status, 7 );
		} elseif ( 'owb-' === substr( $status, 0, 4 ) ) {
			$status = substr( $status, 4 );
		}

		return $status;
	}

	public static function maybe_prefix_withdrawal_order_status( $status ) {
		$status          = self::maybe_remove_withdrawal_order_status_prefix( $status );
		$status_prefixed = 'wc-owb-' . $status;

		if ( array_key_exists( $status_prefixed, self::get_withdrawal_statuses() ) ) {
			return $status_prefixed;
		}

		return $status;
	}

	public static function get_withdrawal_order_props( $cpt = false ) {
		$props = array(
			'_withdrawal_number'       => 'withdrawal_number',
			'_date_confirmed'          => 'date_confirmed',
			'_date_rejected'           => 'date_rejected',
			'_original_status'         => 'original_status',
			'_rejection_reason'        => 'rejection_reason',
			'_is_partial'              => 'is_partial',
			'_has_verified_email'      => 'has_verified_email',
			'_is_update'               => 'is_update',
			'_is_guest'                => 'is_guest',
			'_refund_id'               => 'refund_id',
			'_first_name'              => 'first_name',
			'_last_name'               => 'last_name',
			'_email'                   => 'email',
			'_order_number'            => 'order_number',
			'_verification_code'       => 'verification_code',
			'_contract_identification' => 'contract_identification',
		);

		if ( $cpt ) {
			$props = array_merge(
				$props,
				array(
					'_order_key'           => 'order_key',
					'_customer_id'         => 'customer_id',
					'_customer_ip_address' => 'customer_ip_address',
					'_customer_user_agent' => 'customer_user_agent',
					'_billing_email'       => 'billing_email',
				)
			);
		}

		return $props;
	}

	public static function localize_printed_scripts() {
		$handles = array(
			'eu-owb-woocommerce',
		);

		$data = array(
			'eu-owb-woocommerce' => array(
				'name' => 'eu_owb_woocommerce_order_withdrawal_params',
				'data' => array(
					'wc_ajax_url' => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				),
			),
		);

		foreach ( $handles as $handle ) {
			if ( ! array_key_exists( $handle, $data ) ) {
				continue;
			}

			$params = $data[ $handle ];

			if ( ! in_array( $handle, self::$localized_scripts, true ) && wp_script_is( $handle ) ) {
				self::$localized_scripts[] = $handle;

				wp_localize_script( $handle, $params['name'], $params['data'] );
			}
		}
	}

	public static function maybe_embed() {
		if ( 'yes' === self::get_setting( 'enable_embed', 'yes' ) && eu_owb_has_public_withdrawal_page() ) {
			$footer_hook = self::get_theme_footer_hook();

			add_action( $footer_hook['hook'], array( __CLASS__, 'print_button' ), (int) $footer_hook['priority'] );
		}
	}

	public static function is_shop_request() {
		return apply_filters( 'eu_owb_woocommerce_is_shop_request', ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) );
	}

	public static function print_button() {
		if ( self::is_shop_request() || 'yes' === self::get_setting( 'embed_everywhere', 'no' ) ) {
			echo wp_kses_post( self::order_withdrawal_button() );
		}
	}

	public static function get_withdrawals_url() {
		return admin_url( 'admin.php?page=wc-owb-withdrawals' );
	}

	public static function register_plugin_links() {
		if ( self::is_standalone() ) {
			add_filter( 'plugin_action_links_' . plugin_basename( trailingslashit( self::get_path() ) . 'eu-order-withdrawal-button-for-woocommerce.php' ), array( __CLASS__, 'plugin_action_links' ) );
		}
	}

	public static function plugin_action_links( $links ) {
		return array_merge(
			array(
				'<a href="' . esc_url( self::is_integration() ? admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=withdrawal_button' ) : admin_url( 'admin.php?page=wc-settings&tab=advanced&section=owb' ) ) . '">' . _x( 'Settings', 'owb', 'woocommerce-germanized' ) . '</a>',
				'<a href="' . esc_url( self::get_withdrawals_url() ) . '">' . _x( 'Withdrawals', 'owb', 'woocommerce-germanized' ) . '</a>',
			),
			$links
		);
	}

	protected static function get_theme_footer_hook() {
		$custom_hooks = array(
			'astra'      => array(
				'hook'     => 'astra_footer',
				'priority' => 50,
			),
			'storefront' => array(
				'hook'     => 'storefront_footer',
				'priority' => 20,
			),
		);

		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : '';
		$hook  = array(
			'hook'     => 'wp_footer',
			'priority' => 5,
		);

		if ( array_key_exists( $theme->get_template(), $custom_hooks ) ) {
			$hook = wp_parse_args(
				$custom_hooks[ $theme->get_template() ],
				array(
					'hook'     => '',
					'priority' => 10,
				)
			);
		}

		return $hook;
	}

	public static function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( self::get_path() . '/templates/' . $template ) ) {
			return untrailingslashit( self::get_template_path() );
		}

		return $dir;
	}

	public static function has_email_improvements_enabled() {
		$is_enabled = false;

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			$is_enabled = \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'email_improvements' );
		}

		return $is_enabled;
	}

	public static function register_emails( $emails ) {
		$emails['EU_OWB_Email_Customer_Withdrawal_Request_Received']  = include self::get_path() . '/includes/emails/class-eu-owb-email-customer-withdrawal-request-received.php';
		$emails['EU_OWB_Email_Customer_Withdrawal_Request_Confirmed'] = include self::get_path() . '/includes/emails/class-eu-owb-email-customer-withdrawal-request-confirmed.php';
		$emails['EU_OWB_Email_Customer_Withdrawal_Request_Rejected']  = include self::get_path() . '/includes/emails/class-eu-owb-email-customer-withdrawal-request-rejected.php';
		$emails['EU_OWB_Email_New_Withdrawal_Request']                = include self::get_path() . '/includes/emails/class-eu-owb-email-new-withdrawal-request.php';
		$emails['EU_OWB_Email_Deleted_Withdrawal_Request']            = include self::get_path() . '/includes/emails/class-eu-owb-email-deleted-withdrawal-request.php';

		return $emails;
	}

	public static function email_hooks() {
		add_action( 'eu_owb_woocommerce_withdrawal_request_details', array( __CLASS__, 'withdrawal_email_edit_link' ), 10, 5 );
		add_action( 'eu_owb_woocommerce_withdrawal_request_details', array( __CLASS__, 'withdrawal_email_details' ), 20, 5 );
	}

	public static function email_styles( $styles ) {
		return $styles . '
			#body_content .email-withdrawal-details tbody tr:last-child td {
                border-bottom: 0;
                padding-bottom: 0;
            }
		';
	}

	/**
	 * @param \WC_Order|WithdrawalOrder $order
	 * @param $sent_to_admin
	 * @param $plain_text
	 * @param $email
	 * @param WithdrawalOrder $withdrawal
	 *
	 * @return void
	 */
	public static function withdrawal_email_edit_link( $order, $sent_to_admin, $plain_text, $email, $withdrawal ) {
		if ( is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Received' ) && $withdrawal->has_parent() ) {
			$order                = $withdrawal->get_parent();
			$edit_withdrawal_link = eu_owb_get_edit_withdrawal_url( $order );

			$has_multiple_orders        = wc_string_to_bool( $withdrawal->get_meta( '_has_multiple_matching_orders' ) );
			$embed_edit_withdrawal_link = apply_filters( 'eu_owb_woocommerce_email_embed_partial_withdrawal_link', ( ( ( ! $withdrawal->is_partial() && eu_owb_order_supports_partial_withdrawal( $order ) ) || $has_multiple_orders ) && ! empty( $edit_withdrawal_link ) && $withdrawal->is_guest() ), $withdrawal, $order );

			if ( $embed_edit_withdrawal_link && $withdrawal->has_verified_email() ) {
				if ( $plain_text ) {
					wc_get_template(
						'emails/plain/email-withdrawal-edit-link.php',
						array(
							'order'                => $order,
							'sent_to_admin'        => $sent_to_admin,
							'plain_text'           => $plain_text,
							'withdrawal'           => $withdrawal,
							'email'                => $email,
							'edit_withdrawal_link' => $edit_withdrawal_link,
						)
					);
				} else {
					wc_get_template(
						'emails/email-withdrawal-edit-link.php',
						array(
							'order'                => $order,
							'sent_to_admin'        => $sent_to_admin,
							'plain_text'           => $plain_text,
							'withdrawal'           => $withdrawal,
							'email'                => $email,
							'edit_withdrawal_link' => $edit_withdrawal_link,
						)
					);
				}
			}
		}
	}

	/**
	 * @param \WC_Order|WithdrawalOrder $order
	 * @param $sent_to_admin
	 * @param $plain_text
	 * @param $email
	 * @param WithdrawalOrder $withdrawal
	 *
	 * @return void
	 */
	public static function withdrawal_email_details( $order, $sent_to_admin, $plain_text, $email, $withdrawal ) {
		if ( $plain_text ) {
			wc_get_template(
				'emails/plain/email-withdrawal-details.php',
				array(
					'order'                 => $order,
					'sent_to_admin'         => $sent_to_admin,
					'plain_text'            => $plain_text,
					'email'                 => $email,
					'withdrawal'            => $withdrawal,
					'show_deleted_original' => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Received' ) ? true : false,
					'hide_items'            => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Confirmed' ) ? false : ( ! $withdrawal->has_verified_email() && ! $sent_to_admin ),
				)
			);
		} else {
			wc_get_template(
				'emails/email-withdrawal-details.php',
				array(
					'order'                 => $order,
					'sent_to_admin'         => $sent_to_admin,
					'plain_text'            => $plain_text,
					'email'                 => $email,
					'withdrawal'            => $withdrawal,
					'show_deleted_original' => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Received' ) ? true : false,
					'hide_items'            => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Confirmed' ) ? false : ( ! $withdrawal->has_verified_email() && ! $sent_to_admin ),
				)
			);
		}
	}

	public static function page_has_withdrawal_form( $the_post = null ) {
		return self::page_has_shortcode( 'eu_owb_order_withdrawal_request_form', $the_post ) || apply_filters( 'eu_owb_woocommerce_page_has_form', false, $the_post );
	}

	public static function page_has_shortcode( $tag, $the_post = null ) {
		if ( ! is_null( $the_post ) ) {
			$the_post = get_post( $the_post );
		} else {
			global $post;

			$the_post = $post;
		}

		return is_a( $the_post, 'WP_Post' ) && has_shortcode( $the_post->post_content, $tag );
	}

	public static function register_scripts() {
		self::register_script( 'eu-owb-woocommerce', 'static/order-withdrawal.js', array( 'jquery', 'woocommerce' ) );
		wp_register_style( 'eu-owb-woocommerce-form', self::get_assets_url( 'static/form-styles.css' ), array(), self::get_version() );

		if ( self::page_has_withdrawal_form() ) {
			wp_enqueue_style( 'eu-owb-woocommerce-form' );
			wp_enqueue_script( 'eu-owb-woocommerce' );
		}
	}

	public static function register_post_statuses() {
		register_post_status(
			'wc-pending-wdraw',
			array(
				'label'                     => _x( 'Pending withdrawal', 'owb', 'woocommerce-germanized' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _nx_noop( 'Pending withdrawal (%s)', 'Pending withdrawals (%s)', 'owb', 'woocommerce-germanized' ),
			)
		);

		register_post_status(
			'wc-withdrawn',
			array(
				'label'                     => _x( 'Withdrawn', 'owb', 'woocommerce-germanized' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _nx_noop( 'Withdrawn (%s)', 'Withdrawn (%s)', 'owb', 'woocommerce-germanized' ),
			)
		);

		register_post_status(
			'wc-owb-requested',
			array(
				'label'                     => _x( 'Requested', 'owb', 'woocommerce-germanized' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'label_count'               => _nx_noop( 'Requested (%s)', 'Requested (%s)', 'owb', 'woocommerce-germanized' ),
			)
		);

		register_post_status(
			'wc-owb-confirmed',
			array(
				'label'                     => _x( 'Confirmed', 'owb', 'woocommerce-germanized' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'label_count'               => _nx_noop( 'Confirmed (%s)', 'Confirmed (%s)', 'owb', 'woocommerce-germanized' ),
			)
		);

		register_post_status(
			'wc-owb-rejected',
			array(
				'label'                     => _x( 'Rejected', 'owb', 'woocommerce-germanized' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'label_count'               => _nx_noop( 'Rejected (%s)', 'Rejected (%s)', 'owb', 'woocommerce-germanized' ),
			)
		);
	}

	public static function register_order_statuses( $statuses ) {
		/**
		 * Need to shorten wc-pending-withdrawal as it is too long for the custom order table limitation.
		 */
		$statuses['wc-pending-wdraw'] = _x( 'Pending withdrawal', 'owb', 'woocommerce-germanized' );
		$statuses['wc-withdrawn']     = _x( 'Withdrawn', 'owb', 'woocommerce-germanized' );

		return $statuses;
	}

	public static function register_shortcodes() {
		add_shortcode( 'eu_owb_order_withdrawal_request_form', array( __CLASS__, 'order_withdrawal_request_form' ) );
		add_shortcode( 'eu_owb_order_withdrawal_button', array( __CLASS__, 'order_withdrawal_button' ) );

		/**
		 * Mark the return page as a Woo page to make sure default form styles work.
		 */
		add_filter(
			'is_woocommerce',
			function ( $is_woocommerce ) {
				if ( self::page_has_withdrawal_form() ) {
					$is_woocommerce = true;
				}

				return $is_woocommerce;
			}
		);
	}

	public static function order_withdrawal_button( $args = array(), $content = '' ) {
		$args = wp_parse_args(
			$args,
			array(
				'include_wrapper' => true,
				'button_classes'  => implode(
					' ',
					array_filter(
						array(
							'button',
							eu_owb_get_element_class_name( 'button' ),
						)
					)
				),
				'button_text'     => eu_owb_get_withdrawal_button_text(),
			)
		);

		if ( ! empty( $content ) ) {
			$args['button_text'] = $content;
		}

		return wc_get_template_html( 'global/order-withdrawal-button.php', $args );
	}

	public static function order_withdrawal_request_form( $args = array() ) {
		$order_key             = isset( $_GET['order_key'] ) ? wc_clean( wp_unslash( $_GET['order_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id              = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$manually_select_items = isset( $_GET['manually_select_items'] ) ? wc_string_to_bool( wc_clean( wp_unslash( $_GET['manually_select_items'] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order                 = null;

		if ( ! empty( $order_id ) && ( $the_order = wc_get_order( $order_id ) ) ) {
			if ( $order_id === $the_order->get_id() && ! empty( $the_order->get_order_key() ) && hash_equals( $the_order->get_order_key(), $order_key ) ) {
				$order = $the_order;
			} elseif ( is_user_logged_in() && current_user_can( 'view_order', $the_order->get_id() ) ) {
				$order = $the_order;
			}
		}

		$defaults = array(
			'order'                 => $order,
			'order_key'             => $order_key,
			'manually_select_items' => $manually_select_items,
		);

		$args    = wp_parse_args( $args, $defaults );
		$notices = function_exists( 'wc_print_notices' ) ? wc_print_notices( true ) : '';
		$html    = '';

		// Output notices in case notices have not been outputted yet.
		if ( ! empty( $notices ) ) {
			$html .= '<div class="woocommerce">' . $notices . '</div>';
		}

		$html .= wc_get_template_html( 'forms/order-withdrawal-request.php', $args );

		return $html;
	}

	public static function is_integration() {
		return apply_filters( 'eu_owb_woocommerce_is_integration', false );
	}

	public static function is_hpos_enabled() {
		if ( ! is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) ) {
			return false;
		}

		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	public static function deactivate() {
		Install::deactivate();
	}

	public static function install() {
		self::init();

		if ( ! self::has_dependencies() ) {
			return;
		}

		Install::install();
	}

	public static function install_integration() {
		self::install();
	}

	public static function is_standalone() {
		return defined( 'EU_OWB_WC_IS_STANDALONE_PLUGIN' ) && EU_OWB_WC_IS_STANDALONE_PLUGIN;
	}

	public static function check_version() {
		if ( self::is_standalone() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'eu_owb_woocommerce_version' ) !== self::get_version() ) ) {
			Install::install();

			do_action( 'eu_owb_woocommerce_updated' );
		}
	}

	public static function log( $message, $type = 'info', $source = '' ) {
		/**
		 * Filter that allows adjusting whether to enable or disable
		 * logging for the shipments package
		 *
		 * @param boolean $enable_logging True if logging should be enabled. False otherwise.
		 */
		if ( ! apply_filters( 'eu_owb_woocommerce_enable_logging', false ) ) {
			return;
		}

		$logger = wc_get_logger();

		if ( ! $logger ) {
			return;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'eu-owb-woocommerce' . ( ! empty( $source ) ? '-' . $source : '' ) ) );
	}

	public static function has_dependencies() {
		return class_exists( 'WooCommerce' );
	}

	private static function includes() {
		Ajax::init();
		Admin::init();
		Privacy::init();

		include_once self::get_path() . '/includes/eu-owb-core-functions.php';
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_template_path() {
		return apply_filters( 'eu_owb_woocommerce_template_path', 'woocommerce/' );
	}

	/**
	 * Filter WooCommerce Templates to look into /templates before looking within theme folder
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param string $template_path
	 *
	 * @return string
	 */
	public static function filter_templates( $template, $template_name, $template_path ) {
		$default_template_path = apply_filters( 'eu_owb_woocommerce_default_template_path', self::get_path() . '/templates/' . $template_name, $template_name );

		if ( file_exists( $default_template_path ) ) {
			$template_path = self::get_template_path();

			// Check for Theme overrides
			$theme_template = locate_template(
				apply_filters(
					'eu_owb_woocommerce_locate_theme_template_locations',
					array(
						trailingslashit( $template_path ) . $template_name,
					),
					$template_name
				)
			);

			if ( 'forms/order-withdrawal-request.php' === $template_name ) {
				wp_enqueue_style( 'eu-owb-woocommerce-form' );
				wp_enqueue_script( 'eu-owb-woocommerce' );
			}

			if ( ! $theme_template ) {
				$template = $default_template_path;
			} else {
				$template = $theme_template;
			}
		}

		return $template;
	}

	public static function declare_feature_compatibility() {
		if ( ! self::is_standalone() ) {
			return;
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', trailingslashit( self::get_path() ) . 'eu-order-withdrawal-button-for-woocommerce.php', true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', trailingslashit( self::get_path() ) . 'eu-order-withdrawal-button-for-woocommerce.php', true );
		}
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path( $rel_path = '' ) {
		return trailingslashit( dirname( __DIR__ ) ) . $rel_path;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url( $rel_path = '' ) {
		return trailingslashit( plugins_url( '', __DIR__ ) ) . $rel_path;
	}

	public static function register_script( $handle, $path, $dep = array(), $ver = '', $in_footer = array( 'strategy' => 'defer' ) ) {
		global $wp_version;

		if ( version_compare( $wp_version, '6.3', '<' ) ) {
			$in_footer = true;
		}

		$ver = empty( $ver ) ? self::get_version() : $ver;

		wp_register_script(
			$handle,
			self::get_assets_url( $path ),
			$dep,
			$ver,
			$in_footer
		);
	}

	public static function get_assets_url( $script_or_style ) {
		$assets_url = self::get_url( 'build' );
		$is_debug   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$is_style   = '.css' === substr( $script_or_style, -4 );
		$is_static  = strstr( $script_or_style, 'static/' );

		if ( $is_debug && $is_static && ! $is_style ) {
			$assets_url = self::get_url( 'assets/js' );
		}

		return trailingslashit( $assets_url ) . $script_or_style;
	}

	public static function enable_partial_withdrawals() {
		return wc_string_to_bool( self::get_setting( 'enable_partial_withdrawals', 'yes' ) );
	}

	public static function get_setting( $name, $default_value = false ) {
		$option_name = "eu_owb_woocommerce_{$name}";

		return get_option( $option_name, $default_value );
	}
}
