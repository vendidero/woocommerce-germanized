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
			add_action( 'admin_init', array( $this, 'check_notice_hide' ), 25 );

			add_action( 'after_switch_theme', array( $this, 'remove_theme_notice_hide' ), 25 );
			add_action( 'admin_print_styles', array( $this, 'add_notices' ), 1 );
			add_action( 'in_plugin_update_message-woocommerce-germanized/woocommerce-germanized.php', array( $this, 'pro_incompatibility_notice' ), 10, 2 );
			add_filter( 'site_transient_update_plugins', array( $this, 'pro_incompatibility_plain_update_message' ), 10 );

			include_once 'notes/class-wc-gzd-admin-note.php';
			include_once 'notes/class-wc-gzd-admin-note-theme-supported.php';
			include_once 'notes/class-wc-gzd-admin-note-update.php';
			include_once 'notes/class-wc-gzd-admin-note-review.php';
			include_once 'notes/class-wc-gzd-admin-note-template-outdated.php';
			include_once 'notes/class-wc-gzd-admin-note-pro.php';
			include_once 'notes/class-wc-gzd-admin-note-dhl-importer.php';
			include_once 'notes/class-wc-gzd-admin-note-base-country.php';
			include_once 'notes/class-wc-gzd-admin-note-internetmarke-importer.php';
			include_once 'notes/class-wc-gzd-admin-note-shipping-excl-tax.php';
			include_once 'notes/class-wc-gzd-admin-note-encryption.php';
			include_once 'notes/class-wc-gzd-admin-note-virtual-vat.php';
			include_once 'notes/class-wc-gzd-admin-note-legal-news.php';
			include_once 'notes/class-wc-gzd-admin-note-oss-install.php';
			include_once 'notes/class-wc-gzd-admin-note-ts-install.php';
		}

		/**
		 * Inform users of possible compatibility conflicts. Append a notice in case of detecting an incompatibility.
		 *
		 * @see https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/admin/helper/class-wc-helper-updater.php#L25
		 *
		 * @param $data
		 *
		 * @return mixed
		 */
		public function pro_incompatibility_plain_update_message( $data ) {
			if ( isset( $data->response ) && array_key_exists( 'woocommerce-germanized/woocommerce-germanized.php', $data->response ) && WC_germanized()->is_pro() ) {
				$plugin_data = $data->response['woocommerce-germanized/woocommerce-germanized.php'];

				if ( ! isset( $plugin_data->upgrade_notice ) && ! $this->is_next_update_compatible_with_pro( $plugin_data->new_version ) ) {
					$data->response['woocommerce-germanized/woocommerce-germanized.php']->upgrade_notice = $this->get_pro_incompatible_message( true );
				}
			}

			return $data;
		}

		protected function is_next_update_compatible_with_pro( $new_version ) {
			$is_supported = true;

			if ( WC_germanized()->is_pro() ) {
				// Check compatibility with next version
				$max_version_supported = '';

				if ( class_exists( 'WC_GZDP_Dependencies' ) ) {
					$dep = WC_GZDP_Dependencies::instance();

					if ( is_callable( array( $dep, 'get_wc_gzd_max_version_supported' ) ) ) {
						$max_version_supported = WC_GZDP_Dependencies::instance()->get_wc_gzd_max_version_supported();
					}
				}

				if ( ! empty( $max_version_supported ) ) {
					/**
					 * Explicitly use $max_version_supported as first parameter to make sure
					 * the more accurate $new_version string is cut if necessary.
					 */
					if ( \Vendidero\Germanized\PluginsHelper::compare_versions( $max_version_supported, $new_version, '<' ) ) {
						$is_supported = false;
					}
				}
			}

			return $is_supported;
		}

		public function pro_incompatibility_notice( $data, $plugin ) {
			if ( WC_germanized()->is_pro() && ! $this->is_next_update_compatible_with_pro( $plugin->new_version ) ) {
				echo '</p>' . wp_kses_post( $this->get_pro_incompatible_message() );
			}
		}

		protected function get_pro_incompatible_message( $plain = false ) {
			if ( $plain ) {
				return sprintf( __( '<strong>Be aware!</strong> This update is not compatible with your current Germanized Pro version. Please check for updates (%s) before updating Germanized to prevent compatibility issues.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/germanized-pro-aktualisieren' );
			} else {
				ob_start();
				include __DIR__ . '/views/html-notice-update-pro-incompatible.php';
				return ob_get_clean();
			}
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

				$core_notes = array(
					'WC_GZD_Admin_Note_Theme_Supported',
					'WC_GZD_Admin_Note_Update',
					'WC_GZD_Admin_Note_Review',
					'WC_GZD_Admin_Note_Template_Outdated',
					'WC_GZD_Admin_Note_Pro',
					'WC_GZD_Admin_Note_DHL_Importer',
					'WC_GZD_Admin_Note_Base_Country',
					'WC_GZD_Admin_Note_Internetmarke_Importer',
					'WC_GZD_Admin_Note_Shipping_Excl_Tax',
					'WC_GZD_Admin_Note_Legal_News',
				);

				if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
					$core_notes[] = 'WC_GZD_Admin_Note_Encryption';
				}

				if ( 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' ) ) {
					$core_notes[] = 'WC_GZD_Admin_Note_Virtual_Vat';
				}

				if ( 'yes' === get_option( 'woocommerce_gzd_is_oss_standalone_update' ) ) {
					$core_notes[] = 'WC_GZD_Admin_Note_OSS_Install';
				}

				if ( 'yes' === get_option( 'woocommerce_gzd_is_ts_standalone_update' ) ) {
					$core_notes[] = 'WC_GZD_Admin_Note_TS_Install';
				}

				$notes       = apply_filters( 'woocommerce_gzd_admin_notes', $core_notes );
				$this->notes = array();

				foreach ( $notes as $note ) {
					$note                             = new $note();
					$this->notes[ $note->get_name() ] = $note;
				}
			}

			return $this->notes;
		}

		public function get_woo_note( $id = '' ) {
			if ( class_exists( '\Automattic\WooCommerce\Admin\Notes\Note' ) ) {
				$note = new \Automattic\WooCommerce\Admin\Notes\Note( $id );
			} else {
				$note = new \Automattic\WooCommerce\Admin\Notes\WC_Admin_Note( $id );
			}

			return $note;
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
			$wc_screen_ids = array_merge( $wc_screen_ids, array( 'woocommerce_page_wc-admin' ) );

			// Notices should only show on WooCommerce screens, the main dashboard, and on the plugins screen.
			if ( ! in_array( $screen_id, $wc_screen_ids, true ) && ! in_array( $screen_id, $show_on_screens, true ) ) {
				return;
			}

			foreach ( $this->get_notes() as $note_id => $note ) {
				$note->queue();
			}
		}

		public function activate_legal_news_note() {
			update_option( '_wc_gzd_has_legal_news', 'yes' );

			/**
			 * Reset to make sure the note is not dismissed.
			 */
			if ( $note = $this->get_note( 'legal_news' ) ) {
				$note->reset();
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

			if ( isset( $_GET['notice'] ) ) {
				$notes = $this->get_notes();

				foreach ( $notes as $note ) {
					$notice            = 'wc-gzd-hide-' . str_replace( '_', '-', $note->get_name() ) . '-notice';
					$notice_deactivate = 'wc-gzd-disable-' . str_replace( '_', '-', $note->get_name() ) . '-notice';

					if ( $_GET['notice'] === $notice && isset( $_GET['nonce'] ) && check_admin_referer( $notice, 'nonce' ) ) {

						$note->dismiss();
						$redirect_url = remove_query_arg( 'notice', remove_query_arg( 'nonce', wp_unslash( $_SERVER['REQUEST_URI'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

						wp_safe_redirect( esc_url_raw( $redirect_url ) );
						exit();
					} elseif ( $_GET['notice'] === $notice_deactivate && isset( $_GET['nonce'] ) && check_admin_referer( $notice_deactivate, 'nonce' ) ) {

						$note->deactivate();
						$redirect_url = remove_query_arg( 'notice', remove_query_arg( 'nonce', wp_unslash( $_SERVER['REQUEST_URI'] ) ) );  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

						wp_safe_redirect( esc_url_raw( $redirect_url ) );
						exit();
					}
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
				'virtue',
				'flatsome',
				'enfold',
				'storefront',
				'shopkeeper',
				'astra',
				'twentytwentytwo',
				'twentytwentythree',
				'oceanwp',
			);

			$current = wp_get_theme();

			if ( in_array( $current->get_template(), $supporting, true ) ) {
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

					$theme_template = locate_template(
						array(
							$template_path,
							$template,
						)
					);

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
