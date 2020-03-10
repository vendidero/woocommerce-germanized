<?php
/**
 * Display notices in admin.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Admin_Notices' ) ) :

	/**
	 * Adds Notices after Install / Update to Admin
	 *
	 * @class        WC_GZD_Admin_Notices
	 * @version        1.0.0
	 * @author        Vendidero
	 */
	class WC_GZD_Admin_Notices {

		/**
		 * Single instance current class
		 *
		 * @var object
		 */
		protected static $_instance = null;

		protected $notes = null;

		/**
		 * Ensures that only one instance of this class is loaded or can be loaded.
		 *
		 * @static
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		protected function __construct() {
			add_action( 'admin_init', array( $this, 'check_notice_hide' ) );

			add_action( 'after_switch_theme', array( $this, 'remove_theme_notice_hide' ) );
			add_action( 'admin_print_styles', array( $this, 'add_notices' ), 1 );

			include_once( 'notes/class-wc-gzd-admin-note.php' );
			include_once( 'notes/class-wc-gzd-admin-note-theme-supported.php' );
			include_once( 'notes/class-wc-gzd-admin-note-update.php' );
			include_once( 'notes/class-wc-gzd-admin-note-review.php' );
			include_once( 'notes/class-wc-gzd-admin-note-template-outdated.php' );
			include_once( 'notes/class-wc-gzd-admin-note-pro.php' );
			include_once( 'notes/class-wc-gzd-admin-note-dhl-importer.php' );
		}

		public function enable_notices() {
			$enabled = true;

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				$enabled = false;
			}

			if ( get_option( 'woocommerce_gzd_disable_notices' ) === 'yes' ) {
				$enabled = false;
			}

			/**
			 * Filter to enable or disable admin notices in WP-Admin.
			 *
			 * @param bool $enabled Whether notices are enabled or disabled.
			 *
			 * @since 1.8.5
			 *
			 */
			return apply_filters( 'woocommerce_gzd_enable_notices', $enabled );
		}

		/**
		 * @return WC_GZD_Admin_Note[]
		 */
		public function get_notes() {
			if ( is_null( $this->notes ) ) {

				$notes = array(
					'WC_GZD_Admin_Note_Theme_Supported',
					'WC_GZD_Admin_Note_Update',
					'WC_GZD_Admin_Note_Review',
					'WC_GZD_Admin_Note_Template_Outdated',
					'WC_GZD_Admin_Note_Pro',
					'WC_GZD_Admin_Note_DHL_Importer'
				);

				$this->notes = array();

				foreach( $notes as $note ) {
					$note = new $note();

					$this->notes[ $note->get_name() ] = $note;
				}
			}

			return $this->notes;
		}

		/**
		 * @param $name
		 *
		 * @return bool|WC_GZD_Admin_Note
		 */
		public function get_note( $name ) {
			$notes = $this->get_notes();

			if ( array_key_exists( $name, $notes ) ) {
				return $notes[ $name ];
			}

			return false;
		}

		/**
		 * Add notices + styles if needed.
		 */
		public function add_notices() {
			$screen          = get_current_screen();
			$screen_id       = $screen ? $screen->id : '';
			$show_on_screens = array(
				'dashboard',
				'plugins',
			);

			$wc_screen_ids = function_exists( 'wc_get_screen_ids' ) ? wc_get_screen_ids() : array();
			$wc_screen_ids = array_merge ( $wc_screen_ids, array( 'woocommerce_page_wc-admin' ) );

			// Notices should only show on WooCommerce screens, the main dashboard, and on the plugins screen.
			if ( ! in_array( $screen_id, $wc_screen_ids, true ) && ! in_array( $screen_id, $show_on_screens, true ) ) {
				return;
			}

			foreach( $this->get_notes() as $note_id => $note ) {
				$note->queue();
			}
		}

		public function remove_theme_notice_hide() {
			if ( $note = $this->get_note( 'theme_supported' ) ) {
				$note->reset();
			}

			if ( $note = $this->get_note( 'template_outdated' ) ) {
				$note->reset();
			}
		}

		public function check_notice_hide() {

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			$notes = $this->get_notes();

			foreach( $notes as $note ) {
				$notice            = 'wc-gzd-hide-' . str_replace( '_', '-', $note->get_name() ) . '-notice';
				$notice_deactivate = 'wc-gzd-disable-' . str_replace( '_', '-', $note->get_name() ) . '-notice';

				if ( isset( $_GET['notice'] ) && $_GET['notice'] === $notice && isset( $_GET['nonce'] ) && check_admin_referer( $notice, 'nonce' ) ) {

					$note->dismiss();
					$redirect_url = remove_query_arg( 'notice', remove_query_arg( 'nonce', $_SERVER['REQUEST_URI'] ) );

					wp_safe_redirect( $redirect_url );
					exit();
				} elseif ( isset( $_GET['notice'] ) && $_GET['notice'] === $notice_deactivate && isset( $_GET['nonce'] ) && check_admin_referer( $notice_deactivate, 'nonce' ) ) {

					$note->deactivate();
					$redirect_url = remove_query_arg( 'notice', remove_query_arg( 'nonce', $_SERVER['REQUEST_URI'] ) );

					wp_safe_redirect( $redirect_url );
					exit();
				}
			}
		}

		public function is_theme_ready() {
			$stylesheet = get_stylesheet_directory() . '/style.css';
			$data       = get_file_data( $stylesheet, array( 'wc_gzd_compatible' => 'wc_gzd_compatible' ) );

			if ( ! $data['wc_gzd_compatible'] && ! current_theme_supports( 'woocommerce-germanized' ) ) {
				return false;
			}

			return true;
		}

		public function is_theme_supported_by_pro() {
			$supporting = array(
				'enfold',
				'flatsome',
				'storefront',
				'virtue',
				'shopkeeper',
				'astra'
			);

			$current = wp_get_theme();

			if ( in_array( $current->get_template(), $supporting ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Checks if current theme is woocommerce germanized compatible
		 *
		 * @return boolean
		 */
		public function is_theme_compatible() {
			$templates_to_check = WC_germanized()->get_critical_templates();

			if ( ! empty( $templates_to_check ) ) {
				foreach ( $templates_to_check as $template ) {
					$template_path = trailingslashit( 'woocommerce' ) . $template;

					$theme_template = locate_template( array(
						$template_path,
						$template
					) );

					if ( $theme_template ) {
						return false;
					}
				}
			}

			return true;
		}
	}

endif;

return WC_GZD_Admin_Notices::instance();
