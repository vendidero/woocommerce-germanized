<?php

namespace Vendidero\Shiptastic\Admin\Setup;

use Vendidero\Shiptastic\Admin\Admin;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\ShippingProvider\Simple;

defined( 'ABSPATH' ) || exit;

class Wizard {

	/**
	 * Current step
	 *
	 * @var string
	 */
	private static $current_step = '';

	/**
	 * Steps for the setup wizard
	 *
	 * @var array
	 */
	private static $steps = null;

	public static function init() {
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menus' ), 20 );
			add_action( 'admin_init', array( __CLASS__, 'render' ), 20 );
			add_action( 'admin_init', array( __CLASS__, 'redirect' ), 5 );

			// Load after base has registered scripts
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 15 );

			add_action( 'wp_ajax_woocommerce_stc_next_wizard_step', array( 'Vendidero\Shiptastic\Ajax', 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_woocommerce_stc_next_wizard_step', array( __CLASS__, 'save' ) );
		}
	}

	public static function redirect() {
		if ( get_transient( '_wc_shiptastic_setup_wizard_redirect' ) && apply_filters( 'woocommerce_shiptastic_enable_setup_wizard', true ) ) {
			$do_redirect  = true;
			$current_page = isset( $_GET['page'] ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification

			// On these pages, or during these events, postpone the redirect.
			if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
				$do_redirect = false;
			}

			// On these pages, or during these events, disable the redirect.
			if (
				( 'wc-shiptastic-setup' === $current_page ) ||
				apply_filters( 'woocommerce_shiptastic_prevent_automatic_wizard_redirect', Package::is_integration() ) ||
				isset( $_GET['activate-multi'] ) // phpcs:ignore WordPress.Security.NonceVerification
			) {
				delete_transient( '_wc_shiptastic_setup_wizard_redirect' );
				$do_redirect = false;
			}

			if ( $do_redirect ) {
				delete_transient( '_wc_shiptastic_setup_wizard_redirect' );
				wp_safe_redirect( admin_url( 'admin.php?page=wc-shiptastic-setup' ) );
				exit;
			}
		}
	}

	public static function get_steps() {
		if ( is_null( self::$steps ) ) {
			self::setup();
		}

		return self::$steps;
	}

