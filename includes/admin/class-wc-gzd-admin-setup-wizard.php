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
				'germanize' => array(
					'name'      => __( 'Germanize', 'woocommerce-germanized' ),
					'view'      => 'germanize.php',
					'handler'   => array( $this, 'wc_gzd_setup_germanize_save' ),
					'errors'    => array(),
					'order'     => 1,
				),
				'settings' 	  => array(
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
				'first_steps' 	       => array(
					'name'             => __( 'First Steps', 'woocommerce-germanized' ),
					'view'             => 'first-steps.php',
					'order'            => 10,
					'errors'  	       => array(),
					'button_next'      => __( 'Start tutorial', 'woocommerce-germanized' ),
					'button_next_link' => admin_url( 'admin.php?page=wc-settings&tab=germanized&tutorial=yes' ),
				),
			);

			if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
			    $new_key = WC_GZD_Secret_Box_Helper::get_random_encryption_key();

			    if ( ! is_wp_error( $new_key ) ) {
			         $default_steps['encrypt'] = array(
                        'name'    => __( 'Encryption', 'woocommerce-germanized' ),
                        'view'    => 'encrypt.php',
                        'handler' => array( $this, 'wc_gzd_setup_encrypt_save' ),
                        'order'   => 3,
                        'errors'  => array(),
                        'button_next' => ( ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() && WC_GZD_Secret_Box_Helper::supports_auto_insert() ) ? esc_attr__( 'Insert key', 'woocommerce-germanized' ) : esc_attr__( 'Continue', 'woocommerce-germanized' ),
                    );
			    }
			}

			$this->steps   = $default_steps;
			uasort( $this->steps, array( $this, '_uasort_callback' ) );

			$order = 0;

			foreach( $this->steps as $key => $step ) {
			    $this->steps[ $key ]['order'] = ++$order;
			}

			$this->step = isset( $_REQUEST['step'] ) ? sanitize_key( $_REQUEST['step'] ) : current( array_keys( $this->steps ) ); // WPCS: CSRF ok, input var ok.

			// Check if a step has been skipped and maybe delete som tmp options
			if ( isset( $_GET['skip'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wc-gzd-setup-skip' ) ) {
				$skipped_step = sanitize_key( $_GET['skip'] );
			}
		}

		public function _uasort_callback( $step1, $step2 ) {
			if ( $step1['order'] == $step2['order'] ) {return 0;}
			return ( $step1['order'] < $step2['order'] ) ? -1 : 1;
		}

		protected function get_settings( $step = '' ) {
			$settings = array();

			if ( 'germanize' === $step ) {
				$pages = wc_get_page_id( 'revocation' ) < 1 ? array(
					'title' 	=> __( 'Pages', 'woocommerce-germanized' ),
					'desc' 		=> __( 'Create legal pages placeholders e.g. terms & conditions.', 'woocommerce-germanized' ),
					'id' 		=> 'woocommerce_gzd_create_legal_pages',
					'default'	=> 'yes',
					'type' 		=> 'gzd_toggle',
				) : array();

				$settings = array(
					array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'germanized_options' ),
					array(
						'title' 	=> __( 'Settings', 'woocommerce-germanized' ),
						'desc' 		=> __( 'Germanize WooCommerce settings (e.g. currency, tax display).', 'woocommerce-germanized' ),
						'id' 		=> 'woocommerce_gzd_germanize_settings',
						'default'	=> 'yes',
						'type' 		=> 'gzd_toggle',
					),
					$pages,
					array(
						'title' 	=> _x( 'OSS status', 'install', 'woocommerce-germanized' ),
						'desc' 		=> sprintf( __( 'I\'m participating in the <a href="%s" target="_blank" rel="noopener">One Stop Shop procedure</a>.', 'woocommerce-germanized' ), 'https://ec.europa.eu/taxation_customs/business/vat/oss_de' ),
						'id' 		=> 'oss_use_oss_procedure',
						'default'	=> 'no',
						'type' 		=> 'gzd_toggle',
					),
					array(
						'title' 	=> _x( 'VAT', 'install', 'woocommerce-germanized' ),
						'desc' 		=> __( 'Let Germanized insert EU VAT rates.', 'woocommerce-germanized' ),
						'id' 		=> 'woocommerce_gzd_vat_rates',
						'default'	=> 'yes',
						'type' 		=> 'gzd_toggle',
					),
					array( 'type' => 'sectionend', 'id' => 'germanized_options' ),
				);
			} elseif( 'settings' === $step ) {
				$settings = array(
					array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'setting_options' ),
					array(
						'title' 	=> __( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ),
						'desc' 		=> __( 'VAT based on &#167;19 UStG', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Enable this option if you have chosen to apply to <a href="%s" target="_blank">&#167;19 UStG</a>.', 'woocommerce-germanized' ), esc_url( 'http://www.gesetze-im-internet.de/ustg_1980/__19.html' ) ) . '</div>',
						'id' 		=> 'woocommerce_gzd_small_enterprise',
						'default'	=> 'no',
						'type' 		=> 'gzd_toggle',
					),
					array(
						'title' 	=> __( 'Double Opt In', 'woocommerce-germanized' ),
						'desc' 		=> __( 'Enable customer double opt in during registration.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Sends an email to the customer after registration to verify his account. <strong>By default unactivated customers will be deleted after 7 days</strong>. You may adjust your DOI <a href="%s" target="_blank">settings</a> accordingly.', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-double_opt_in' ) ) . '</div>',
						'id' 		=> 'woocommerce_gzd_customer_activation',
						'default'	=> 'no',
						'type' 		=> 'gzd_toggle',
					),
					array( 'type' => 'sectionend', 'id' => 'setting_options' ),
				);
			} elseif( 'shipping_provider' === $step ) {
			    foreach( wc_gzd_get_shipping_providers() as $provider ) {
			        if ( $provider->is_manual_integration() ) {
			            continue;
			        }

			        $settings = array_merge( $settings, array(
                        array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'shipping_provider_' . $provider->get_name() ),
                        array(
                            'title' 	=> $provider->get_title(),
                            'desc' 		=> sprintf( __( 'Enable %s integration', 'woocommerce-germanized' ), $provider->get_title() ),
                            'id' 		=> 'woocommerce_gzd_' . $provider->get_name() . '_activate',
                            'default'	=> wc_bool_to_string( $provider->is_activated() ),
                            'type' 		=> 'gzd_toggle',
                        ),
                        array( 'type' => 'sectionend', 'id' => 'shipping_provider_' . $provider->get_name() ),
			        ) );
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
			return ( isset( $_GET['page'] ) && 'wc-gzd-setup' === $_GET['page'] );
		}

		public function get_error_message( $step = false ) {
			if ( isset( $_GET['error'] ) ) {
				$error_key 	  = sanitize_key( $_GET['error'] );
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

			return admin_url( 'admin.php?page=wc-gzd-setup&step='  . $key );
		}

		public function get_next_step() {
			$current = $this->get_step();
			$next    = $this->step;

			if ( $current['order'] < sizeof( $this->steps ) ) {
				$order_next = $current['order'] + 1;

				foreach( $this->steps as $step_key => $step ) {
					if ( $step['order'] === $order_next ) {
						$next = $step_key;
					}
				}
			}

			return $next;
		}

		protected function header() {
			set_current_screen();
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
			$output_steps      = $this->steps;
			?>
			<ul class="step wc-gzd-steps">
				<?php
				foreach ( $output_steps as $step_key => $step ) {
					?>
					<li class="step-item <?php echo $step_key === $this->step ? 'active' : ''; ?>">
						<a href="<?php echo $this->get_step_url( $step_key ) ?>"><?php echo esc_html( $step['name'] ); ?></a>
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
			<form class="wc-gzd-setup-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<div class="wc-gzd-setup-content">

				<?php if ( $error_message = $this->get_error_message() ) : ?>
					<div id="message" class="error inline">
						<p><?php echo $error_message; ?></p>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
					if ( file_exists( WC_GERMANIZED_ABSPATH . 'includes/admin/views/setup/' . $this->steps[ $this->step ]['view'] ) ) {

						// Extract the variables to a local namespace
						extract( array(
							'steps'    => $this->steps,
							'step'     => $this->step,
							'wizard'   => $this,
							'settings' => $this->get_settings( $this->step ),
						) );

						include( 'views/setup/' . $this->steps[ $this->step ]['view'] );
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

					<?php if ( $current['order'] < sizeof( $this->steps ) ) : ?>
						<a class="wc-gzd-setup-link wc-gzd-setup-link-skip" href="<?php echo wp_nonce_url( add_query_arg( array( 'skip' => esc_attr( $this->step ) ), $this->get_step_url( $this->get_next_step() ) ), 'wc-gzd-setup-skip' ); ?>"><?php esc_html_e( 'Skip Step', 'woocommerce-germanized' ); ?></a>
					<?php endif; ?>

					<?php if ( isset( $current['button_next_link'] ) && ! empty( $current['button_next_link'] ) ) : ?>
						<a class="button button-primary wc-gzd-setup-link" href="<?php echo esc_url( $current['button_next_link'] ); ?>"><?php echo isset( $current['button_next'] ) ? esc_attr( $current['button_next'] ) : esc_attr__( 'Continue', 'woocommerce-germanized' ); ?></a>
					<?php else: ?>
						<button class="button button-primary wc-gzd-setup-link" type="submit"><?php echo isset( $current['button_next'] ) ? esc_attr( $current['button_next'] ) : esc_attr__( 'Continue', 'woocommerce-germanized' ); ?></button>
					<?php endif; ?>

				</div>

				<div class="escape">
					<a href="<?php echo admin_url(); ?>"><?php _e( 'Return to WP Admin', 'woocommerce-germanized' ); ?></a>
				</div>
			</div>
			</form>
			<?php do_action( 'admin_footer', '' ); ?>
			<?php do_action( 'admin_print_footer_scripts' ); ?>
			</body>
			</html>
			<?php
		}

		/**
		 * Get slug from path and associate it with the path.
		 *
		 * @param array  $plugins Associative array of plugin files to paths.
		 * @param string $key Plugin relative path. Example: woocommerce/woocommerce.php.
		 */
		private function associate_plugin_file( $plugins, $key ) {
			$path                 = explode( '/', $key );
			$filename             = end( $path );
			$plugins[ $filename ] = $key;
			return $plugins;
		}

		private function install_plugin( $plugin_to_install_id, $plugin_to_install ) {

			if ( ! empty( $plugin_to_install['repo-slug'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				require_once ABSPATH . 'wp-admin/includes/plugin.php';

				WP_Filesystem();

				$skin              = new Automatic_Upgrader_Skin();
				$upgrader          = new WP_Upgrader( $skin );
				$installed_plugins = array_reduce( array_keys( get_plugins() ), array( $this, 'associate_plugin_file' ), array() );
				$plugin_slug       = $plugin_to_install['repo-slug'];
				$plugin_file       = isset( $plugin_to_install['file'] ) ? $plugin_to_install['file'] : $plugin_slug . '.php';
				$installed         = false;
				$activate          = false;

				// See if the plugin is installed already.
				if ( isset( $installed_plugins[ $plugin_file ] ) ) {
					$installed = true;
					$activate  = ! is_plugin_active( $installed_plugins[ $plugin_file ] );
				}

				// Install this thing!
				if ( ! $installed ) {
					// Suppress feedback.
					ob_start();

					try {
						$plugin_information = plugins_api(
							'plugin_information',
							array(
								'slug'   => $plugin_slug,
								'fields' => array(
									'short_description' => false,
									'sections'          => false,
									'requires'          => false,
									'rating'            => false,
									'ratings'           => false,
									'downloaded'        => false,
									'last_updated'      => false,
									'added'             => false,
									'tags'              => false,
									'homepage'          => false,
									'donate_link'       => false,
									'author_profile'    => false,
									'author'            => false,
								),
							)
						);

						if ( is_wp_error( $plugin_information ) ) {
							throw new Exception( $plugin_information->get_error_message() );
						}

						$package  = $plugin_information->download_link;
						$download = $upgrader->download_package( $package );

						if ( is_wp_error( $download ) ) {
							throw new Exception( $download->get_error_message() );
						}

						$working_dir = $upgrader->unpack_package( $download, true );

						if ( is_wp_error( $working_dir ) ) {
							throw new Exception( $working_dir->get_error_message() );
						}

						$result = $upgrader->install_package(
							array(
								'source'                      => $working_dir,
								'destination'                 => WP_PLUGIN_DIR,
								'clear_destination'           => false,
								'abort_if_destination_exists' => false,
								'clear_working'               => true,
								'hook_extra'                  => array(
									'type'   => 'plugin',
									'action' => 'install',
								),
							)
						);

						if ( is_wp_error( $result ) ) {
							throw new Exception( $result->get_error_message() );
						}

						$activate = true;

					} catch ( Exception $e ) {
						return false;
					}

					// Discard feedback.
					ob_end_clean();
				}

				wp_clean_plugins_cache();

				// Activate this thing.
				if ( $activate ) {
					try {
						$result = activate_plugin( $installed ? $installed_plugins[ $plugin_file ] : $plugin_slug . '/' . $plugin_file );

						if ( is_wp_error( $result ) ) {
							throw new Exception( $result->get_error_message() );
						}
					} catch ( Exception $e ) {
						return false;
					}
				}
			}

			return true;
		}

		public function save() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wc-gzd-setup' ) ) {
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
		    $redirect 	 = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );
			$providers   = wc_gzd_get_shipping_providers();

			foreach( $providers as $provider ) {
			    if ( isset( $_POST["woocommerce_gzd_{$provider->get_name()}_activate"] ) && 'yes' === wc_bool_to_string( $_POST["woocommerce_gzd_{$provider->get_name()}_activate"] ) ) {
			        $provider->activate();
			        update_option( '_wc_gzd_setup_shipping_provider_activated', 'yes' );
			    }
			}

			wp_safe_redirect( $redirect );
			exit();
		}

		public function wc_gzd_setup_encrypt_save() {
		    $redirect 	 = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );

			if ( WC_GZD_Secret_Box_Helper::supports_auto_insert() && ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) {
			    $result   = WC_GZD_Secret_Box_Helper::maybe_insert_missing_key();
			    $redirect = add_query_arg( array( 'encrypt-success' => wc_bool_to_string( $result ) ), $redirect );
			}

			wp_safe_redirect( $redirect );
			exit();
		}

		public function wc_gzd_setup_germanize_save() {
			$redirect 	 = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );

			if ( isset( $_POST['oss_use_oss_procedure'] ) && ! empty( $_POST['oss_use_oss_procedure'] ) ) {
			    update_option( 'oss_use_oss_procedure', 'yes' );
			}

			if ( isset( $_POST['woocommerce_gzd_germanize_settings'] ) && ! empty( $_POST['woocommerce_gzd_germanize_settings'] ) ) {
			    WC_GZD_Install::set_default_settings();
			}

			if ( isset( $_POST['woocommerce_gzd_create_legal_pages'] ) && ! empty( $_POST['woocommerce_gzd_create_legal_pages'] ) ) {
			    WC_GZD_Install::create_pages();
			}

			if ( isset( $_POST['woocommerce_gzd_vat_rates'] ) && ! empty( $_POST['woocommerce_gzd_vat_rates'] ) ) {
			    WC_GZD_Install::create_tax_rates();
			}

			wp_safe_redirect( $redirect );
			exit();
		}

		public function wc_gzd_setup_settings_save() {
			$redirect 	 = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );
			$settings    = $this->get_settings( $this->step );

			if ( ! empty( $settings ) ) {
			    WC_Admin_Settings::save_fields( $settings );
			}

			if ( isset( $_POST['woocommerce_gzd_small_enterprise'] ) && ! empty( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
			    WC_GZD_Admin::instance()->enable_small_business_options();
			} else {
			    WC_GZD_Admin::instance()->disable_small_business_options();
			}

			wp_safe_redirect( $redirect );
			exit();
		}
	}

endif;

return new WC_GZD_Admin_Setup_Wizard();
