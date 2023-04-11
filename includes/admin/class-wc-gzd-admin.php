<?php

use Vendidero\Germanized\DHL\Admin\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_GZD_Admin {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	protected $wizard = null;

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
		add_action( 'add_meta_boxes', array( $this, 'add_legal_page_metabox' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'register_product_meta_boxes' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'save_post', array( $this, 'save_legal_page_content' ), 10, 3 );

		add_filter( 'woocommerce_admin_status_tabs', array( $this, 'set_gzd_status_tab' ) );
		add_action( 'woocommerce_admin_status_content_germanized', array( $this, 'status_tab' ) );

		add_action( 'admin_init', array( $this, 'tool_actions' ) );
		add_action( 'admin_init', array( $this, 'check_resend_activation_email' ) );
		add_action( 'admin_init', array( $this, 'check_dhl_import' ) );
		add_action( 'admin_init', array( $this, 'check_internetmarke_import' ) );

		add_filter( 'woocommerce_addons_section_data', array( $this, 'set_addon' ), 10, 2 );
		add_action(
			'woocommerce_admin_order_data_after_shipping_address',
			array(
				$this,
				'show_checkbox_status',
			),
			10,
			1
		);

		add_filter( 'woocommerce_order_actions', array( $this, 'order_actions' ), 10, 1 );
		add_action( 'woocommerce_order_action_order_confirmation', array( $this, 'resend_order_confirmation' ), 10, 1 );
		add_action(
			'woocommerce_order_action_paid_for_order_notification',
			array(
				$this,
				'send_paid_for_order_notification',
			),
			10,
			1
		);

		add_filter(
			'pre_update_option_wp_page_for_privacy_policy',
			array(
				$this,
				'pre_update_wp_privacy_option_page',
			),
			10,
			2
		);
		add_filter(
			'pre_update_option_woocommerce_data_security_page_id',
			array(
				$this,
				'pre_update_gzd_privacy_option_page',
			),
			10,
			2
		);

		add_action( 'woocommerce_admin_field_gzd_toggle', array( $this, 'toggle_input_field' ), 5 );
		add_action( 'woocommerce_admin_field_gzd_select_term', array( $this, 'term_field' ), 5 );
		add_action( 'woocommerce_admin_field_image', array( $this, 'image_field' ), 10, 1 );
		add_action( 'woocommerce_admin_field_html', array( $this, 'html_field' ), 10, 1 );
		add_action( 'woocommerce_admin_field_hidden', array( $this, 'hidden_field' ), 10, 1 );
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'save_fields' ), 0, 3 );

		add_action( 'woocommerce_oss_enabled_oss_procedure', array( $this, 'oss_enable_hide_tax_percentage' ), 10 );

		add_filter( 'woocommerce_gzd_shipment_admin_provider_list', array( $this, 'maybe_register_shipping_providers' ), 10 );

		$this->wizward = require 'class-wc-gzd-admin-setup-wizard.php';
	}

	public function tool_actions() {
		$actions = array(
			'language_install',
			'text_options_deletion',
			'complaints_shortcode_append',
			'insert_vat_rates',
			'disable_notices',
			'encryption_key_insert',
			'enable_debug_mode',
			'disable_food_options',
			'install_oss',
			'install_ts',
		);

		if ( current_user_can( 'manage_woocommerce' ) ) {
			foreach ( $actions as $action ) {
				$nonce_action = "wc-gzd-check-{$action}";

				/**
				 * Legacy notice support
				 */
				if ( 'encryption_key_insert' === $action && isset( $_GET['insert-encryption-key'] ) ) {
					$nonce_action                     = 'wc-gzd-insert-encryption-key';
					$_GET[ "wc-gzd-check-{$action}" ] = true;
				}

				if ( isset( $_GET[ "wc-gzd-check-{$action}" ] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( $nonce_action ) ) {
					$method = "check_{$action}";

					if ( is_callable( array( $this, $method ) ) ) {
						$this->$method();

						wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-status&tab=germanized' ) ) );
						exit();
					}
				}
			}
		}
	}

	protected function check_install_oss() {
		if ( current_user_can( 'install_plugins' ) ) {
			\Vendidero\Germanized\PluginsHelper::install_or_activate_oss();

			if ( \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ) {
				wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-settings&tab=germanized-oss' ) ) );
				exit();
			}
		}

		wp_safe_redirect( esc_url_raw( admin_url( 'plugin-install.php?s=one+stop+shop+woocommerce&tab=search&type=term' ) ) );
		exit();
	}

	protected function check_install_ts() {
		if ( current_user_can( 'install_plugins' ) ) {
			\Vendidero\Germanized\PluginsHelper::install_or_activate_trusted_shops();

			if ( \Vendidero\Germanized\PluginsHelper::is_trusted_shops_plugin_active() ) {
				wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-settings&tab=germanized-trusted_shops_easy_integration' ) ) );
				exit();
			}
		}

		wp_safe_redirect( esc_url_raw( admin_url( 'plugin-install.php?s=trusted+shops+easy+integration+for+woocommerce&tab=search&type=term' ) ) );
		exit();
	}

	protected function check_enable_debug_mode() {
		if ( 'yes' === get_option( 'woocommerce_gzd_extended_debug_mode' ) ) {
			update_option( 'woocommerce_gzd_extended_debug_mode', 'no' );
		} else {
			update_option( 'woocommerce_gzd_extended_debug_mode', 'yes' );
		}
	}

	protected function check_disable_food_options() {
		if ( 'yes' === get_option( 'woocommerce_gzd_disable_food_options' ) ) {
			update_option( 'woocommerce_gzd_disable_food_options', 'no' );
		} else {
			update_option( 'woocommerce_gzd_disable_food_options', 'yes' );
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Interfaces\ShippingProvider $providers
	 */
	public function maybe_register_shipping_providers( $providers ) {
		if ( ! WC_germanized()->is_pro() ) {
			if ( $this->is_dpd_available() ) {
				$dpd               = new WC_GZD_Admin_Provider_DPD();
				$providers['_dpd'] = $dpd;
			}

			if ( $this->is_gls_available() ) {
				$gls               = new WC_GZD_Admin_Provider_GLS();
				$providers['_gls'] = $gls;
			}
		}

		return $providers;
	}

	public function is_gls_available() {
		return in_array( \Vendidero\Germanized\Shipments\Package::get_base_country(), array( 'DE', 'AT', 'CH', 'BE', 'LU', 'FR', 'IE', 'ES' ), true );
	}

	public function is_dpd_available() {
		return in_array( \Vendidero\Germanized\Shipments\Package::get_base_country(), array( 'DE', 'AT' ), true );
	}

	public function oss_enable_hide_tax_percentage() {
		update_option( 'woocommerce_gzd_hide_tax_rate_shop', 'yes' );
	}

	public function check_dhl_import() {
		if ( ! class_exists( '\Vendidero\Germanized\DHL\Admin\Importer\DHL' ) ) {
			return;
		}

		if ( isset( $_GET['wc-gzd-dhl-import'] ) && isset( $_GET['_wpnonce'] ) && Importer\DHL::is_plugin_enabled() ) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'woocommerce_gzd_dhl_import_nonce' ) ) {
				wp_die( esc_html_x( 'Action failed. Please refresh the page and retry.', 'dhl', 'woocommerce-germanized' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html_x( 'You don\'t have permission to do this.', 'dhl', 'woocommerce-germanized' ) );
			}

			if ( Importer\DHL::is_available() ) {
				$this->import_dhl_settings();
			}

			if ( $shipping_provider = Vendidero\Germanized\Shipments\ShippingProvider\Helper::instance()->get_shipping_provider( 'dhl' ) ) {
				$shipping_provider->activate();
			}

			deactivate_plugins( 'dhl-for-woocommerce/pr-dhl-woocommerce.php' );

			wp_safe_redirect( esc_url_raw( add_query_arg( array( 'has-imported' => 'yes' ), wc_gzd_get_shipping_provider( 'dhl' )->get_edit_link() ) ) );
		}
	}

	public function import_dhl_settings() {
		Importer\DHL::import_order_data( 50 );
		Importer\DHL::import_settings();

		update_option( 'woocommerc_gzd_dhl_import_finished', 'yes' );
	}

	public function check_internetmarke_import() {
		if ( ! class_exists( '\Vendidero\Germanized\DHL\Admin\Importer\Internetmarke' ) ) {
			return;
		}

		if ( isset( $_GET['wc-gzd-internetmarke-import'] ) && isset( $_GET['_wpnonce'] ) && Importer\Internetmarke::is_available() ) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'woocommerce_gzd_internetmarke_import_nonce' ) ) {
				wp_die( esc_html_x( 'Action failed. Please refresh the page and retry.', 'dhl', 'woocommerce-germanized' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html_x( 'You don\'t have permission to do this.', 'dhl', 'woocommerce-germanized' ) );
			}

			$this->import_internetmarke_settings();

			wp_safe_redirect( esc_url_raw( wc_gzd_get_shipping_provider( 'deutsche_post' )->get_edit_link() ) );
		}
	}

	public function import_internetmarke_settings() {
		Importer\DHL::import_settings();

		deactivate_plugins( 'woo-dp-internetmarke/woo-dp-internetmarke.php' );

		update_option( 'woocommerce_gzd_dhl_internetmarke_enable', 'yes' );
		update_option( 'woocommerce_gzd_internetmarke_import_finished', 'yes' );
	}

	public function save_fields( $value, $option, $raw_value ) {
		$option = wp_parse_args(
			$option,
			array(
				'type' => '',
			)
		);

		if ( 'gzd_toggle' === $option['type'] ) {
			$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
		} elseif ( 'gzd_select_term' === $option['type'] ) {
			$option = wp_parse_args(
				$option,
				array(
					'multiple' => false,
				)
			);

			if ( $option['multiple'] ) {
				$value = is_array( $value ) ? $value : array( $value );
				$value = array_map( 'absint', $value );
				$value = array_filter( $value );
			} else {
				if ( is_array( $value ) ) {
					$value = $value[0];
				}

				$value = absint( $value );
			}
		} elseif ( isset( $option['class'] ) && strstr( $option['class'], 'wc_input_decimal' ) ) {
			/**
			 * Woo does not support a decimal field in admin settings by default. In case we do find wc_input_decimal
			 * as a input class, make sure to format the decimal accordingly while saving.
			 */
			$value = ( '' === $raw_value ) ? '' : wc_format_decimal( trim( stripslashes( $raw_value ) ) );
		}

		return $value;
	}

	public function save_decimal_field( $value, $option, $raw_value ) {
		if ( isset( $option['class'] ) && strstr( $option['class'], 'wc_input_decimal' ) ) {
			$value = ( '' === $raw_value ) ? '' : wc_format_decimal( trim( stripslashes( $raw_value ) ) );
		}

		return $value;
	}

	public function save_toggle_input_field( $value, $option, $raw_value ) {
		if ( 'gzd_toggle' === $option['type'] ) {
			$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
		}

		return $value;
	}

	public function image_field( $value ) {
		?>
		<tr valign="top">
			<th class="forminp forminp-image" colspan="2" id="<?php echo esc_attr( $value['id'] ); ?>">
				<a href="<?php echo esc_url( $value['href'] ); ?>" target="_blank"><img src="<?php echo esc_url( $value['img'] ); ?>"/></a>
			</th>
		</tr>
		<?php
	}

	public function html_field( $value ) {
		$value = wp_parse_args(
			$value,
			array(
				'id'                => '',
				'custom_attributes' => array(),
			)
		);

		?>
		<tr valign="top">
			<th class="forminp forminp-html" id="<?php echo esc_attr( $value['id'] ); ?>">
				<label><?php echo esc_attr( $value['title'] ); ?><?php echo( isset( $value['desc_tip'] ) && ! empty( $value['desc_tip'] ) ? wc_help_tip( $value['desc_tip'] ) : '' ); ?></label>
			</th>
			<td class="forminp">
				<?php echo wp_kses_post( $value['html'] ); ?>
				<input
					type="hidden"
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					<?php
					if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
						foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
							echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '" ';
						}
					}
					?>
				/>
			</td>
		</tr>
		<?php
	}

	public function hidden_field( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		?>
		<tr valign="top" style="display: none">
			<th class="forminp forminp-image">
				<input type="hidden" id="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>"/>
			</th>
		</tr>
		<?php
	}

	/**
	 * Show a notice highlighting bad template files.
	 */
	public function get_template_version_check_result() {
		/**
		 * Filter to include certain packages or plugins while checking for outdated templates.
		 *
		 * @param array $template_data Template data in key => value pairs.
		 *
		 * @since 3.0.0
		 *
		 */
		$template_data = apply_filters(
			'woocommerce_gzd_template_check',
			array(
				'germanized' => array(
					'title'             => __( 'Germanized for WooCommerce', 'woocommerce-germanized' ),
					'path'              => array( WC_germanized()->plugin_path() . '/templates' ),
					'template_path'     => WC_germanized()->template_path(),
					'outdated_help_url' => 'https://vendidero.de/dokument/veraltete-germanized-templates-aktualisieren',
					'files'             => array(),
					'has_outdated'      => false,
				),
			)
		);

		foreach ( $template_data as $plugin => $path_data ) {
			$path_data = wp_parse_args(
				$path_data,
				array(
					'title'             => '',
					'path'              => '',
					'template_path'     => '',
					'outdated_help_url' => 'https://vendidero.de/dokument/veraltete-germanized-templates-aktualisieren',
					'files'             => array(),
					'has_outdated'      => false,
				)
			);

			if ( ! is_array( $path_data['path'] ) ) {
				$path_data['path'] = array( $path_data['path'] );
			}

			$template_data[ $plugin ] = $path_data;
			$core_templates           = array();

			foreach ( $path_data['path'] as $path ) {
				$core_templates[ $path ] = WC_Admin_Status::scan_template_files( $path );
			}

			$template_path = $path_data['template_path'];

			foreach ( $core_templates as $core_path => $files ) {
				foreach ( $files as $file ) {
					if ( '.DS_Store' === $file ) {
						continue;
					}

					$theme_file = false;

					if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/' . $file;
					} elseif ( file_exists( get_stylesheet_directory() . '/' . $template_path . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/' . $template_path . $file;
					} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
						$theme_file = get_template_directory() . '/' . $file;
					} elseif ( file_exists( get_template_directory() . '/' . $template_path . $file ) ) {
						$theme_file = get_template_directory() . '/' . $template_path . $file;
					}

					if ( false !== $theme_file ) {
						$core_version  = WC_Admin_Status::get_file_version( trailingslashit( $core_path ) . $file );
						$theme_version = WC_Admin_Status::get_file_version( $theme_file );

						if ( ! $theme_version ) {
							$theme_version = '1.0';
						}

						$file_data = array(
							'core_file'     => trailingslashit( $core_path ) . $file,
							'template'      => $file,
							'theme_file'    => $theme_file,
							'theme_version' => $theme_version,
							'core_version'  => $core_version,
							'outdated'      => false,
						);

						if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
							$file_data['outdated']                    = true;
							$template_data[ $plugin ]['has_outdated'] = true;
						}

						$template_data[ $plugin ]['files'][] = $file_data;
					}
				}
			}
		}

		return $template_data;
	}

	public function toggle_input_field( $value ) {
		// Description handling.
		$field_description_data = WC_Admin_Settings::get_field_description( $value );

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$option_value = $value['value'];

		if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
			?>
			<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="wc-gzd-label-wrap"><?php echo esc_html( $value['title'] ); ?><?php echo wp_kses_post( $field_description_data['tooltip_html'] ); ?></span>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
			<fieldset>
			<?php
		} else {
			?>
			<fieldset>
			<?php
		}
		?>
		<a href="#" class="woocommerce-gzd-input-toggle-trigger">
			<span id="<?php echo esc_attr( $value['id'] ); ?>-toggle" class="woocommerce-gzd-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( 'yes' === $option_value ? 'enabled' : 'disabled' ); ?>"><?php echo ( ( 'yes' === $option_value ) ? esc_html__( 'Yes', 'woocommerce-germanized' ) : esc_html__( 'No', 'woocommerce-germanized' ) ); ?></span>
		</a>
		<input
		name="<?php echo esc_attr( $value['id'] ); ?>"
		id="<?php echo esc_attr( $value['id'] ); ?>"
		type="checkbox"
		style="display: none; <?php echo esc_attr( $value['css'] ); ?>"
		value="1"
		class="<?php echo esc_attr( $value['class'] ); ?>"
		<?php checked( $option_value, 'yes' ); ?>
		<?php
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '" ';
			}
		}
		?>
		/><?php echo esc_html( $value['suffix'] ); ?><?php echo wp_kses_post( $field_description_data['description'] ); ?>

		</fieldset>
		<?php
		if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
			?>
			</td>
			</tr>
			<?php
		}
	}

	public function term_field( $value ) {
		$value = wp_parse_args(
			$value,
			array(
				'multiple' => false,
				'taxonomy' => 'product_category',
			)
		);

		// Description handling.
		$field_description_data = WC_Admin_Settings::get_field_description( $value );

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$option_value = $value['value'];
		$placeholder  = __( 'Search for a term&hellip;', 'woocommerce-germanized' );

		if ( $taxonomy = get_taxonomy( $value['taxonomy'] ) ) {
			$labels      = get_taxonomy_labels( $taxonomy );
			$placeholder = isset( $labels->search_items ) ? $labels->search_items . '&hellip;' : $placeholder;
		}

		if ( ! is_array( $option_value ) ) {
			$option_value = array_filter( array( $option_value ) );
		}

		$options = array();

		foreach ( $option_value as $term_id ) {
			$term = get_term_by( 'id', $term_id, $value['taxonomy'] );

			if ( $term && ! is_wp_error( $term ) ) {
				$options[ $term_id ] = $term->name;
			}
		}
		?>
		<tr valign="top" class="gzd_<?php echo ( $value['multiple'] ? 'multi_' : 'single_' ); ?>select_term gzd_select_term">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $field_description_data['tooltip_html'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<select
						name="<?php echo esc_attr( $value['field_name'] ); ?><?php echo ( $value['multiple'] ? '[]' : '' ); ?>"
						<?php echo ( $value['multiple'] ? 'multiple="multiple"' : '' ); ?>
						id="<?php echo esc_attr( $value['id'] ); ?>"
						style="<?php echo esc_attr( $value['css'] ); ?>"
						class="gzd-<?php echo ( $value['multiple'] ? 'multi-' : 'single-' ); ?>select-term gzd-select-term <?php echo esc_attr( $value['class'] ); ?>"
						<?php
						if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
							foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
								echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '" ';
							}
						}
						?>
						data-placeholder="<?php echo esc_attr( $placeholder ); ?>"
						data-allow_clear="true"
						data-taxonomy="<?php echo esc_attr( $value['taxonomy'] ); ?>"
				>
					<option value=""></option>
					<?php foreach ( $options as $term_id => $display_name ) : ?>
						<option value="<?php echo esc_attr( $term_id ); ?>" selected="selected">
							<?php echo wp_strip_all_tags( $display_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</option>
					<?php endforeach; ?>
				</select> <?php echo wp_kses_post( $field_description_data['description'] ); ?>
			</td>
		</tr>
		<?php
	}

	public function pre_update_gzd_privacy_option_page( $new_value, $old_value ) {
		/**
		 * Filter to disable syncing WP privacy page option with Germanized
		 * privacy page option.
		 *
		 * @param bool $enabled Set to false to disable syncing.
		 *
		 * @since 2.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_sync_wp_privacy_page', true ) ) {
			remove_filter(
				'pre_update_option_wp_page_for_privacy_policy',
				array(
					$this,
					'pre_update_wp_privacy_option_page',
				),
				10
			);
			update_option( 'wp_page_for_privacy_policy', $new_value );
		}

		return $new_value;
	}

	/**
	 * Updates Germanized privacy page option as soon as WP option changes to keep the pages in sync.
	 *
	 * @param $new_value
	 * @param $old_value
	 */
	public function pre_update_wp_privacy_option_page( $new_value, $old_value ) {

		/** This filter is documented in includes/admin/class-wc-gzd-admin.php */
		if ( apply_filters( 'woocommerce_gzd_sync_wp_privacy_page', true ) ) {
			remove_filter(
				'pre_update_option_woocommerce_data_security_page_id',
				array(
					$this,
					'pre_update_gzd_privacy_option_page',
				),
				10
			);
			update_option( 'woocommerce_data_security_page_id', $new_value );
		}

		return $new_value;
	}

	public function send_paid_for_order_notification( $order ) {
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_paid_for_order' );

		$mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_paid_for_order' );

		if ( $mail ) {
			$mail->trigger( $order );

			// Note the event.
			$order->add_order_note( __( 'Paid for order notification manually sent to customer.', 'woocommerce-germanized' ), false, true );
		}

		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_paid_for_order' );
	}

	public function resend_order_confirmation( $order ) {
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_processing_order' );

		// Send the customer invoice email.
		WC()->payment_gateways();
		WC()->shipping();

		$mail_id = 'customer_processing_order';
		$mail    = WC_germanized()->emails->get_email_instance_by_id( $mail_id );

		if ( $mail ) {
			$mail->trigger( $order );

			// Note the event.
			$order->add_order_note( __( 'Order confirmation manually sent to customer.', 'woocommerce-germanized' ), false, true );

			/**
			 * Admin manual resend order confirmation email.
			 *
			 * This hook fires after a manual resend of the order confirmation email has been triggered.
			 *
			 * @param WC_Order $order The order for which the confirmation email is sent.
			 * @param string $mail_id The email id (customer_processing_order).
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_after_resend_order_confirmation_email', $order, $mail_id );
		}

		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_processing_order' );
	}

	public function order_actions( $actions ) {
		$actions['order_confirmation']          = __( 'Resend order confirmation', 'woocommerce-germanized' );
		$actions['paid_for_order_notification'] = __( 'Send paid for order notification', 'woocommerce-germanized' );

		return $actions;
	}

	public function status_tab() {
		WC_GZD_Admin_Status::output();
	}

	public function set_gzd_status_tab( $tabs ) {
		$tabs['germanized'] = __( 'Germanized', 'woocommerce-germanized' );

		return $tabs;
	}

	/**
	 * @param WC_Order $order
	 */
	public function show_checkbox_status( $order ) {
		if ( $order->get_meta( '_parcel_delivery_opted_in' ) ) {
			?>
			<p class="parcel-delivery-checkbox-status"><strong><?php esc_html_e( 'Parcel Delivery Data Transfer?', 'woocommerce-germanized' ); ?></strong><span><?php echo( wc_gzd_order_supports_parcel_delivery_reminder( $order->get_id() ) ? '<span class="dashicons dashicons-yes wc-gzd-dashicon">' . esc_html__( 'Allowed', 'woocommerce-germanized' ) . '</span>' : '<span class="dashicons dashicons-no-alt wc-gzd-dashicon">' . esc_html__( 'Not Allowed', 'woocommerce-germanized' ) . '</span>' ); ?></span></p>
			<?php
		}

		if ( $order->get_meta( '_photovoltaic_systems_opted_in' ) ) {
			?>
			<p class="photovoltaic-systems-checkbox-status"><strong><?php esc_html_e( 'Photovoltaic Systems VAT exemption?', 'woocommerce-germanized' ); ?></strong><span><?php echo( wc_gzd_order_applies_for_photovoltaic_system_vat_exemption( $order->get_id() ) ? '<span class="dashicons dashicons-yes wc-gzd-dashicon">' . esc_html__( 'Allowed', 'woocommerce-germanized' ) . '</span>' : '<span class="dashicons dashicons-no-alt wc-gzd-dashicon">' . esc_html__( 'Not Allowed', 'woocommerce-germanized' ) . '</span>' ); ?></span></p>
			<?php
		}
	}

	public function set_addon( $products, $section_id ) {
		if ( 'featured' !== $section_id ) {
			return $products;
		}

		array_unshift(
			$products,
			(object) array(
				'title'   => 'Germanized für WooCommerce Pro',
				'excerpt' => 'Upgrade jetzt auf die Pro Version von Germanized und profitiere von weiteren nützliche Funktionen speziell für den deutschen Markt sowie professionellem Support.',
				'link'    => 'https://vendidero.de/woocommerce-germanized#upgrade',
				'price'   => '79 €',
			)
		);

		return $products;
	}

	public function status_page() {
		WC_GZD_Admin_Status::output();
	}

	public function add_scripts() {
		$screen            = get_current_screen();
		$suffix            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path       = WC_germanized()->plugin_url() . '/assets/';
		$admin_script_path = $assets_path . 'js/admin/';

		wp_register_style( 'woocommerce-gzd-admin', $assets_path . 'css/admin' . $suffix . '.css', false, WC_GERMANIZED_VERSION );
		wp_enqueue_style( 'woocommerce-gzd-admin' );

		wp_register_style(
			'woocommerce-gzd-admin-settings',
			$assets_path . 'css/admin-settings' . $suffix . '.css',
			array(
				'woocommerce_admin_styles',
				'woocommerce-gzd-admin',
			),
			WC_GERMANIZED_VERSION
		);

		wp_register_script( 'wc-gzd-admin-product', $admin_script_path . 'product' . $suffix . '.js', array( 'wc-admin-product-meta-boxes', 'media-models' ), WC_GERMANIZED_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		wp_register_script( 'wc-gzd-admin-product-variations', $admin_script_path . 'product-variations' . $suffix . '.js', array( 'wc-gzd-admin-product', 'wc-admin-variation-meta-boxes' ), WC_GERMANIZED_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		wp_localize_script(
			'wc-gzd-admin-product-variations',
			'wc_gzd_admin_product_variations_params',
			array(
				'i18n_set_delivery_time' => __( 'Insert delivery time name, slug or id.', 'woocommerce-germanized' ),
				'i18n_set_product_unit'  => __( 'Insert product units amount.', 'woocommerce-germanized' ),
			)
		);

		wp_register_script( // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
			'wc-gzd-admin-legal-checkboxes',
			$admin_script_path . 'legal-checkboxes' . $suffix . '.js',
			array(
				'jquery',
				'wp-util',
				'underscore',
				'backbone',
				'jquery-ui-sortable',
				'wc-enhanced-select',
			),
			WC_GERMANIZED_VERSION
		);

		wp_register_script( // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
			'wc-gzd-admin-settings',
			$assets_path . 'js/admin/settings' . $suffix . '.js',
			array(
				'jquery',
				'woocommerce_admin',
			),
			WC_GERMANIZED_VERSION
		);

		wp_localize_script(
			'wc-gzd-admin-settings',
			'wc_gzd_admin_settings_params',
			array(
				'tab_toggle_nonce'        => wp_create_nonce( 'wc_gzd_tab_toggle_nonce' ),
				'install_extension_nonce' => wp_create_nonce( 'wc_gzd_install_extension_nonce' ),
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'search_term_nonce'       => wp_create_nonce( 'search-taxonomy-terms' ),
			)
		);

		if ( in_array( $screen->id, array( 'product', 'edit-product' ), true ) ) {
			wp_enqueue_script( 'wc-gzd-admin-product' );
			wp_enqueue_script( 'wc-gzd-admin-product-variations' );
		}

		/**
		 * After admin assets.
		 *
		 * This hook fires after Germanized has loaded and enqueued it's admin assets.
		 *
		 * @param WC_GZD_Admin $this The admin class.
		 * @param string $admin_script_path The absolute URL to the plugins admin js scripts.
		 * @param string $suffix The assets suffix e.g. .min in non-debugging-mode.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_admin_assets', $this, $admin_script_path, $suffix );
	}

	/**
	 * @param string $post_type
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function add_legal_page_metabox( $post_type, $post ) {
		$legal_pages = array();

		foreach ( array_keys( wc_gzd_get_legal_pages( true ) ) as $page ) {
			$legal_pages[] = wc_get_page_id( $page );
		}

		if ( $post && in_array( $post->ID, $legal_pages, true ) ) {
			add_meta_box( 'wc-gzd-legal-page-email-content', __( 'Optional Email Content', 'woocommerce-germanized' ), array( $this, 'init_legal_page_metabox' ), 'page' );
		}
	}

	public function init_legal_page_metabox( $post ) {
		echo '<p class="small">' . esc_html__( 'Add content which will be replacing default page content within emails.', 'woocommerce-germanized' ) . '</p>';
		wp_editor(
			htmlspecialchars_decode( get_post_meta( $post->ID, '_legal_text', true ) ),
			'legal_page_email_content',
			array(
				'textarea_name' => '_legal_text',
				'textarea_rows' => 5,
			)
		);
	}

	/**
	 * @param string $post_type
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function register_product_meta_boxes( $post_type, $post ) {
		if ( 'product' === $post_type && $post ) {
			$product = wc_get_product( $post );

			if ( $product ) {
				add_meta_box(
					'wc-gzd-product-mini-desc',
					__( 'Cart description', 'woocommerce-germanized' ),
					array(
						$this,
						'init_product_mini_desc',
					),
					'product',
					'advanced',
					'high'
				);

				if ( ! $product->is_type( 'variable' ) ) {
					add_meta_box(
						'wc-gzd-product-defect-description',
						__( 'Defect description', 'woocommerce-germanized' ),
						array(
							$this,
							'init_product_defect_description',
						),
						'product',
						'advanced',
						'high'
					);
				}
			}
		}
	}

	public function save_legal_page_content( $post_id, $post, $update ) {
		if ( 'page' !== $post->post_type ) {
			return;
		}

		if ( isset( $_POST['_legal_text'] ) && ! empty( $_POST['_legal_text'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Addslashes before updating meta data as update_post_meta unslashes the data again
			update_post_meta( $post_id, '_legal_text', addslashes( wc_gzd_sanitize_html_text_field( wp_unslash( $_POST['_legal_text'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		} else {
			delete_post_meta( $post_id, '_legal_text' );
		}
	}

	public function init_product_mini_desc( $post ) {
		echo '<p class="small">' . esc_html__( 'This content will be shown as short product description within checkout and emails.', 'woocommerce-germanized' ) . '</p>';

		wp_editor(
			htmlspecialchars_decode( get_post_meta( $post->ID, '_mini_desc', true ) ),
			'wc_gzd_product_mini_desc',
			array(
				'textarea_name' => '_mini_desc',
				'textarea_rows' => 5,
				'media_buttons' => false,
			)
		);
	}

	public function init_product_defect_description( $post ) {
		echo '<p class="small">' . esc_html__( 'Inform your customers about product defects. This description will be shown on top of your product description and during cart/checkout.', 'woocommerce-germanized' ) . '</p>';

		wp_editor(
			htmlspecialchars_decode( get_post_meta( $post->ID, '_defect_description', true ) ),
			'wc_gzd_product_defect_description',
			array(
				'textarea_name' => '_defect_description',
				'textarea_rows' => 5,
				'media_buttons' => false,
			)
		);
	}

	protected function check_language_install() {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$language = isset( $_GET['wc-gzd-check-language_install'] ) ? sanitize_text_field( wp_unslash( $_GET['wc-gzd-check-language_install'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $language ) ) {
			return false;
		}

		// Download language pack if possible
		if ( wp_can_install_language_pack() ) {
			$loaded_language = wp_download_language_pack( $language );

			if ( $loaded_language ) {
				update_option( 'WPLANG', $language );
				load_default_textdomain( $loaded_language );

				// Redirect to check for updates
				wp_safe_redirect( esc_url_raw( admin_url( 'update-core.php?force-check=1' ) ) );
				exit();
			}
		}

		return false;
	}

	protected function check_text_options_deletion() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", 'woocommerce_gzd_%_text' ) );

		$manager = WC_GZD_Legal_Checkbox_Manager::instance();
		$manager->do_register_action();
		$options = $manager->get_options();

		$checkboxes   = $manager->get_checkboxes();
		$text_options = array(
			'label',
			'error_message',
			'confirmation',
			'admin_desc',
			'admin_name',
		);

		foreach ( $checkboxes as $checkbox ) {
			if ( ! $checkbox->is_core() ) {
				continue;
			}
			foreach ( $text_options as $text_option ) {
				if ( isset( $options[ $checkbox->get_id() ][ $text_option ] ) ) {
					unset( $options[ $checkbox->get_id() ][ $text_option ] );
				}
			}
		}

		/**
		 * Clear options cache before calling add_option again
		 */
		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		$manager->update_options( $options );

		// Reinstall options
		WC_GZD_Install::create_options();

		/**
		 * After text options deletion.
		 *
		 * This hook fires after Germanized has deleted and re-installed it's text options.
		 *
		 * @since 1.6.0
		 */
		do_action( 'woocommerce_gzd_deleted_text_options' );

		// Redirect to check for updates
		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ) );
		exit();
	}

	public function get_complaints_shortcode_pages() {
		$pages = array(
			'imprint' => wc_get_page_id( 'imprint' ),
		);

		if ( wc_get_page_id( 'terms' ) && -1 !== wc_get_page_id( 'terms' ) ) {
			$pages['terms'] = wc_get_page_id( 'terms' );
		}

		return $pages;
	}

	public function check_resend_activation_email() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['user_id'] ) && isset( $_GET['gzd-resend-activation'] ) && 'yes' === $_GET['gzd-resend-activation'] && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'resend-activation-link' ) ) {
			$user_id = absint( $_GET['user_id'] );

			if ( ! empty( $user_id ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
				$helper              = WC_GZD_Customer_Helper::instance();
				$user_activation     = $helper->get_customer_activation_meta( $user_id, true );
				$user_activation_url = $helper->get_customer_activation_url( $user_activation );

				if ( $email = WC_germanized()->emails->get_email_instance_by_id( 'customer_new_account_activation' ) ) {
					$email->trigger( $user_id, $user_activation, $user_activation_url );
				}
			}

			// Redirect to check for updates
			wp_safe_redirect( esc_url_raw( admin_url( sprintf( 'user-edit.php?user_id=%d&gzd-sent=yes', $user_id ) ) ) );
		}
	}

	protected function check_complaints_shortcode_append() {
		$pages = $this->get_complaints_shortcode_pages();

		foreach ( $pages as $page_name => $page_id ) {
			if ( -1 !== $page_id ) {
				$this->insert_complaints_shortcode( $page_id );
			}
		}

		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=disputes' ) ) );
		exit();
	}

	public function is_complaints_shortcode_inserted( $page_id ) {
		$post = get_post( $page_id );

		if ( $post ) {
			return wc_gzd_content_has_shortcode( $post->post_content, 'gzd_complaints' );
		}

		return false;
	}

	public function insert_complaints_shortcode( $page_id ) {
		if ( $this->is_complaints_shortcode_inserted( $page_id ) ) {
			return;
		}

		wc_gzd_update_page_content( $page_id, '[gzd_complaints]' );
	}

	protected function check_encryption_key_insert() {
		$result = false;

		if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
			if ( ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) {
				$result = WC_GZD_Secret_Box_Helper::maybe_insert_missing_key();
			}
		}

		// Redirect to check for updates
		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'added-encryption-key' => wc_bool_to_string( $result ) ), wp_get_referer() ) ) );
		exit();
	}

	protected function check_disable_notices() {
		if ( get_option( 'woocommerce_gzd_disable_notices' ) ) {
			delete_option( 'woocommerce_gzd_disable_notices' );
		} else {
			update_option( 'woocommerce_gzd_disable_notices', 'yes' );
		}
	}

	public function disable_small_business_options() {
		// Update woocommerce options to show tax
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
	}

	public function enable_small_business_options() {
		// Update woocommerce options to not show tax
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		update_option( 'woocommerce_price_display_suffix', '' );

		update_option( 'woocommerce_gzd_tax_mode_additional_costs', 'none' );
	}

	protected function check_insert_vat_rates() {
		WC_GZD_Install::create_tax_rates();

		// Redirect to check for updates
		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-settings&tab=tax&section=standard' ) ) );
		exit();
	}

	public function get_shipping_method_instances() {

		// Make sure we are not firing before init because otherwise some Woo errors might occur
		if ( ! did_action( 'init' ) ) {
			return array();
		}

		// WC_Shipping_Zone will try to call WC()->countries. Make sure that the object already exists.
		if ( ! isset( WC()->countries ) || ! is_a( WC()->countries, 'WC_Countries' ) ) {
			return array();
		}

		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			$instances = WC()->shipping->get_shipping_methods();
		} else {
			$zones     = WC_Shipping_Zones::get_zones();
			$worldwide = new WC_Shipping_Zone( 0 );

			$instances = $worldwide->get_shipping_methods( true );

			foreach ( $zones as $id => $zone ) {
				$zone      = new WC_Shipping_Zone( $id );
				$instances = $instances + $zone->get_shipping_methods( true );
			}
		}

		return $instances;
	}

	public function get_shipping_method_instances_options() {
		$methods                  = $this->get_shipping_method_instances();
		$shipping_methods_options = array();

		foreach ( $methods as $key => $method ) {

			if ( method_exists( $method, 'get_rate_id' ) ) {
				$key = $method->get_rate_id();
			} else {
				$key = $method->id;
			}

			$title = $method->get_title();

			$shipping_methods_options[ $key ] = ( empty( $title ) ? $method->get_method_title() : $title );
		}

		return $shipping_methods_options;
	}

	public function get_payment_gateway_options() {
		$gateways = WC()->payment_gateways->payment_gateways();
		$options  = array();

		if ( ! empty( $gateways ) ) {
			foreach ( $gateways as $gateway ) {
				$options[ $gateway->id ] = $gateway->title;
			}
		}

		return $options;
	}

	private function get_setting_key_by_id( $settings, $id, $type = '' ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				if ( isset( $value['id'] ) && $value['id'] === $id ) {
					if ( ! empty( $type ) && $type !== $value['type'] ) {
						continue;
					}

					return $key;
				}
			}
		}

		return false;
	}

	public function remove_setting( $settings, $id ) {

		foreach ( $settings as $key => $value ) {
			if ( isset( $value['id'] ) && $id === $value['id'] ) {
				unset( $settings[ $key ] );
			}
		}

		return array_filter( $settings );

	}

	public function insert_setting_after( $settings, $id, $insert = array(), $type = '' ) {
		$key = $this->get_setting_key_by_id( $settings, $id, $type );
		if ( is_numeric( $key ) ) {
			$key ++;
			$settings = array_merge( array_merge( array_slice( $settings, 0, $key, true ), $insert ), array_slice( $settings, $key, count( $settings ) - 1, true ) );
		} else {
			$settings += $insert;
		}

		return $settings;
	}

}

WC_GZD_Admin::instance();
