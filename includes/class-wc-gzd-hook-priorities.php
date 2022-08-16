<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Hook_Priorities {

	/**
	 * Single instance of WC_GZD_Hook_Priorities
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $priorities             = array();
	public $default_priorities     = array();
	public $hooks                  = array();
	public $queue                  = array();
	protected $hook_order_queue    = array();
	protected $original_priorities = array();

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		$this->init();

		add_action( 'after_setup_theme', array( $this, 'load_priorities' ), 45 );
		add_action( 'after_setup_theme', array( $this, 'force_hook_order_queued' ), 50 );

		add_action( 'after_switch_theme', array( $this, 'clear_cache' ), 25 );
	}

	public function init() {
		// Default priorities used within WooCommerce (not customized by themes)
		$this->default_priorities = array(
			'woocommerce_single_product_summary' => array(
				'woocommerce_template_single_price' => 10,
			),
			'woocommerce_checkout_order_review'  => array(
				'woocommerce_order_review'     => 10,
				'woocommerce_checkout_payment' => 20,
			),
			'woocommerce_thankyou'               => array(
				'woocommerce_order_details_table' => 10,
			),
		);

		$this->priorities  = $this->default_priorities;
		$cached_priorities = get_transient( 'woocommerce_gzd_hook_priorities' );

		// Load custom theme priorities
		if ( false !== $cached_priorities ) {
			$this->priorities = array_merge( $this->priorities, (array) $cached_priorities );
		}

		$this->hooks = array(
			'single_small_business_info'           => 30,
			'cart_subtotal_unit_price'             => 0,
			'cart_product_differential_taxation'   => 9,
			'cart_small_business_info'             => 0,
			'checkout_small_business_info'         => 25,
			'checkout_edit_data_notice'            => 0,
			'checkout_payment'                     => 10,
			'checkout_order_review'                => 20,
			'checkout_order_submit'                => 21,
			'checkout_legal'                       => 2,
			'checkout_set_terms'                   => 3,
			'checkout_digital_checkbox'            => 4,
			'checkout_service_checkbox'            => 5,
			'checkout_direct_debit'                => 6,
			'order_product_units'                  => 1,
			'order_product_delivery_time'          => 2,
			'order_product_item_desc'              => 3,
			'order_product_unit_price'             => 0,
			'order_pay_now_button'                 => 0,
			'email_product_differential_taxation'  => 0,
			'email_product_deposit_packaging_type' => 1,
			'email_product_unit_price'             => 1,
			'email_product_deposit'                => 2,
			'email_product_units'                  => 3,
			'email_product_delivery_time'          => 4,
			'email_product_item_desc'              => 5,
			'email_product_defect_description'     => 6,
			'email_product_attributes'             => 7,
			'gzd_footer_vat_info'                  => 0,
			'footer_vat_info'                      => 5,
			'gzd_footer_sale_info'                 => 0,
			'footer_sale_info'                     => 5,
		);
	}

	/**
	 * Returns the priority for critical hooks (see $this->priorities) which may be customized by a theme.
	 *
	 * @param $hook
	 * @param $function
	 *
	 * @return int
	 */
	public function get_priority( $hook, $function, $fallback = 10, $force_original = false ) {
		$priority = $fallback;

		if ( isset( $this->priorities[ $hook ][ $function ] ) ) {
			$priority = $this->priorities[ $hook ][ $function ];
		}

		if ( $force_original && isset( $this->original_priorities[ $hook ][ $function ] ) ) {
			$priority = $this->original_priorities[ $hook ][ $function ];
		}

		return $priority;
	}

	public function update_priority( $hook, $function, $new_priority = 10, $old_priority = false, $fallback = 10 ) {
		$old_priority = is_numeric( $old_priority ) ? $old_priority : $this->get_priority( $hook, $function, $fallback );

		/**
		 * Store old priorities to allow restoring the old position.
		 */
		if ( ! isset( $this->original_priorities[ $hook ] ) ) {
			$this->original_priorities[ $hook ] = array();
		}

		$this->original_priorities[ $hook ][ $function ] = $old_priority;

		remove_action( $hook, $function, $old_priority );

		if ( ! has_action( $hook, $function ) ) {
			/**
			 * Update new priorities in the list.
			 */
			if ( ! isset( $this->priorities[ $hook ] ) ) {
				$this->priorities[ $hook ] = array();
			}

			$this->priorities[ $hook ][ $function ] = $new_priority;

			add_action( $hook, $function, $new_priority );
		}
	}

	/**
	 * Returns the priority for a custom wc germanized frontend hook
	 */
	public function get_hook_priority( $hook, $suppress_filters = false ) {
		if ( isset( $this->hooks[ $hook ] ) ) {
			/**
			 * Filters frontend hook priority.
			 *
			 * @param int $priority The hook priority.
			 * @param string $hook The hook name.
			 * @param WC_GZD_Hook_Priorities $hooks The hook priority instance.
			 *
			 * @since 1.0.0
			 */
			return ( ! $suppress_filters ? apply_filters( 'wc_gzd_frontend_hook_priority', $this->hooks[ $hook ], $hook, $this ) : $this->hooks[ $hook ] );
		}

		return false;
	}

	public function get_hook_priorities() {
		return $this->hooks;
	}

	/**
	 * This method forces a sequential order of a certain hook. Switches
	 * priorities after_setup_theme to improve theme compatibility.
	 *
	 * @param $hook
	 * @param $functions
	 *
	 * @return void
	 */
	public function force_hook_order( $hook, $functions ) {
		$args = array(
			'hook'      => $hook,
			'functions' => array(),
		);

		foreach ( $functions as $function ) {
			$function_arg = wp_parse_args(
				$function,
				array(
					'function'     => '',
					'new_priority' => 10,
				)
			);

			if ( ! empty( $function_arg['function'] ) ) {
				$args['functions'][] = $function_arg;
			}
		}

		if ( ! empty( $args['functions'] ) ) {
			$this->hook_order_queue[] = $args;
		}

		if ( did_action( 'after_setup_theme' ) ) {
			$this->force_hook_order_queued();
		}
	}

	public function force_hook_order_queued() {
		foreach ( $this->hook_order_queue as $queue ) {
			$hook            = $queue['hook'];
			$hooks_to_change = array();

			foreach ( $queue['functions'] as $function_data ) {
				$new_prio  = is_numeric( $function_data['new_priority'] ) ? $function_data['new_priority'] : $this->get_priority( $hook, $function_data['new_priority'] );
				$old_prio  = $this->get_priority( $hook, $function_data['function'] );
				$last_hook = isset( $hooks_to_change[ count( $hooks_to_change ) - 1 ] ) ? $hooks_to_change[ count( $hooks_to_change ) - 1 ] : false;

				if ( $last_hook && $new_prio <= $last_hook['new_priority'] ) {
					$new_prio = $last_hook['new_priority'] + 1;
				}

				$hooks_to_change[] = array(
					'hook'         => $hook,
					'function'     => $function_data['function'],
					'new_priority' => $new_prio,
					'old_priority' => $old_prio,
				);
			}

			foreach ( $hooks_to_change as $hook ) {
				$this->update_priority( $hook['hook'], $hook['function'], $hook['new_priority'], $hook['old_priority'] );
			}
		}
	}

	public function change_priority( $hook, $function, $new_prio ) {
		wc_deprecated_function( 'WC_GZD_Hook_Priorities::change_priority', '3.10.0' );
	}

	/**
	 * Hooked by after_setup_theme. Not to be called directly
	 */
	public function change_priority_queue() {
		wc_deprecated_function( 'WC_GZD_Hook_Priorities::change_priority', '3.10.0' );
	}

	public function clear_cache() {
		delete_transient( 'woocommerce_gzd_hook_priorities' );
	}

	/**
	 * Regenerates the hook priority cache (checks for theme customizations)
	 */
	public function load_priorities() {
		$priorities = get_transient( 'woocommerce_gzd_hook_priorities' );

		if ( false === $priorities ) {
			$this->priorities = $this->default_priorities;

			if ( ! empty( $this->priorities ) ) {
				foreach ( $this->priorities as $hook => $functions ) {
					foreach ( $functions as $function => $old_prio ) {
						$prio = has_action( $hook, $function );

						if ( false === $prio ) {
							$prio = has_filter( $hook, $function );
						}

						if ( is_numeric( $prio ) ) {
							$this->priorities[ $hook ][ $function ] = $prio;
						}
					}
				}
			}

			if ( ! empty( $this->priorities ) ) {
				set_transient( 'woocommerce_gzd_hook_priorities', $this->priorities, apply_filters( 'woocommerce_gzd_hook_priority_cache_duration', MINUTE_IN_SECONDS * 5 ) );
			} else {
				delete_transient( 'woocommerce_gzd_hook_priorities' );
			}
		}
	}
}

WC_GZD_Hook_Priorities::instance();