	protected static function get_all_steps() {
		$all_steps = array(
			'welcome'           => array(
				'name'     => _x( 'Welcome', 'shipments', 'woocommerce-germanized' ),
				'order'    => 10,
				'settings' => function () {
					$fields                   = wc_stc_get_shipment_setting_address_fields();
					$default_fields           = wc_stc_get_shipment_setting_default_address_fields();
					$settings                 = array();
					$address_fields_to_render = array(
						'company',
						'first_name',
						'last_name',
						'address_1',
						'address_2',
						'city',
						'postcode',
						'country',
					);

					foreach ( $address_fields_to_render as $field ) {
						$settings[] = array(
							'title'     => $default_fields[ $field ],
							'type'      => 'country' === $field ? 'shipments_country_select' : 'text',
							'id'        => "woocommerce_shiptastic_shipper_address_{$field}",
							'value'     => 'country' === $field ? $fields[ $field ] . ':' . $fields['state'] : $fields[ $field ],
							'default'   => 'country' === $field ? $fields[ $field ] . ':' . $fields['state'] : $fields[ $field ],
							'row_class' => in_array( $field, array( 'city', 'postcode', 'first_name', 'last_name' ), true ) ? 'half' : '',
						);
					}

					return $settings;
				},
			),
			'packaging'         => array(
				'name'     => _x( 'Packaging', 'shipments', 'woocommerce-germanized' ),
				'order'    => 20,
				'settings' => function () {
					return array(
						array(
							'type' => 'packaging_list',
						),
					);
				},
				'handler'  => function () {
					Admin::save_packaging_list( array(), '' );
				},
			),
			'shipping_provider' => array(
				'name'    => _x( 'Shipping Service Provider', 'shipments', 'woocommerce-germanized' ),
				'order'   => 30,
				'handler' => function () {
					$new_title = isset( $_POST['new_shipping_provider_title'] ) ? wc_clean( wp_unslash( $_POST['new_shipping_provider_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( ! empty( $new_title ) ) {
						wc_stc_create_shipping_provider(
							array(
								'title'                    => $new_title,
								'tracking_url_placeholder' => isset( $_POST['new_shipping_provider_tracking_url_placeholder'] ) ? wc_clean( wp_unslash( $_POST['new_shipping_provider_tracking_url_placeholder'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
							)
						);
					}

					$current_providers = wc_stc_get_available_shipping_providers();

					if ( 1 === count( $current_providers ) ) {
						update_option( 'woocommerce_shiptastic_default_shipping_provider', array_values( $current_providers )[0]->get_name() );
					}
				},
			),
			'returns'           => array(
				'name'    => _x( 'Returns', 'shipments', 'woocommerce-germanized' ),
				'order'   => 40,
				'handler' => function () {
					Admin::save_return_reasons();

					if ( $main_provider = self::get_main_shipping_provider() ) {
						$supports_customer_returns = isset( $_POST[ "shipping_provider_{$main_provider->get_name()}_supports_customer_returns" ] ) ? wc_string_to_bool( wc_clean( wp_unslash( $_POST[ "shipping_provider_{$main_provider->get_name()}_supports_customer_returns" ] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$supports_guest_returns    = isset( $_POST[ "shipping_provider_{$main_provider->get_name()}_supports_guest_returns" ] ) ? wc_string_to_bool( wc_clean( wp_unslash( $_POST[ "shipping_provider_{$main_provider->get_name()}_supports_guest_returns" ] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$needs_manual_confirmation = isset( $_POST[ "shipping_provider_{$main_provider->get_name()}_return_manual_confirmation" ] ) ? wc_string_to_bool( wc_clean( wp_unslash( $_POST[ "shipping_provider_{$main_provider->get_name()}_return_manual_confirmation" ] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

						$main_provider->set_supports_customer_returns( $supports_customer_returns );
						$main_provider->set_supports_guest_returns( $supports_guest_returns );
						$main_provider->set_return_manual_confirmation( $needs_manual_confirmation );

						$main_provider->save();
					}
				},
			),
			'ready'             => array(
				'name'  => _x( 'Ready to ship', 'shipments', 'woocommerce-germanized' ),
				'order' => 50,
			),
		);

		uasort( $all_steps, array( __CLASS__, 'uasort_callback' ) );

		$order = 0;

		foreach ( $all_steps as $key => $step ) {
			$all_steps[ $key ] = wp_parse_args(
				$all_steps[ $key ],
				array(
					'id'               => $key,
					'view'             => $key . '.php',
					'button_next'      => _x( 'Continue', 'shipments-wizard', 'woocommerce-germanized' ),
					'button_next_link' => '',
					'settings'         => null,
					'handler'          => null,
				)
			);

			$all_steps[ $key ]['order'] = ++$order;
		}

		return $all_steps;
	}

	protected static function setup() {
		$default_steps      = self::get_all_steps();
		self::$steps        = $default_steps;
		self::$current_step = isset( $_REQUEST['step'] ) ? sanitize_key( wp_unslash( $_REQUEST['step'] ) ) : current( array_keys( self::$steps ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	protected static function uasort_callback( $step1, $step2 ) {
		if ( $step1['order'] === $step2['order'] ) {
			return 0;
		}

		return ( $step1['order'] < $step2['order'] ) ? -1 : 1;
	}

	/**
	 * Add admin menus/screens.
	 */
	public static function admin_menus() {
		add_submenu_page( '', _x( 'Setup', 'shipments', 'woocommerce-germanized' ), _x( 'Setup', 'shipments', 'woocommerce-germanized' ), 'manage_options', 'wc-shiptastic-setup' );
	}

	/**
	 * @return \Vendidero\Shiptastic\Interfaces\ShippingProvider|\Vendidero\Shiptastic\ShippingProvider\Auto|Simple|null
	 */
	public static function get_main_shipping_provider() {
		$main_provider = wc_stc_get_default_shipping_provider_instance();

		if ( ! $main_provider ) {
			$available = \Vendidero\Shiptastic\ShippingProvider\Helper::instance()->get_available_shipping_providers();

			if ( ! empty( $available ) ) {
				$main_provider = array_values( $available )[0];
			}
		}

		return $main_provider;
	}

	/**
	 * Register/enqueue scripts and styles for the Setup Wizard.
	 *
	 * Hooked onto 'admin_enqueue_scripts'.
	 */
	public static function enqueue_scripts() {
		if ( self::is_active() ) {
			wp_register_style( 'woocommerce_shiptastic_wizard', Package::get_assets_url( 'static/admin-wizard-styles.css' ), array( 'wp-admin', 'dashicons', 'buttons', 'woocommerce_shiptastic_admin' ), Package::get_version() );
			wp_enqueue_style( 'woocommerce_shiptastic_wizard' );

			wp_register_script( 'wc-shiptastic-admin-wizard', Package::get_assets_url( 'static/admin-wizard.js' ), array( 'wc-shiptastic-admin', 'wc-shiptastic-admin-settings' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
			wp_enqueue_script( 'wc-shiptastic-admin-wizard' );

			wp_localize_script(
				'wc-shiptastic-admin-wizard',
				'wc_shiptastic_admin_wizard_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}

	private static function is_active() {
		return ( isset( $_GET['page'] ) && 'wc-shiptastic-setup' === wc_clean( wp_unslash( $_GET['page'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public function get_error_message( $step = false ) {
		if ( isset( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error_key    = sanitize_key( wp_unslash( $_GET['error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_step = $this->get_current_step( $step );

			if ( isset( $current_step['errors'][ $error_key ] ) ) {
				return $current_step['errors'][ $error_key ];
			}
		}

		return false;
	}

	/**
	 * Show the setup wizard.
	 */
	public static function render() {
		if ( ! self::is_active() ) {
			return;
		}

		if ( ! $current_step = self::get_current_step() ) {
			return;
		}

		$steps    = self::get_steps();
		$step_pct = ceil( $current_step['order'] / count( $steps ) * 100 );

		set_current_screen( 'wc-shiptastic-setup' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php echo esc_html_x( 'Shiptastic &rsaquo; Setup Wizard', 'shipments', 'woocommerce-germanized' ); ?></title>
			<?php do_action( 'admin_enqueue_scripts' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_print_scripts' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="wc-shiptastic-wizard wp-core-ui wc-shiptastic-wizard-step-<?php echo esc_attr( self::$current_step ); ?>">
			<div class="wc-shiptastic-wizard-header">
				<div class="wc-shiptastic-wizard-progress-bar">
					<div class="wc-shiptastic-wizard-progress-bar-container">
						<div class="wc-shiptastic-wizard-progress-bar-filler" style="width: <?php echo esc_attr( $step_pct ); ?>%;"></div>
					</div>
				</div>
				<div class="wc-shiptastic-wizard-header-nav">
					<div class="wc-shiptastic-wizard-logo">
						<span class="shiptastic-logo">
							<?php include Package::get_path( 'assets/icons/logo.svg' ); ?>
						</span>
					</div>
					<?php if ( $current_step['order'] < count( $steps ) ) : ?>
						<a class="wc-shiptastic-wizard-link wc-shiptastic-wizard-link-skip" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'skip' => esc_attr( $current_step['id'] ) ), self::get_step_url( self::get_next_step() ) ), 'wc-shiptastic-wizard-skip' ) ); ?>"><?php echo esc_html_x( 'Skip Step', 'shipments', 'woocommerce-germanized' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<form class="wc-shiptastic-wizard-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<div class="wc-shiptastic-wizard-content">
					<?php self::view( $current_step['id'] ); ?>

					<input type="hidden" name="action" value="woocommerce_stc_next_wizard_step" />
					<input type="hidden" name="step" value="<?php echo esc_attr( $current_step['id'] ); ?>" />

					<?php wp_nonce_field( 'wc-shiptastic-wizard' ); ?>
				</div>
			</form>
			<div class="wc-shiptastic-wizard-footer">
				<div class="escape">
					<a href="<?php echo esc_url( admin_url() ); ?>"><?php echo esc_html_x( 'Return to Dashboard', 'shipments', 'woocommerce-germanized' ); ?></a>
				</div>
			</div>
			<?php do_action( 'admin_footer', '' ); ?>
			<?php do_action( 'admin_print_footer_scripts' ); ?>
		</body>
		</html>
		<?php
		exit;
	}

	protected static function view( $step_key ) {
		if ( $step = self::get_step( $step_key ) ) {
			$view_file = str_replace( '_', '-', sanitize_file_name( $step['view'] ) );

			if ( file_exists( Package::get_path( 'includes/admin/views/wizard/' . $view_file ) ) ) {
				include Package::get_path( 'includes/admin/views/wizard/' . $view_file );
			}
		}
	}

	public static function get_settings( $key ) {
		$settings = array();

		if ( ! $step = self::get_step( $key ) ) {
			return $settings;
		}

		$settings = is_null( $step['settings'] ) ? array() : call_user_func( $step['settings'] );

		return (array) $settings;
	}

	public static function has_settings( $key ) {
		$settings = self::get_settings( $key );

		return ! empty( $settings );
	}

	public static function get_current_step( $key = false ) {
		$steps = self::get_steps();

		if ( ! $key ) {
			$key = self::$current_step;
		}

		return self::get_step( $key );
	}

	public static function get_step( $key ) {
		$steps = self::get_steps();

		return ( isset( $steps[ $key ] ) ? $steps[ $key ] : false );
	}

	public static function get_step_url( $key ) {
		if ( ! $step = self::get_current_step( $key ) ) {
			return false;
		}

		return admin_url( 'admin.php?page=wc-shiptastic-setup&step=' . $key );
	}

	public static function get_next_step( $current_step = null ) {
		self::get_steps();

		$current = null === $current_step ? self::get_current_step() : $current_step;
		$next    = $current['id'];

		foreach ( self::$steps as $step_key => $step ) {
			if ( $step['order'] > $current['order'] ) {
				$next = $step_key;
				break;
			}
		}

		return $next;
	}

	public static function save() {
		check_ajax_referer( 'wc-shiptastic-wizard' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		/**
		 * Use all steps to determine the current step as some steps may be hidden conditionally
		 * and that might have changed upon saving the request.
		 */
		$current_step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : self::get_current_step()['id'];
		$all_steps    = self::get_all_steps();
		$step         = array_key_exists( $current_step, $all_steps ) ? $all_steps[ $current_step ] : false;

		if ( ! $step ) {
			wp_die();
		}

		self::$current_step = $current_step;
		$result             = true;
		$next_step          = self::get_next_step( $step );

		if ( ! is_null( $step['handler'] ) ) {
			$result = call_user_func( $step['handler'] );
		} else {
			$settings = self::get_settings( $current_step );

			if ( ! empty( $settings ) ) {
				\WC_Admin_Settings::save_fields( $settings );
			}
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result, 500 );
		} else {
			wp_send_json(
				array(
					'redirect' => self::get_step_url( $next_step ),
				)
			);
		}

		exit();
	}
}
