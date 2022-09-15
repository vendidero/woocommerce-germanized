<?php
/**
 * WC GZD Setup Wizard Class
 *
 * @package  woocommerce-germanized
 * @since    2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_GZD_Admin_Setup_Wizard' ) ) :

	/**
	 * The Storefront NUX Admin class
	 */
	class WC_GZD_Admin_Setup_Wizard {

		/**
		 * Current step
		 *
		 * @var string
		 */
		private $step = '';

		/**
		 * Steps for the setup wizard
		 *
		 * @var array
		 */
		private $steps = array();

		/**
		 * Setup class.
		 *
		 * @since 2.2.0
		 */
		public function __construct() {
			if ( did_action( 'plugins_loaded' ) ) {
				$this->load();
			} else {
				add_action( 'plugins_loaded', array( $this, 'load' ) );
			}
		}

		public function load() {
			if ( current_user_can( 'manage_options' ) ) {
				add_action( 'admin_menu', array( $this, 'admin_menus' ), 20 );
				add_action( 'admin_init', array( $this, 'initialize' ), 10 );
				add_action( 'admin_init', array( $this, 'setup_wizard' ), 20 );

				// Load after base has registered scripts
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 5 );
				add_action( 'admin_post_wc_gzd_setup', array( $this, 'save' ) );
			}
		}

		public function initialize() {
			$default_steps = array(
				'germanize'         => array(
					'name'    => __( 'Germanize', 'woocommerce-germanized' ),
					'view'    => 'germanize.php',
					'handler' => array( $this, 'wc_gzd_setup_germanize_save' ),
					'errors'  => array(
						'oss_install' => current_user_can( 'install_plugins' ) ? sprintf( __( 'There was an error while automatically installing %1$s. %2$s', 'woocommerce-germanized' ), esc_html__( 'One Stop Shop', 'woocommerce-germanized' ), \Vendidero\Germanized\PluginsHelper::get_plugin_manual_install_message( 'one-stop-shop-woocommerce' ) ) : '',
					),
					'order'   => 1,
				),
				'settings'          => array(
					'name'    => __( 'Settings', 'woocommerce-germanized' ),
					'view'    => 'settings.php',
					'handler' => array( $this, 'wc_gzd_setup_settings_save' ),
					'order'   => 2,
					'errors'  => array(),
				),
				'shipping_provider' => array(
					'name'    => __( 'Shipping Provider', 'woocommerce-germanized' ),
					'view'    => 'provider.php',
					'handler' => array( $this, 'wc_gzd_setup_provider_save' ),
					'order'   => 5,
					'errors'  => array(),
				),
				'first_steps'       => array(
					'name'             => __( 'First Steps', 'woocommerce-germanized' ),
					'view'             => 'first-steps.php',
					'order'            => 10,
					'errors'           => array(),
					'button_next'      => __( 'Start tutorial', 'woocommerce-germanized' ),
					'button_next_link' => admin_url( 'admin.php?page=wc-settings&tab=germanized&tutorial=yes' ),
				),
			);

			if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
				if ( ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) {
					$new_key = WC_GZD_Secret_Box_Helper::get_random_encryption_key();

					if ( ! is_wp_error( $new_key ) ) {
						$default_steps['encrypt'] = array(
							'name'        => __( 'Encryption', 'woocommerce-germanized' ),
							'view'        => 'encrypt.php',
							'handler'     => array( $this, 'wc_gzd_setup_encrypt_save' ),
							'order'       => 3,
							'errors'      => array(),
							'button_next' => ( ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() && WC_GZD_Secret_Box_Helper::supports_auto_insert() ) ? esc_attr__( 'Insert key', 'woocommerce-germanized' ) : esc_attr__( 'Continue', 'woocommerce-germanized' ),
						);
					}
				}
			}

			$this->steps = $default_steps;
			uasort( $this->steps, array( $this, 'uasort_callback' ) );

			$order = 0;

			foreach ( $this->steps as $key => $step ) {
				$this->steps[ $key ]['order'] = ++$order;
			}

			$this->step = isset( $_REQUEST['step'] ) ? sanitize_key( wp_unslash( $_REQUEST['step'] ) ) : current( array_keys( $this->steps ) );

			// Check if a step has been skipped and maybe delete som tmp options
			if ( isset( $_GET['skip'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'wc-gzd-setup-skip' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$skipped_step = sanitize_key( wp_unslash( $_GET['skip'] ) );
			}
		}

		protected function uasort_callback( $step1, $step2 ) {
			if ( $step1['order'] === $step2['order'] ) {
				return 0;
			}

			return ( $step1['order'] < $step2['order'] ) ? -1 : 1;
		}

		protected function get_settings( $step = '' ) {
			$settings = array();

			if ( 'germanize' === $step ) {
				$pages = wc_get_page_id( 'revocation' ) < 1 ? array(
					'title'   => __( 'Pages', 'woocommerce-germanized' ),
					'desc'    => __( 'Create legal pages placeholders e.g. terms & conditions.', 'woocommerce-germanized' ),
					'id'      => 'woocommerce_gzd_create_legal_pages',
					'default' => 'yes',
					'type'    => 'gzd_toggle',
				) : array();

				$settings = array(
					array(
						'title' => '',
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'germanized_options',
					),
					array(
						'title'   => __( 'Settings', 'woocommerce-germanized' ),
						'desc'    => __( 'Germanize WooCommerce settings (e.g. currency, tax display).', 'woocommerce-germanized' ),
						'id'      => 'woocommerce_gzd_germanize_settings',
						'default' => 'yes',
						'type'    => 'gzd_toggle',
					),
					$pages,
					array(
						'title'   => _x( 'OSS status', 'install', 'woocommerce-germanized' ),
						'desc'    => sprintf( __( 'I\'m participating in the <a href="%s" target="_blank" rel="noopener">One Stop Shop (OSS) procedure</a>.', 'woocommerce-germanized' ), 'https://ec.europa.eu/taxation_customs/business/vat/oss_de' ) . ( ! \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ? '<div class="wc-gzd-additional-desc">' . __( 'Activating this option will automatically install the <a href="https://wordpress.org/plugins/one-stop-shop-woocommerce/" target="_blank">One Stop Shop Plugin</a> developed by us.', 'woocommerce-germanized' ) . '</div>' : '' ),
						'id'      => 'oss_use_oss_procedure',
						'default' => 'no',
						'type'    => 'gzd_toggle',
					),
					array(
						'title'             => _x( 'OSS observer', 'install', 'woocommerce-germanized' ),
						'desc'              => __( 'Observe the OSS delivery threshold of the current year.', 'woocommerce-germanized' ) . ( ! \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ? '<div class="wc-gzd-additional-desc">' . __( 'Get notified automatically when you are close to reaching the delivery threshold.', 'woocommerce-germanized' ) . ' ' . __( 'Activating this option will automatically install the <a href="https://wordpress.org/plugins/one-stop-shop-woocommerce/" target="_blank">One Stop Shop Plugin</a> developed by us.', 'woocommerce-germanized' ) . '</div>' : '' ),
						'id'                => 'oss_enable_auto_observation',
						'default'           => 'no',
						'type'              => 'gzd_toggle',
						'custom_attributes' => array(
							'data-show_if_oss_use_oss_procedure' => 'no',
						),
					),
					array(
						'title'   => _x( 'VAT', 'install', 'woocommerce-germanized' ),
						'desc'    => __( 'Let Germanized insert EU VAT rates.', 'woocommerce-germanized' ),
						'id'      => 'woocommerce_gzd_vat_rates',
						'default' => 'yes',
						'type'    => 'gzd_toggle',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'germanized_options',
					),
				);
			} elseif ( 'settings' === $step ) {
				$settings = array(
					array(
						'title' => '',
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'setting_options',
					),
					array(
						'title'   => __( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ),
						'desc'    => __( 'VAT based on &#167;19 UStG', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Enable this option if you have chosen to apply to <a href="%s" target="_blank">&#167;19 UStG</a>.', 'woocommerce-germanized' ), esc_url( 'http://www.gesetze-im-internet.de/ustg_1980/__19.html' ) ) . '</div>',
						'id'      => 'woocommerce_gzd_small_enterprise',
						'default' => 'no',
						'type'    => 'gzd_toggle',
					),
					array(
						'title'   => __( 'Double Opt In', 'woocommerce-germanized' ),
						'desc'    => __( 'Enable customer double opt in during registration.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Sends an email to the customer after registration to verify his account. <strong>By default unactivated customers will be deleted after 7 days</strong>. You may adjust your DOI <a href="%s" target="_blank">settings</a> accordingly.', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-double_opt_in' ) ) . '</div>',
						'id'      => 'woocommerce_gzd_customer_activation',
						'default' => 'no',
						'type'    => 'gzd_toggle',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'setting_options',
					),
				);
			} elseif ( 'shipping_provider' === $step ) {
				$providers = apply_filters( 'woocommerce_gzd_shipment_admin_provider_list', wc_gzd_get_shipping_providers() );

				foreach ( $providers as $provider ) {
					if ( $provider->is_manual_integration() ) {
						continue;
					}

					$title_clean = wp_strip_all_tags( preg_replace( '/>.*?</s', '><', $provider->get_title() ) );

					$settings = array_merge(
						$settings,
						array(
							array(
								'title' => '',
								'type'  => 'title',
								'desc'  => '',
								'id'    => 'shipping_provider_' . $provider->get_name(),
							),
						)
					);

					if ( $provider->is_pro() && ! WC_germanized()->is_pro() ) {
						$settings = array_merge(
							$settings,
							array(
								array(
									'title'   => $title_clean,
									'id'      => 'woocommerce_gzd_' . $provider->get_name() . '_activate',
									'default' => wc_bool_to_string( $provider->is_activated() ),
									'type'    => 'html',
									'html'    => '<p><span class="status-disabled" style="display: inline-block; vertical-align: middle; margin-right: 3px;"></span> ' . sprintf( __( 'Upgrade to %1$s to activate %2$s integration.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized#upgrade" class="wc-gzd-pro wc-gzd-pro-outlined" target="_blank" rel="noreferrer">pro</a>', $title_clean ) . '</p>',
								),
							)
						);
					} else {
						$settings = array_merge(
							$settings,
							array(
								array(
									'title'   => $title_clean,
									'desc'    => sprintf( __( 'Enable %s integration', 'woocommerce-germanized' ), $provider->get_title() ),
									'id'      => 'woocommerce_gzd_' . $provider->get_name() . '_activate',
									'default' => wc_bool_to_string( $provider->is_activated() ),
									'type'    => 'gzd_toggle',
								),
							)
						);
					}

					$settings = array_merge(
						$settings,
						array(
							array(
								'type' => 'sectionend',
								'id'   => 'shipping_provider_' . $provider->get_name(),
							),
						)
					);
				}
			}

			return $settings;
		}

		/**
		 * Add admin menus/screens.
		 */
		public function admin_menus() {
			add_submenu_page( '', __( 'Setup', 'woocommerce-germanized' ), __( 'Setup', 'woocommerce-germanized' ), 'manage_options', 'wc-gzd-setup', array( $this, 'none' ) );
		}

		/**
		 * Register/enqueue scripts and styles for the Setup Wizard.
		 *
		 * Hooked onto 'admin_enqueue_scripts'.
		 */
		public function enqueue_scripts() {
			if ( $this->is_setup_wizard() ) {
				$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				$assets_path = WC_germanized()->plugin_url() . '/assets/';

				// Register admin styles.
				wp_register_style( 'woocommerce-gzd-admin-setup-wizard', $assets_path . 'css/admin-wizard' . $suffix . '.css', array( 'wp-admin', 'dashicons', 'install', 'woocommerce-gzd-admin-settings' ), WC_GERMANIZED_VERSION );
				wp_enqueue_style( 'woocommerce-gzd-admin-setup-wizard' );

				wp_register_script( 'wc-gzd-admin-settings', $assets_path . 'js/admin/settings' . $suffix . '.js', array(), WC_GERMANIZED_VERSION, true );
				wp_register_script( 'wc-gzd-admin-setup', $assets_path . 'js/admin/setup' . $suffix . '.js', array( 'jquery', 'wc-gzd-admin-settings', 'jquery-tiptip' ), WC_GERMANIZED_VERSION, true );

				wp_enqueue_script( 'wc-gzd-admin-setup' );
			}
		}

		private function is_setup_wizard() {
			return ( isset( $_GET['page'] ) && 'wc-gzd-setup' === wc_clean( wp_unslash( $_GET['page'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		public function get_error_message( $step = false ) {
			if ( isset( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$error_key    = sanitize_key( wp_unslash( $_GET['error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$current_step = $this->get_step( $step );

				if ( isset( $current_step['errors'][ $error_key ] ) ) {
					return $current_step['errors'][ $error_key ];
				}
			}

			return false;
		}

		/**
		 * Show the setup wizard.
		 */
		public function setup_wizard() {
			if ( ! $this->is_setup_wizard() ) {
				return;
			}

			ob_start();
			$this->header();
			$this->steps();
			$this->content();
			$this->footer();
			exit;
		}

		public function get_step( $key = false ) {
			if ( ! $key ) {
				$key = $this->step;
			}

			return ( isset( $this->steps[ $key ] ) ? $this->steps[ $key ] : false );
		}

		public function get_step_url( $key ) {
			if ( ! $step = $this->get_step( $key ) ) {
				return false;
			}

			return admin_url( 'admin.php?page=wc-gzd-setup&step=' . $key );
		}

		public function get_next_step() {
			$current = $this->get_step();
			$next    = $this->step;

			if ( $current['order'] < count( $this->steps ) ) {
				$order_next = $current['order'] + 1;

				foreach ( $this->steps as $step_key => $step ) {
					if ( $step['order'] === $order_next ) {
						$next = $step_key;
					}
				}
			}

			return $next;
		}

		protected function header() {
			set_current_screen( 'wc-gzd-setup' );
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><?php esc_html_e( 'Germanized &rsaquo; Setup Wizard', 'woocommerce-germanized' ); ?></title>
				<?php do_action( 'admin_enqueue_scripts' ); ?>
				<?php wp_print_scripts( 'wc-gzd-admin-setup-wizard' ); ?>
				<?php do_action( 'admin_print_styles' ); ?>
				<?php do_action( 'admin_print_scripts' ); ?>
				<?php do_action( 'admin_head' ); ?>
			</head>
			<body class="wc-gzd-setup wp-core-ui wc-gzd-setup-step-<?php echo esc_attr( $this->step ); ?>">
				<div class="wc-gzd-setup-header">
					<div class="logo-wrapper"><div class="logo"></div></div>
			<?php
		}

		protected function steps() {
			$output_steps = $this->steps;
			?>
			<ul class="step wc-gzd-steps">
				<?php
				foreach ( $output_steps as $step_key => $step ) {
					?>
					<li class="step-item <?php echo $step_key === $this->step ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( $this->get_step_url( $step_key ) ); ?>"><?php echo esc_html( $step['name'] ); ?></a>
					</li>
					<?php
				}
				?>
			</ul>
			</div>
			<?php
		}

		protected function content() {
			?>
			<form class="wc-gzd-setup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<div class="wc-gzd-setup-content">

				<?php if ( $error_message = $this->get_error_message() ) : ?>
					<div id="message" class="error inline">
						<p><?php echo wp_kses_post( $error_message ); ?></p>
					</div>
				<?php endif; ?>

				<?php
				if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
					if ( file_exists( WC_GERMANIZED_ABSPATH . 'includes/admin/views/setup/' . $this->steps[ $this->step ]['view'] ) ) {

						// Extract the variables to a local namespace
						extract( // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
							array(
								'steps'    => $this->steps,
								'step'     => $this->step,
								'wizard'   => $this,
								'settings' => $this->get_settings( $this->step ),
							)
						);

						include 'views/setup/' . $this->steps[ $this->step ]['view'];
					}
				}

				echo '</div>';
		}

		protected function footer() {
			$current = $this->get_step( $this->step );
			?>
			<div class="wc-gzd-setup-footer">
				<div class="wc-gzd-setup-links">
					<input type="hidden" name="action" value="wc_gzd_setup" />
					<input type="hidden" name="step" value="<?php echo esc_attr( $this->step ); ?>" />

					<?php wp_nonce_field( 'wc-gzd-setup' ); ?>

					<?php if ( $current['order'] < count( $this->steps ) ) : ?>
						<a class="wc-gzd-setup-link wc-gzd-setup-link-skip" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'skip' => esc_attr( $this->step ) ), $this->get_step_url( $this->get_next_step() ) ) ), 'wc-gzd-setup-skip' ); ?>"><?php esc_html_e( 'Skip Step', 'woocommerce-germanized' ); ?></a>
					<?php endif; ?>

					<?php if ( isset( $current['button_next_link'] ) && ! empty( $current['button_next_link'] ) ) : ?>
						<a class="button button-primary wc-gzd-button wc-gzd-setup-link" href="<?php echo esc_url( $current['button_next_link'] ); ?>"><?php echo isset( $current['button_next'] ) ? esc_attr( $current['button_next'] ) : esc_attr__( 'Continue', 'woocommerce-germanized' ); ?></a>
					<?php else : ?>
						<button class="button button-primary wc-gzd-setup-link wc-gzd-button" type="submit"><?php echo isset( $current['button_next'] ) ? esc_attr( $current['button_next'] ) : esc_attr__( 'Continue', 'woocommerce-germanized' ); ?></button>
					<?php endif; ?>

				</div>

				<div class="escape">
					<a href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to WP Admin', 'woocommerce-germanized' ); ?></a>
				</div>
			</div>
			</form>
			<?php do_action( 'admin_footer', '' ); ?>
			<?php do_action( 'admin_print_footer_scripts' ); ?>
			</body>
			</html>
			<?php
		}

		public function save() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'wc-gzd-setup' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				wp_die();
			} elseif ( ! current_user_can( 'manage_options' ) ) {
				wp_die();
			}

			$current_step = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : $this->step;

			if ( ! $step = $this->get_step( $current_step ) ) {
				wp_die();
			}

			call_user_func( $step['handler'] );
		}

		public function wc_gzd_setup_provider_save() {
			$redirect    = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );
			$providers   = wc_gzd_get_shipping_providers();

			foreach ( $providers as $provider ) {
				if ( isset( $_POST[ "woocommerce_gzd_{$provider->get_name()}_activate" ] ) && 'yes' === wc_bool_to_string( wc_clean( wp_unslash( $_POST[ "woocommerce_gzd_{$provider->get_name()}_activate" ] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$provider->activate();
					update_option( '_wc_gzd_setup_shipping_provider_activated', 'yes' );
				}
			}

			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit();
		}

		public function wc_gzd_setup_encrypt_save() {
			$redirect    = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );

			if ( WC_GZD_Secret_Box_Helper::supports_auto_insert() && ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) {
				$result   = WC_GZD_Secret_Box_Helper::maybe_insert_missing_key();
				$redirect = add_query_arg( array( 'encrypt-success' => wc_bool_to_string( $result ) ), $redirect );
			}

			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit();
		}

		public function wc_gzd_setup_germanize_save() {
			$redirect    = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );

			$enable_oss          = isset( $_POST['oss_use_oss_procedure'] ) && ! empty( $_POST['oss_use_oss_procedure'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$enable_oss_observer = isset( $_POST['oss_enable_auto_observation'] ) && ! empty( $_POST['oss_enable_auto_observation'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() && current_user_can( 'install_plugins' ) ) {
				if ( $enable_oss || $enable_oss_observer ) {
					$result = \Vendidero\Germanized\PluginsHelper::install_or_activate_oss();

					if ( ! \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ) {
						wp_safe_redirect( esc_url_raw( add_query_arg( array( 'error' => 'oss_install' ), $current_url ) ) );
						exit();
					}
				}
			}

			if ( $enable_oss ) {
				update_option( 'oss_use_oss_procedure', 'yes' );
			}

			if ( $enable_oss_observer ) {
				update_option( 'oss_enable_auto_observation', 'yes' );
			}

			if ( isset( $_POST['woocommerce_gzd_germanize_settings'] ) && ! empty( $_POST['woocommerce_gzd_germanize_settings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				WC_GZD_Install::set_default_settings();
			}

			if ( isset( $_POST['woocommerce_gzd_create_legal_pages'] ) && ! empty( $_POST['woocommerce_gzd_create_legal_pages'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				WC_GZD_Install::create_pages();
			}

			if ( isset( $_POST['woocommerce_gzd_vat_rates'] ) && ! empty( $_POST['woocommerce_gzd_vat_rates'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				WC_GZD_Install::create_tax_rates();
			}

			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit();
		}

		public function wc_gzd_setup_settings_save() {
			$redirect    = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );
			$settings    = $this->get_settings( $this->step );

			if ( ! empty( $settings ) ) {
				WC_Admin_Settings::save_fields( $settings );
			}

			if ( isset( $_POST['woocommerce_gzd_small_enterprise'] ) && ! empty( $_POST['woocommerce_gzd_small_enterprise'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				WC_GZD_Admin::instance()->enable_small_business_options();
			} else {
				WC_GZD_Admin::instance()->disable_small_business_options();
			}

			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit();
		}
	}

endif;

return new WC_GZD_Admin_Setup_Wizard();
