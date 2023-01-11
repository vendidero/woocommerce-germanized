<?php

use Vendidero\Germanized\Shipments\Package;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class        WC_GZD_Settings_Germanized
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Germanized extends WC_Settings_Page {

	protected $id = 'germanized';

	protected $tabs = null;

	public function __construct() {
		$this->label = __( 'Germanized', 'woocommerce-germanized' );
		$this->get_tabs();

		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		add_filter( 'woocommerce_navigation_is_connected_page', array( $this, 'add_wc_admin_breadcrumbs' ), 5, 2 );

		parent::__construct();
	}

	public function add_wc_admin_breadcrumbs( $is_connected, $current_page ) {
		if ( false === $is_connected && false === $current_page && $this->is_active() ) {
			$page_id = 'wc-settings';

			/**
			 * Check whether Woo Admin is actually loaded, e.g. core pages have been registered before
			 * registering our page(s). This may not be the case if WC Admin is disabled, e.g. via a
			 * woocommerce_admin_features filter.
			 */
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '6.5.0', '>=' ) && ! function_exists( 'wc_admin_connect_core_pages' ) ) {
				return $is_connected;
			}

			if ( ! class_exists( 'Automattic\WooCommerce\Admin\PageController' ) ) {
				return $is_connected;
			}

			$page_controller = Automattic\WooCommerce\Admin\PageController::get_instance();

			if ( ! is_callable( array( $page_controller, 'get_current_screen_id' ) ) ) {
				return $is_connected;
			}

			$screen_id = $page_controller->get_current_screen_id();

			if ( preg_match( "/^woocommerce_page_{$page_id}\-/", $screen_id ) ) {
				add_filter( 'woocommerce_navigation_get_breadcrumbs', array( $this, 'filter_wc_admin_breadcrumbs' ), 20 );
				return true;
			}
		}

		return $is_connected;
	}

	public function filter_wc_admin_breadcrumbs( $breadcrumbs ) {
		if ( ! function_exists( 'wc_admin_get_core_pages_to_connect' ) ) {
			return $breadcrumbs;
		}

		$core_pages = wc_admin_get_core_pages_to_connect();
		$tab        = isset( $_GET['tab'] ) ? wc_clean( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab_clean  = str_replace( 'germanized-', '', $tab );

		$new_breadcrumbs = array(
			array(
				esc_url_raw( add_query_arg( 'page', 'wc-settings', 'admin.php' ) ),
				$core_pages['wc-settings']['title'],
			),
		);

		if ( $this->id === $tab ) {
			$new_breadcrumbs[] = $this->label;
		} else {
			$new_breadcrumbs[] = array(
				esc_url_raw(
					add_query_arg(
						array(
							'page' => 'wc-settings',
							'tab'  => 'germanized',
						),
						'admin.php'
					)
				),
				$this->label,
			);
		}

		foreach ( $this->get_tabs() as $tab ) {
			if ( $tab_clean === $tab->get_name() ) {
				$new_breadcrumbs[] = preg_replace( '/<[^>]*>[^<]*<[^>]*>/', '', $tab->get_label() );
				break;
			}
		}

		return $new_breadcrumbs;
	}

	private function get_inner_settings( $section_id = '' ) {
		$settings = array();

		foreach ( $this->get_tabs() as $tab ) {
			$sections = $tab->get_sections();

			if ( ! empty( $sections ) ) {
				foreach ( $tab->get_sections() as $section_name => $section ) {
					$settings = array_merge( $settings, $tab->get_settings( $section_name ) );
				}
			} else {
				$settings = array_merge( $settings, $tab->get_settings() );
			}
		}

		return $settings;
	}

	public function get_settings_for_section_core( $section_id ) {
		return $this->get_inner_settings( $section_id );
	}

	public function get_settings( $section_id = '' ) {
		return $this->get_inner_settings( $section_id );
	}

	public function admin_styles() {
		// Admin styles for WC pages only.
		if ( $this->is_active() ) {
			wp_enqueue_style( 'woocommerce-gzd-admin-settings' );

			/**
			 * This action indicates that the admin settings styles are enqueued.
			 *
			 * @since 3.0.0
			 */
			do_action( 'woocommerce_gzd_admin_settings_styles' );
		}
	}

	public function admin_scripts() {
		if ( $this->is_active() ) {
			wp_enqueue_script( 'wc-gzd-admin-settings' );

			/**
			 * This action indicates that the admin settings scripts are enqueued.
			 *
			 * @since 3.0.0
			 */
			do_action( 'woocommerce_gzd_admin_settings_scripts' );
		}
	}

	protected function is_active() {
		if ( isset( $_GET['tab'] ) && strpos( wc_clean( wp_unslash( $_GET['tab'] ) ), 'germanized' ) !== false ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return false;
	}

	public function add_body_classes( $classes ) {
		if ( $this->is_active() ) {
			$classes = $classes . ' wc-gzd-settings';
		}

		return $classes;
	}

	public function get_tabs() {
		include_once dirname( __FILE__ ) . '/abstract-wc-gzd-settings-tab.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-general.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-shopmarks.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-emails.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-taxes.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-button-solution.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-checkboxes.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-doi.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-oss.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-contract.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-invoices.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-multistep-checkout.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-terms-generator.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-revocation-generator.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-trusted-shops.php';

		if ( class_exists( '\Vendidero\Germanized\Shipments\Package' ) && Package::has_dependencies() ) {
			include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-shipments.php';
			include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-shipping-provider.php';
		}

		/**
		 * Filter to register or remove certain setting tabs from the Germanized settings screen.
		 * Make sure that your class is loaded before adding it to the tabs array.
		 *
		 * @param array $tabs Array containing key => value pairs of tab name and class name.
		 *
		 * @since 3.0.0
		 *
		 */
		$tabs = apply_filters(
			'woocommerce_gzd_admin_settings_tabs',
			array(
				'general'                        => 'WC_GZD_Settings_Tab_General',
				'shopmarks'                      => 'WC_GZD_Settings_Tab_Shopmarks',
				'taxes'                          => 'WC_GZD_Settings_Tab_Taxes',
				'button_solution'                => 'WC_GZD_Settings_Tab_Button_Solution',
				'multistep_checkout'             => 'WC_GZD_Settings_Tab_Multistep_Checkout',
				'invoices'                       => 'WC_GZD_Settings_Tab_Invoices',
				'shipments'                      => 'WC_GZD_Settings_Tab_Shipments',
				'shipping_provider'              => 'WC_GZD_Settings_Tab_Shipping_Provider',
				'double_opt_in'                  => 'WC_GZD_Settings_Tab_DOI',
				'emails'                         => 'WC_GZD_Settings_Tab_Emails',
				'checkboxes'                     => 'WC_GZD_Settings_Tab_Checkboxes',
				'contract'                       => 'WC_GZD_Settings_Tab_Contract',
				'terms_generator'                => 'WC_GZD_Settings_Tab_Terms_Generator',
				'revocation_generator'           => 'WC_GZD_Settings_Tab_Revocation_Generator',
				'oss'                            => 'WC_GZD_Settings_Tab_OSS',
				'trusted_shops_easy_integration' => 'WC_GZD_Settings_Tab_Trusted_Shops',
			)
		);

		if ( is_null( $this->tabs ) ) {
			$this->tabs = array();

			foreach ( $tabs as $key => $tab ) {
				if ( class_exists( $tab ) ) {
					$this->tabs[ $key ] = new $tab();
				}
			}
		}

		return $this->tabs;
	}

	/**
	 * @param $name
	 *
	 * @return bool|WC_GZD_Settings_Tab
	 */
	public function get_tab_by_name( $name ) {
		foreach ( $this->get_tabs() as $tab ) {
			if ( $name === $tab->get_name() ) {
				return $tab;
			}
		}

		return false;
	}

	public function output() {
		$GLOBALS['hide_save_button'] = true;
		$tabs                        = $this->get_tabs();

		include_once dirname( __FILE__ ) . '/views/html-admin-settings-tabs.php';
	}
}
