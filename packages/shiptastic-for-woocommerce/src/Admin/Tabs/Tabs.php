<?php

namespace Vendidero\Shiptastic\Admin\Tabs;

use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Extensions;
use Vendidero\Shiptastic\Package;

class Tabs extends \WC_Settings_Page {

	protected $id = 'shiptastic';

	protected $tabs = null;

	public function __construct() {
		$this->label = _x( 'Shiptastic', 'shipments-settings-page-title', 'woocommerce-germanized' );
		$this->get_tabs();

		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		add_filter( 'woocommerce_navigation_is_connected_page', array( $this, 'add_wc_admin_breadcrumbs' ), 5, 2 );

		parent::__construct();

		if ( Package::is_integration() ) {
			remove_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		}
	}

	protected function get_breadcrumb() {
		$breadcrumb = Settings::get_main_breadcrumb();

		return $breadcrumb;
	}

	public function header() {
		$breadcrumb = $this->get_breadcrumb();
		$count      = 0;
		?>
		<ul class="wc-shiptastic-settings-breadcrumb">
			<?php
			foreach ( $breadcrumb as $breadcrumb_item ) :
				++$count;
				?>
				<li class="breadcrumb-item breadcrumb-item-<?php echo esc_attr( $breadcrumb_item['class'] ) . ' ' . ( count( $breadcrumb ) === $count ? 'breadcrumb-item-active' : '' ); ?>"><?php echo ( ! empty( $breadcrumb_item['href'] ) ? '<a class="breadcrumb-link" href="' . esc_attr( $breadcrumb_item['href'] ) . '">' . wp_kses_post( $breadcrumb_item['title'] ) . '</a>' : wp_kses_post( $breadcrumb_item['title'] ) ); ?></li>
			<?php endforeach; ?>

			<li class="title-action">
				<a class="page-title-action" href="<?php echo esc_url( esc_html_x( 'https://vendidero.com/doc/shiptastic/home-shiptastic', 'shipments-help-url', 'woocommerce-germanized' ) ); ?>"><?php echo esc_html_x( 'Documentation', 'shipments', 'woocommerce-germanized' ); ?></a>
			</li>
		</ul>

		<?php
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

			$page_controller = \Automattic\WooCommerce\Admin\PageController::get_instance();

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
		$tab_clean  = str_replace( 'shiptastic-', '', $tab );

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
							'tab'  => 'shiptastic',
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

	public function admin_scripts() {
		if ( $this->is_active() ) {
			wp_enqueue_script( 'wc-shiptastic-admin-settings' );

			/**
			 * This action indicates that the admin settings scripts are enqueued.
			 *
			 */
			do_action( 'woocommerce_shiptastic_admin_settings_scripts' );
		}
	}

	protected function is_active() {
		if ( isset( $_GET['tab'] ) && strpos( wc_clean( wp_unslash( $_GET['tab'] ) ), 'shiptastic' ) !== false ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return false;
	}

	public function add_body_classes( $classes ) {
		if ( $this->is_active() ) {
			$classes = $classes . ' wc-shiptastic-settings';

			if ( Extensions::compare_versions( Extensions::get_plugin_version( 'woocommerce' ), '9.9.0', '>=' ) ) {
				$classes = $classes . ' wc-shiptastic-settings-modern-nav';
			}
		}

		return $classes;
	}

	public function get_tabs() {
		/**
		 * Filter to register or remove certain setting tabs from the settings screen.
		 * Make sure that your class is loaded before adding it to the tabs array.
		 *
		 * @param array $tabs Array containing key => value pairs of tab name and class name.
		 *
		 *
		 */
		$tabs = apply_filters(
			'woocommerce_shiptastic_admin_settings_tabs',
			array(
				'general'           => General::class,
				'shipping_provider' => ShippingProvider::class,
				'packaging'         => Packaging::class,
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
	 * @return bool|Tab
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
		$integration                 = $this;

		include Package::get_path( 'includes/admin/views/tabs/html-admin-settings-tabs.php' );
	}
}
