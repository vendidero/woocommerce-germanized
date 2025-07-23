<?php

namespace Vendidero\Shiptastic\Admin;

use Vendidero\Shiptastic\API\Helper;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\ShippingMethod\MethodHelper;
use Vendidero\Shiptastic\Packaging\ReportHelper;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\Automation;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

	protected static $bulk_handlers = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 35 );
		add_action( 'woocommerce_process_shop_order_meta', 'Vendidero\Shiptastic\Admin\MetaBox::save', 60, 2 );

		add_action( 'admin_menu', array( __CLASS__, 'shipments_menu' ), 15 );
		add_action( 'load-woocommerce_page_wc-stc-shipments', array( __CLASS__, 'setup_shipments_table' ), 0 );
		add_action( 'load-woocommerce_page_wc-stc-return-shipments', array( __CLASS__, 'setup_returns_table' ), 0 );

		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_wc_stc_shipments_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_wc_stc_return_shipments_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );

		add_filter( 'woocommerce_navigation_get_breadcrumbs', array( __CLASS__, 'register_admin_breadcrumbs' ), 20, 2 );
		add_filter( 'woocommerce_navigation_is_connected_page', array( __CLASS__, 'register_admin_connected_pages' ), 10, 2 );

		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'register_screen_ids' ), 10 );
		add_action( 'admin_menu', array( __CLASS__, 'menu_highlight' ), 100 );

		// Return reason options
		add_action( 'woocommerce_admin_field_shipment_return_reasons', array( __CLASS__, 'output_return_reasons_field' ) );
		add_action( 'woocommerce_shiptastic_admin_settings_after_save_general_return', array( __CLASS__, 'save_return_reasons' ), 10 );

		// Packaging options
		add_action( 'woocommerce_admin_field_packaging_list', array( __CLASS__, 'output_packaging_list' ) );
		add_action( 'woocommerce_shiptastic_admin_settings_after_save_packaging', array( __CLASS__, 'save_packaging_list' ), 10, 2 );

		add_action( 'woocommerce_admin_field_packaging_reports', array( __CLASS__, 'output_packaging_reports' ) );
		add_action( 'woocommerce_admin_field_shipments_country_select', array( __CLASS__, 'output_custom_country_select' ) );

		// Menu count
		add_action( 'admin_head', array( __CLASS__, 'menu_return_count' ) );

		// Check upload folder
		add_action( 'admin_notices', array( __CLASS__, 'check_upload_dir' ) );

		// Register endpoints within settings
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'register_endpoint_settings' ), 20, 2 );
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'register_settings' ) );

		// Product Options
		add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'product_options' ), 9 );
		add_action( 'woocommerce_variation_options_dimensions', array( __CLASS__, 'product_variation_options' ), 10, 3 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product' ), 10, 1 );
		add_action( 'woocommerce_admin_process_variation_object', array( __CLASS__, 'save_variation_product' ), 10, 2 );

		// Observe base country setting
		add_action( 'woocommerce_settings_save_general', array( __CLASS__, 'observe_base_country_setting' ), 100 );

		// Edit packaging page
		add_action( 'admin_menu', array( __CLASS__, 'add_packaging_page' ), 25 );
		add_action( 'admin_head', array( __CLASS__, 'hide_packaging_page_from_menu' ) );
		add_action( 'woocommerce_admin_field_shipping_provider_packaging_zone_title', array( __CLASS__, 'render_shipping_provider_packaging_zone_title_field' ) );
		add_action( 'woocommerce_admin_field_shipping_provider_packaging_zone_title_close', array( __CLASS__, 'render_shipping_provider_packaging_zone_title_close_field' ) );
		add_action( 'admin_post_woocommerce_stc_save_packaging_settings', array( __CLASS__, 'save_packaging_page' ) );

		// Hide shipping provider meta
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'set_order_meta_hidden' ) );

		add_action(
			'admin_init',
			function () {
				// Order shipping status
				add_filter( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_columns', array( __CLASS__, 'register_order_shipping_status_column' ), 20 );
				add_action( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_custom_column', array( __CLASS__, 'render_order_columns' ), 20, 2 );

				add_filter( 'handle_bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'handle_order_bulk_actions' ), 10, 3 );
				add_filter( 'bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'define_order_bulk_actions' ), 10, 1 );
			}
		);

		add_action( 'woocommerce_admin_field_shiptastic_toggle', array( __CLASS__, 'toggle_input_field' ), 30 );
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'sanitize_toggle_field' ), 10, 3 );

		add_action( 'woocommerce_admin_field_shiptastic_oauth', array( __CLASS__, 'oauth_field' ), 30 );

		add_action( 'woocommerce_admin_field_dimensions', array( __CLASS__, 'register_dimensions_field' ), 30 );
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'sanitize_dimensions_field' ), 10, 3 );

		add_action( 'woocommerce_system_status_report', array( __CLASS__, 'status_report' ) );
		add_filter( 'woocommerce_debug_tools', array( __CLASS__, 'register_tools' ), 10, 1 );

		add_action( 'admin_post_woocommerce_stc_oauth', array( __CLASS__, 'oauth' ) );
		add_action( 'admin_post_woocommerce_stc_oauth_init', array( __CLASS__, 'oauth_init' ) );
		add_action( 'admin_post_woocommerce_stc_oauth_revoke', array( __CLASS__, 'oauth_revoke' ) );
	}

	public static function register_tools( $tools ) {
		if ( ! Package::is_integration() ) {
			$tools['shiptastic_debug_mode'] = array(
				'name'     => _x( 'Shiptastic debug mode', 'shipments', 'woocommerce-germanized' ),
				'button'   => true === Package::is_debug_mode() ? _x( 'Disable', 'shipments', 'woocommerce-germanized' ) : _x( 'Enable', 'shipments', 'woocommerce-germanized' ),
				'callback' => array( __CLASS__, 'toggle_debug_mode' ),
				'desc'     => '',
			);
		}

		return $tools;
	}

	public static function toggle_debug_mode() {
		if ( true === Package::is_debug_mode() ) {
			delete_option( 'woocommerce_shiptastic_enable_debug_mode' );
		} else {
			update_option( 'woocommerce_shiptastic_enable_debug_mode', 'yes', false );
		}
	}

	public static function oauth_init() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		if ( ! check_ajax_referer( 'stc_oauth_init' ) ) {
			wp_die( -1 );
		}

		$auth_type = isset( $_GET['auth_type'] ) ? wc_clean( wp_unslash( $_GET['auth_type'] ) ) : '';
		$referer   = wp_get_referer();

		if ( $api = Helper::get_api( $auth_type ) ) {
			if ( $auth = $api->get_auth_api() ) {
				if ( is_a( $auth, '\Vendidero\Shiptastic\API\Auth\OAuthGateway' ) ) {
					$auth->authorize( $referer );
				}
			}
		}

		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'has_error' => 'yes' ), $referer ) ) );
		exit();
	}

	public static function oauth_revoke() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		if ( ! check_ajax_referer( 'stc_oauth_revoke' ) ) {
			wp_die( -1 );
		}

		$auth_type = isset( $_GET['auth_type'] ) ? wc_clean( wp_unslash( $_GET['auth_type'] ) ) : '';
		$referer   = wp_get_referer();

		if ( $api = Helper::get_api( $auth_type ) ) {
			if ( $auth = $api->get_auth_api() ) {
				if ( is_a( $auth, '\Vendidero\Shiptastic\API\Auth\OAuthGateway' ) ) {
					$auth->revoke();
				}
			}
		}

		wp_safe_redirect( esc_url_raw( $referer ) );
		exit();
	}

	public static function oauth() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$auth_type = isset( $_GET['auth_type'] ) ? wc_clean( wp_unslash( $_GET['auth_type'] ) ) : '';

		if ( ! check_ajax_referer( "stc_oauth_{$auth_type}" ) ) {
			wp_die( -1 );
		}

		$nonce = isset( $_GET['request_nonce'] ) ? wp_unslash( $_GET['request_nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! wp_verify_nonce( $nonce, "stc_oauth_init_{$auth_type}" ) ) {
			wp_die( -1 );
		}

		$code     = isset( $_GET['code'] ) ? wc_clean( wp_unslash( $_GET['code'] ) ) : '';
		$referer  = wp_get_referer();
		$is_error = true;

		if ( ! empty( $code ) ) {
			if ( $api = Helper::get_api( $auth_type ) ) {
				if ( $auth = $api->get_auth_api() ) {
					if ( is_a( $auth, '\Vendidero\Shiptastic\API\Auth\OAuthGateway' ) ) {
						$response = $auth->get_token( $code );

						if ( ! $response->is_error() ) {
							$is_error = false;
						}
					}
				}
			}
		}

		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'has_error' => wc_bool_to_string( $is_error ) ), $referer ) ) );
		exit();
	}

	public static function get_template_info() {
		$core_path     = Package::get_path( 'templates' );
		$files         = \WC_Admin_Status::scan_template_files( $core_path );
		$template_path = Package::get_template_path();
		$template_data = array(
			'files'                  => array(),
			'has_outdated_templates' => false,
		);

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
				$core_version  = \WC_Admin_Status::get_file_version( trailingslashit( $core_path ) . $file );
				$theme_version = \WC_Admin_Status::get_file_version( $theme_file );

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
					$file_data['outdated']                   = true;
					$template_data['has_outdated_templates'] = true;
				}

				$template_data['files'][] = $file_data;
			}
		}

		return $template_data;
	}

	public static function status_report() {
		$template_info = self::get_template_info();
		?>
		<table class="wc_status_table widefat" id="status-table-shiptastic" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3" data-export-label="Shiptastic"><h2><?php echo esc_html_x( 'Shiptastic', 'shipments', 'woocommerce-germanized' ); ?></h2></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td data-export-label="Shiptastic Database Version"><?php echo esc_html_x( 'Database Version', 'shipments', 'woocommerce-germanized' ); ?></td>
				<td class="help">&nbsp</td>
				<td><?php echo esc_html( get_option( 'woocommerce_shiptastic_db_version' ) ); ?></td>
			</tr>
			<tr>
				<td data-export-label="Shiptastic Overrides"><?php echo esc_html_x( 'Overrides', 'shipments', 'woocommerce-germanized' ); ?></td>
				<td class="help"><?php echo wc_help_tip( esc_html_x( 'This section shows any files that are overriding the default Shiptastic template pages.', 'shipments', 'woocommerce-germanized' ) ); ?></td>
				<td>
					<?php if ( ! empty( $template_info['files'] ) ) : ?>
						<?php foreach ( $template_info['files'] as $file ) : ?>
							<?php printf( '<code>%s</code>', esc_html( str_replace( WP_CONTENT_DIR . '/themes/', '', $file['theme_file'] ) ) ); ?>
							<?php if ( $file['outdated'] ) : ?>
								<?php printf( esc_html_x( 'Version %1$s is out of date. The core version %2$s is available at: %3$s', 'shipments', 'woocommerce-germanized' ), '<span class="red" style="color:red">' . esc_html( $file['theme_version'] ) . '</span>', esc_html( $file['core_version'] ), '<code>' . esc_html( str_replace( WP_PLUGIN_DIR, '', $file['core_file'] ) ) . '</code>' ); ?>
							<?php endif; ?>
							<br/>
						<?php endforeach; ?>
					<?php else : ?>
						&ndash;
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( true === $template_info['has_outdated_templates'] ) : ?>
				<tr>
					<td data-export-label="Shiptastic Outdated Templates"><?php echo esc_html_x( 'Outdated templates', 'shipments', 'woocommerce-germanized' ); ?>:</td>
					<td class="help">&nbsp;</td>
					<td>
						<mark class="error">
							<span class="dashicons dashicons-warning"></span>
						</mark>
						<a href="" target="_blank">
							<?php echo esc_html_x( 'Learn how to update', 'shipments', 'woocommerce-germanized' ); ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	public static function sanitize_toggle_field( $value, $option, $raw_value ) {
		$option = wp_parse_args(
			$option,
			array(
				'type' => '',
			)
		);

		if ( 'shiptastic_toggle' === $option['type'] ) {
			$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
		}

		return $value;
	}

	public static function register_dimensions_field( $setting ) {
		$setting = wp_parse_args(
			$setting,
			array(
				'id'                => '',
				'desc'              => '',
				'default'           => array(),
				'placeholder'       => array(),
				'custom_attributes' => array(),
				'row_class'         => '',
				'title'             => '',
			)
		);

		if ( ! isset( $setting['value'] ) ) {
			$setting['value'] = \WC_Admin_Settings::get_option( $setting['id'], $setting['default'] );
		}

		$setting['value'] = (array) $setting['value'];
		$setting['value'] = wp_parse_args(
			$setting['value'],
			array(
				'length' => 0,
				'width'  => 0,
				'height' => 0,
			)
		);

		$setting['placeholder'] = (array) $setting['placeholder'];
		$setting['placeholder'] = wp_parse_args(
			$setting['placeholder'],
			array(
				'length' => 0,
				'width'  => 0,
				'height' => 0,
			)
		);

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $setting['custom_attributes'] ) && is_array( $setting['custom_attributes'] ) ) {
			foreach ( $setting['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		$titles = array(
			'length' => _x( 'Length', 'shipments', 'woocommerce-germanized' ),
			'width'  => _x( 'Width', 'shipments', 'woocommerce-germanized' ),
			'height' => _x( 'Height', 'shipments', 'woocommerce-germanized' ),
		);

		// Description handling.
		$field_description_data = \WC_Admin_Settings::get_field_description( $setting );
		?>
		<tr valign="top"<?php echo $setting['row_class'] ? ' class="' . esc_attr( $setting['row_class'] ) . '"' : ''; ?>">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $setting['id'] ); ?>"><?php echo esc_html( $setting['title'] ); ?> <?php echo wp_kses_post( $field_description_data['tooltip_html'] ); ?></label>
		</th>
		<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $setting['type'] ) ); ?>">
			<div class="dimensions-fields">
				<?php
				foreach ( array( 'length', 'width', 'height' ) as $dim ) :
					?>
					<div class="dimension-field">
						<label for="<?php echo esc_attr( $setting['id'] ); ?>-<?php echo esc_attr( $dim ); ?>"><?php echo esc_html( $titles[ $dim ] ); ?></label>
						<input
							name="<?php echo esc_attr( $setting['field_name'] ); ?>[<?php echo esc_attr( $dim ); ?>]"
							id="<?php echo esc_attr( $setting['id'] ); ?>-<?php echo esc_attr( $dim ); ?>"
							type="text"
							style="<?php echo esc_attr( $setting['css'] ); ?>"
							value="<?php echo esc_attr( $setting['value'][ $dim ] ); ?>"
							class="<?php echo esc_attr( $setting['class'] ); ?>"
							placeholder="<?php echo esc_attr( $setting['placeholder'][ $dim ] ); ?>"
							<?php echo implode( ' ', $custom_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						/>
					</div>
				<?php endforeach; ?>
			</div>
			<?php echo wp_kses_post( $field_description_data['description'] ); ?>
		</td>
		</tr>
		<?php
	}

	public static function sanitize_dimensions_field( $value, $option, $raw_value ) {
		$option = wp_parse_args(
			$option,
			array(
				'type'       => '',
				'field_name' => '',
				'id'         => '',
				'store_as'   => 'separate',
			)
		);

		if ( 'dimensions' === $option['type'] ) {
			$value       = wp_parse_args(
				(array) $value,
				array(
					'length' => 0,
					'width'  => 0,
					'height' => 0,
				)
			);
			$value       = wc_clean( $value );
			$option_name = ! empty( $option['field_name'] ) ? $option['field_name'] : $option['id'];

			if ( 'separate' === $option['store_as'] ) {
				$option_name = str_replace( 'dimensions', '', $option_name );

				foreach ( $value as $dim => $dim_val ) {
					update_option( "{$option_name}{$dim}", $dim_val );
				}

				$value = null;
			}
		}

		return $value;
	}

	public static function oauth_field( $value ) {
		$value = wp_parse_args(
			$value,
			array(
				'api_type' => '',
			)
		);

		// Description handling.
		$field_description_data = \WC_Admin_Settings::get_field_description( $value );
		$api                    = Helper::get_api( $value['api_type'] );

		if ( ! $api ) {
			return '';
		}

		$revoke_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'    => 'woocommerce_stc_oauth_revoke',
					'auth_type' => $api->get_setting_name(),
				),
				admin_url( 'admin-post.php' )
			),
			'stc_oauth_revoke'
		);

		$connect_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'    => 'woocommerce_stc_oauth_init',
					'auth_type' => $api->get_setting_name(),
				),
				admin_url( 'admin-post.php' )
			),
			'stc_oauth_init'
		);
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="wc-shiptastic-label-wrap"><?php echo esc_html( $value['title'] ); ?><?php echo wp_kses_post( $field_description_data['tooltip_html'] ); ?></span>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<fieldset>
					<?php if ( $api->get_auth_api()->is_connected() ) : ?>
						<a class="button button-secondary" href="<?php echo esc_url( $revoke_url ); ?>"><?php echo esc_html_x( 'Revoke', 'shipments', 'woocommerce-germanized' ); ?></a>
					<?php else : ?>
						<a class="button button-primary" href="<?php echo esc_url( $connect_url ); ?>"><?php printf( esc_html_x( 'Connect to %s', 'shipments', 'woocommerce-germanized' ), esc_html( $api->get_title() ) ); ?></a>
					<?php endif; ?>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	public static function toggle_input_field( $value ) {
		// Description handling.
		$field_description_data = \WC_Admin_Settings::get_field_description( $value );

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = \WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$option_value = $value['value'];

		if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
			?>
			<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="wc-shiptastic-label-wrap"><?php echo esc_html( $value['title'] ); ?><?php echo wp_kses_post( $field_description_data['tooltip_html'] ); ?></span>
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
		<?php self::render_toggle_field( $value ); ?>
		</fieldset>
		<?php
		if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
			?>
			</td>
			</tr>
			<?php
		}
	}

	public static function render_toggle_field( $args ) {
		$args          = wp_parse_args(
			$args,
			array(
				'id'                => '',
				'css'               => '',
				'value'             => '',
				'class'             => '',
				'name'              => '',
				'suffix'            => '',
				'desc_tip'          => false,
				'desc'              => '',
				'custom_attributes' => array(),
			)
		);
		$args['value'] = wc_bool_to_string( $args['value'] );
		$args['name']  = empty( $args['name'] ) ? $args['id'] : $args['name'];
		// Description handling.
		$field_description_data = \WC_Admin_Settings::get_field_description( $args );
		?>
		<a href="#" class="woocommerce-shiptastic-input-toggle-trigger">
			<span id="<?php echo esc_attr( $args['id'] ); ?>-toggle" class="woocommerce-shiptastic-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( 'yes' === $args['value'] ? 'enabled' : 'disabled' ); ?>"><?php echo ( ( 'yes' === $args['value'] ) ? esc_html_x( 'Yes', 'shipments', 'woocommerce-germanized' ) : esc_html_x( 'No', 'shipments', 'woocommerce-germanized' ) ); ?></span>
		</a>
		<input
		name="<?php echo esc_attr( $args['name'] ); ?>"
		id="<?php echo esc_attr( $args['id'] ); ?>"
		type="checkbox"
		style="display: none; <?php echo esc_attr( $args['css'] ); ?>"
		value="1"
		class="<?php echo esc_attr( $args['class'] ); ?>"
		<?php checked( $args['value'], 'yes' ); ?>
		<?php
		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '" ';
			}
		}
		?>
		/><?php echo esc_html( $args['suffix'] ); ?><?php echo wp_kses_post( $field_description_data['description'] ); ?>
		<?php
	}

	public static function register_admin_connected_pages( $is_connected, $current_page ) {
		if ( false === $is_connected && false === $current_page ) {
			$screen = get_current_screen();

			if ( $screen && in_array( $screen->id, self::get_core_screen_ids(), true ) ) {
				$is_connected = true;

				return $is_connected;
			}
		}

		return $is_connected;
	}

	public static function register_admin_breadcrumbs( $breadcrumbs, $current_page ) {
		if ( ! function_exists( 'wc_admin_get_core_pages_to_connect' ) ) {
			return $breadcrumbs;
		}

		if ( false === $current_page ) {
			$screen = get_current_screen();

			if ( $screen && in_array( $screen->id, self::get_core_screen_ids(), true ) ) {
				$core_pages = wc_admin_get_core_pages_to_connect();

				if ( 'woocommerce_page_shipment-packaging' === $screen->id ) {
					$breadcrumbs = array(
						array(
							esc_url_raw( add_query_arg( 'page', 'wc-settings', 'admin.php' ) ),
							$core_pages['wc-settings']['title'],
						),
						_x( 'Edit packaging', 'shipments', 'woocommerce-germanized' ),
					);
				} else {
					$page = isset( $_GET['page'] ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : 'wc-stc-shipments'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					if ( 'wc-stc-shipments' === $page ) {
						$breadcrumbs = array(
							_x( 'Shipments', 'shipments', 'woocommerce-germanized' ),
						);
					} elseif ( 'wc-stc-return-shipments' === $page ) {
						$breadcrumbs = array(
							_x( 'Returns', 'shipments', 'woocommerce-germanized' ),
						);
					} elseif ( 'shipment-packaging-report' === $page ) {
						$breadcrumbs = array(
							_x( 'Packaging Report', 'shipments', 'woocommerce-germanized' ),
						);
					}
				}
			}
		}

		return $breadcrumbs;
	}

	public static function add_packaging_page() {
		add_submenu_page( 'woocommerce', _x( 'Packaging', 'shipments', 'woocommerce-germanized' ), _x( 'Packaging', 'shipments', 'woocommerce-germanized' ), 'manage_woocommerce', 'shipment-packaging', array( __CLASS__, 'render_packaging_page' ) );
	}

	public static function render_shipping_provider_packaging_zone_title_close_field( $setting ) {
		echo '</table></div>';
	}

	public static function render_shipping_provider_packaging_zone_title_field( $setting ) {
		$setting = wp_parse_args(
			$setting,
			array(
				'name'  => '',
				'value' => 'no',
				'class' => '',
				'title' => '',
			)
		);

		if ( empty( $setting['name'] ) ) {
			$setting['name'] = $setting['id'];
		}

		$has_override = wc_string_to_bool( $setting['value'] );
		?>
		<div class="wc-stc-shipping-provider-override-title-wrapper">
			<h3 class="wc-settings-sub-title <?php echo esc_attr( $setting['class'] ); ?>"><?php echo wp_kses_post( $setting['title'] ); ?></h3>

			<fieldset class="wc-shiptastic-toggle-wrapper override-toggle-wrapper">
				<a class="woocommerce-shiptastic-input-toggle-trigger" href="#"><span class="woocommerce-shiptastic-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( $has_override ? 'enabled' : 'disabled' ); ?>"><?php echo esc_html_x( 'No', 'shipments', 'woocommerce-germanized' ); ?></span></a>
				<input
						name="<?php echo esc_attr( $setting['name'] ); ?>"
						id="wc-stc-toggle-<?php echo esc_attr( $setting['id'] ); ?>"
						type="checkbox"
						style="display: none;"
					<?php checked( $has_override ? 'yes' : 'no', 'yes' ); ?>
						value="1"
						class="wc-stc-override-toggle"
				/><p class="description"><?php echo esc_html_x( 'Override defaults?', 'shipments', 'woocommerce-germanized' ); ?></p>
			</fieldset>
		</div>
		<div class="wc-stc-packaging-zone-wrapper <?php echo esc_attr( $has_override ? 'zone-wrapper-has-override' : '' ); ?>">
			<table class="form-table woocommerce_table">
				<tbody>
		<?php
	}

	public static function get_packaging_admin_url( $packaging_id, $provider_name = '', $section = '' ) {
		$args = array( 'packaging' => absint( $packaging_id ) );

		if ( ! empty( $provider_name ) ) {
			$args['provider'] = $provider_name;
		}

		if ( ! empty( $section ) ) {
			$args['section'] = $section;
		}

		return esc_url_raw( add_query_arg( $args, admin_url( 'admin.php?page=shipment-packaging' ) ) );
	}

	public static function render_packaging_page() {
		if ( isset( $_GET['packaging'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$packaging_id = isset( $_GET['packaging'] ) ? absint( wp_unslash( $_GET['packaging'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $packaging_id ) {
				return;
			}

			if ( ! $packaging = wc_stc_get_packaging( $packaging_id ) ) {
				return;
			}

			$current_tab      = isset( $_GET['tab'] ) ? wc_clean( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_section  = isset( $_GET['section'] ) ? wc_clean( wp_unslash( $_GET['section'] ) ) : 'simple'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_settings = PackagingSettings::get_settings( $packaging, $current_tab, $current_section );
			?>
			<div class="wrap woocommerce woocommerce_page_wc-settings wc-shiptastic-packaging packaging-<?php echo esc_attr( $packaging->get_id() ); ?>">
				<h1 class="wp-heading-inline"><?php echo esc_html( $packaging->get_title() ); ?></h1>
				<a class="page-title-action" href="<?php echo esc_url( Settings::get_settings_url( 'packaging' ) ); ?>"><?php echo esc_html_x( 'All packaging', 'shipments', 'woocommerce-germanized' ); ?></a>
				<hr class="wp-header-end" />

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
						<?php foreach ( PackagingSettings::get_tabs( $packaging ) as $tab => $title ) : ?>
							<a href="<?php echo esc_url( PackagingSettings::get_settings_url( $packaging_id, $tab ) ); ?>" class="nav-tab <?php echo esc_attr( $current_tab === $tab ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $title ); ?></a>
						<?php endforeach; ?>
					</nav>

					<ul class="subsubsub">
						<?php foreach ( PackagingSettings::get_sections( $packaging, $current_tab ) as $section => $title ) : ?>
							<li><a href="<?php echo esc_url( PackagingSettings::get_settings_url( $packaging->get_id(), $current_tab, $section ) ); ?>" class="<?php echo esc_attr( $current_section === $section ? 'current' : '' ); ?>"><?php echo esc_html( $title ); ?></a></li>
						<?php endforeach; ?>
					</ul>

					<div class="wc-shiptastic-admin-settings">
						<?php
						if ( ! empty( $current_settings ) ) :
							$current_settings_to_print = $current_settings;

							if ( ! PackagingSettings::is_provider( $current_tab ) ) {
								$current_settings_to_print = array( $current_settings_to_print );
							}
							?>
							<?php foreach ( $current_settings_to_print as $settings ) : ?>
								<?php \WC_Admin_Settings::output_fields( $settings ); ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $current_settings ) ) : ?>
						<p class="submit">
							<input type="hidden" name="action" value="woocommerce_stc_save_packaging_settings" />
							<input type="hidden" name="section" value="<?php echo esc_attr( $current_section ); ?>" />
							<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>" />
							<input type="hidden" name="packaging_id" value="<?php echo esc_attr( $packaging->get_id() ); ?>" />

							<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php echo esc_attr_x( 'Save changes', 'shipments', 'woocommerce-germanized' ); ?>"><?php echo esc_html_x( 'Save changes', 'shipments', 'woocommerce-germanized' ); ?></button>
							<?php wp_nonce_field( 'woocommerce-stc-packaging-settings' ); ?>
						</p>
					<?php else : ?>
						<div class="notice notice-warning inline"><p><?php printf( esc_html_x( 'This provider does not support adjusting settings related to %1$s', 'shipments', 'woocommerce-germanized' ), esc_html( wc_stc_get_shipment_label_title( $current_section, true ) ) ); ?></p></div>
					<?php endif; ?>
				</form>
			</div>
			<?php
		}
	}

	public static function hide_packaging_page_from_menu() {
		remove_submenu_page( 'woocommerce', 'shipment-packaging' );
	}

	public static function save_packaging_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-stc-packaging-settings' ) ) {
			wp_die( '', 400 );
		}

		$tab          = isset( $_POST['tab'] ) ? wc_clean( wp_unslash( $_POST['tab'] ) ) : '';
		$section      = isset( $_POST['section'] ) ? wc_clean( wp_unslash( $_POST['section'] ) ) : '';
		$packaging_id = isset( $_POST['packaging_id'] ) ? absint( wp_unslash( $_POST['packaging_id'] ) ) : 0;

		if ( ! $packaging = wc_stc_get_packaging( $packaging_id ) ) {
			wp_die( '', 400 );
		}

		PackagingSettings::save_settings( $packaging, $tab, $section );

		wp_safe_redirect( esc_url_raw( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-stc-shipments' ) ) );
	}

	/**
	 * Hide product description from order meta default output
	 *
	 * @param array $metas
	 */
	public static function set_order_meta_hidden( $metas ) {
		$metas = array_merge( $metas, array( 'shipping_provider', '_shipping_provider' ) );

		return $metas;
	}

	public static function render_order_columns( $column, $post_id ) {
		if ( 'shipping_status' === $column ) {
			if ( is_a( $post_id, 'WC_Order' ) ) {
				$the_order = $post_id;
			} else {
				global $the_order;

				if ( ! $the_order || $the_order->get_id() !== $post_id ) {
					$the_order = wc_get_order( $post_id );
				}
			}

			if ( $shipment_order = wc_stc_get_shipment_order( $the_order ) ) {
				$shipping_status = $shipment_order->get_shipping_status();
				$status_html     = '<mark class="order-shipping-status status-' . esc_attr( $shipping_status ) . '"><span>' . esc_html( wc_stc_get_shipment_order_shipping_status_name( $shipping_status ) ) . '</span></mark>';

				if ( in_array( $shipping_status, array( 'shipped', 'partially-shipped' ), true ) && $shipment_order->get_shipments() ) {
					if ( $last_shipment = $shipment_order->get_last_shipment_with_tracking() ) {
						echo '<a target="_blank" href="' . esc_url( $last_shipment->get_tracking_url() ) . '" class="help_tip" data-tip="' . esc_attr( $last_shipment->get_tracking_id() ) . '">' . wp_kses_post( $status_html ) . '</a>';
					} else {
						echo '<a target="_blank" href="' . esc_url( add_query_arg( array( 'order_id' => $the_order->get_id() ), admin_url( 'admin.php?page=wc-stc-shipments' ) ) ) . '" class="help_tip" data-tip="">' . wp_kses_post( $status_html ) . '</a>';
					}
				} else {
					echo wp_kses_post( $status_html );
				}
			}
		}
	}

	public static function register_order_shipping_status_column( $columns ) {
		$new_columns  = array();
		$added_column = false;

		foreach ( $columns as $column_name => $title ) {
			if ( ! $added_column && ( 'shipping_address' === $column_name || 'wc_actions' === $column_name ) ) {
				$new_columns['shipping_status'] = _x( 'Shipping Status', 'shipments-order-column-name', 'woocommerce-germanized' );
				$added_column                   = true;
			}

			$new_columns[ $column_name ] = $title;
		}

		if ( ! $added_column ) {
			$new_columns['shipping_status'] = _x( 'Shipping Status', 'shipments-order-column-name', 'woocommerce-germanized' );
		}

		return $new_columns;
	}

	/**
	 * In case the shipper/return country is set to AF (or DE with missing state) due to a bug in Woo, make sure
	 * to automatically adjust it to the right value in case the base country option is being saved.
	 *
	 * @return void
	 */
	public static function observe_base_country_setting() {
		if ( isset( $_POST['woocommerce_default_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$new_base_country = wc_format_country_state_string( get_option( 'woocommerce_default_country' ) );

			if ( 'AF' !== $new_base_country['country'] ) {
				$shipper_country = wc_format_country_state_string( get_option( 'woocommerce_shiptastic_shipper_address_country' ) );
				$return_country  = wc_format_country_state_string( get_option( 'woocommerce_shiptastic_return_address_country' ) );

				if ( 'AF' === $shipper_country['country'] || ( 'DE' === $new_base_country['country'] && 'DE' === $shipper_country['country'] && empty( $shipper_country['state'] ) && ! empty( $new_base_country['state'] ) ) ) {
					update_option( 'woocommerce_shiptastic_shipper_address_country', get_option( 'woocommerce_default_country' ) );
				}

				if ( 'AF' === $return_country['country'] || ( 'DE' === $new_base_country['country'] && 'DE' === $return_country['country'] && empty( $return_country['state'] ) && ! empty( $return_country['state'] ) ) ) {
					update_option( 'woocommerce_shiptastic_return_address_country', get_option( 'woocommerce_default_country' ) );
				}
			}
		}
	}

	/**
	 * @param $loop
	 * @param $variation_data
	 * @param \WP_Post $variation
	 *
	 * @return void
	 */
	public static function product_variation_options( $loop, $variation_data, $variation ) {
		if ( ! $variation_object = wc_get_product( $variation ) ) {
			return;
		}

		$_parent_product          = wc_get_product( $variation_object->get_parent_id() );
		$shipments_parent_product = wc_shiptastic_get_product( $_parent_product );

		if ( wc_product_dimensions_enabled() ) {
			$shipments_product = wc_shiptastic_get_product( $variation_object );
			$parent_length     = $shipments_parent_product ? wc_format_localized_decimal( $shipments_parent_product->get_shipping_length() ) : '';
			$parent_width      = $shipments_parent_product ? wc_format_localized_decimal( $shipments_parent_product->get_shipping_width() ) : '';
			$parent_height     = $shipments_parent_product ? wc_format_localized_decimal( $shipments_parent_product->get_shipping_height() ) : '';
			?>
			<p class="form-field form-row dimensions_field shipping_dimensions_field hide_if_variation_virtual form-row-first">
				<label for="product_shipping_length">
					<?php
					printf(
						/* translators: %s dimension unit */
						esc_html_x( 'Shipping dimensions (%s)', 'shipments', 'woocommerce-germanized' ),
						esc_html( Package::get_dimensions_unit_label( get_option( 'woocommerce_dimension_unit' ) ) )
					);
					?>
				</label>
				<?php echo wc_help_tip( _x( 'Length x width x height in decimal form', 'shipments', 'woocommerce-germanized' ) ); ?>
				<span class="wrap">
					<input id="product_shipping_length" placeholder="<?php echo $parent_length ? esc_attr( $parent_length ) : esc_attr( wc_format_localized_decimal( $variation_object->get_length() ) ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="variable_shipping_length[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( wc_format_localized_decimal( $shipments_product->get_shipping_length( 'edit' ) ) ); ?>" />
					<input placeholder="<?php echo $parent_width ? esc_attr( $parent_width ) : esc_attr( wc_format_localized_decimal( $variation_object->get_width() ) ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="variable_shipping_width[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( wc_format_localized_decimal( $shipments_product->get_shipping_width( 'edit' ) ) ); ?>" />
					<input placeholder="<?php echo $parent_height ? esc_attr( $parent_height ) : esc_attr( wc_format_localized_decimal( $variation_object->get_height() ) ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="variable_shipping_height[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( wc_format_localized_decimal( $shipments_product->get_shipping_height( 'edit' ) ) ); ?>" />
				</span>
			</p>
			<?php
		}
	}

	public static function product_options() {
		global $product_object;

		$_product          = wc_get_product( $product_object );
		$shipments_product = wc_shiptastic_get_product( $_product );
		$countries         = WC()->countries->get_countries();
		$countries         = array_merge( array( '0' => _x( 'Select a country', 'shipments', 'woocommerce-germanized' ) ), $countries );
		?>
		<?php if ( wc_product_dimensions_enabled() ) : ?>
			<p class="form-field dimensions_field shipping_dimensions_field">
				<label for="product_shipping_length">
					<?php
					printf(
						/* translators: WooCommerce dimension unit */
						esc_html_x( 'Shipping dimensions (%s)', 'shipments', 'woocommerce-germanized' ),
						esc_html( Package::get_dimensions_unit_label( get_option( 'woocommerce_dimension_unit' ) ) )
					);
					?>
				</label>
				<span class="wrap">
					<input id="product_shipping_length" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $_product->get_length() ) ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_shipping_length" value="<?php echo esc_attr( wc_format_localized_decimal( $shipments_product->get_shipping_length( 'edit' ) ) ); ?>" />
					<input id="product_shipping_width" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $_product->get_width() ) ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_shipping_width" value="<?php echo esc_attr( wc_format_localized_decimal( $shipments_product->get_shipping_width( 'edit' ) ) ); ?>" />
					<input id="product_shipping_height" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $_product->get_height() ) ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="_shipping_height" value="<?php echo esc_attr( wc_format_localized_decimal( $shipments_product->get_shipping_height( 'edit' ) ) ); ?>" />
				</span>
				<?php echo wc_help_tip( _x( 'Length x width x height in decimal form', 'shipments', 'woocommerce-germanized' ) ); ?>
			</p>
		<?php endif; ?>
		<?php

		woocommerce_wp_checkbox(
			array(
				'id'          => '_is_non_returnable',
				'label'       => _x( 'Non returnable', 'shipments', 'woocommerce-germanized' ),
				'description' => _x( 'Exclude product from returns, e.g. pet food.', 'shipments', 'woocommerce-germanized' ),
				'value'       => $shipments_product->is_non_returnable( 'edit' ) ? 'yes' : 'no',
			)
		);
		?>
		<p class="wc-stc-product-settings-subtitle">
			<?php echo esc_html_x( 'Customs', 'shipments', 'woocommerce-germanized' ); ?>
			<?php if ( $help_link = apply_filters( 'woocommerce_shiptastic_product_customs_settings_help_link', '' ) ) : ?>
				<a class="page-title-action" href="<?php echo esc_url( $help_link ); ?>"><?php echo esc_html_x( 'Help', 'shipments', 'woocommerce-germanized' ); ?></a>
			<?php endif; ?>
		</p>
		<?php
		woocommerce_wp_text_input(
			array(
				'id'          => '_customs_description',
				'label'       => _x( 'Description', 'shipments', 'woocommerce-germanized' ),
				'desc_tip'    => true,
				'description' => _x( 'Choose a description to be used for customs documents, e.g. CN23 form.', 'shipments', 'woocommerce-germanized' ),
				'value'       => $shipments_product->get_customs_description( 'edit' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_hs_code',
				'label'       => _x( 'HS-Code', 'shipments', 'woocommerce-germanized' ),
				'desc_tip'    => true,
				'description' => _x( 'The HS Code is a number assigned to every possible commodity that can be imported or exported from any country.', 'shipments', 'woocommerce-germanized' ),
				'value'       => $shipments_product->get_hs_code( 'edit' ),
			)
		);

		woocommerce_wp_select(
			array(
				'options'     => $countries,
				'id'          => '_manufacture_country',
				'label'       => _x( 'Country of manufacture', 'shipments', 'woocommerce-germanized' ),
				'desc_tip'    => true,
				'description' => _x( 'The country of manufacture is needed for customs of international shipping.', 'shipments', 'woocommerce-germanized' ),
				'value'       => $shipments_product->get_manufacture_country( 'edit' ),
			)
		);

		do_action( 'woocommerce_shiptastic_product_options', $shipments_product );
	}

	/**
	 * @param \WC_Product_Variation $variation
	 * @param $i
	 *
	 * @return void
	 */
	public static function save_variation_product( $variation, $i ) {
		if ( $shipments_product = wc_shiptastic_get_product( $variation ) ) {
			$shipments_product->set_shipping_length( isset( $_POST['variable_shipping_length'][ $i ] ) ? wc_clean( wp_unslash( $_POST['variable_shipping_length'][ $i ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$shipments_product->set_shipping_width( isset( $_POST['variable_shipping_width'][ $i ] ) ? wc_clean( wp_unslash( $_POST['variable_shipping_width'][ $i ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$shipments_product->set_shipping_height( isset( $_POST['variable_shipping_height'][ $i ] ) ? wc_clean( wp_unslash( $_POST['variable_shipping_height'][ $i ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function save_product( $product ) {
		$shipments_product = wc_shiptastic_get_product( $product );

		$shipments_product->set_hs_code( isset( $_POST['_hs_code'] ) ? wc_clean( wp_unslash( $_POST['_hs_code'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shipments_product->set_customs_description( isset( $_POST['_customs_description'] ) ? wc_clean( wp_unslash( $_POST['_customs_description'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shipments_product->set_manufacture_country( isset( $_POST['_manufacture_country'] ) ? wc_clean( wp_unslash( $_POST['_manufacture_country'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shipments_product->set_is_non_returnable( isset( $_POST['_is_non_returnable'] ) ? wc_clean( wp_unslash( $_POST['_is_non_returnable'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$shipments_product->set_shipping_length( isset( $_POST['_shipping_length'] ) ? wc_clean( wp_unslash( $_POST['_shipping_length'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shipments_product->set_shipping_width( isset( $_POST['_shipping_width'] ) ? wc_clean( wp_unslash( $_POST['_shipping_width'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shipments_product->set_shipping_height( isset( $_POST['_shipping_height'] ) ? wc_clean( wp_unslash( $_POST['_shipping_height'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		/**
		 * Remove legacy data upon saving in case it is not transmitted (e.g. DHL standalone plugin).
		 */
		if ( apply_filters( 'woocommerce_shiptastic_remove_legacy_customs_meta', isset( $_POST['_dhl_hs_code'] ) ? false : true, $product ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$product->delete_meta_data( '_dhl_hs_code' );
			$product->delete_meta_data( '_dhl_manufacture_country' );
		}

		do_action( 'woocommerce_shiptastic_save_product_options', $shipments_product );
	}

	public static function check_upload_dir() {
		$dir     = Package::get_upload_dir();
		$path    = $dir['basedir'];
		$dirname = basename( $path );

		if ( @is_dir( $dir['basedir'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return;
		}
		?>
		<div class="error">
			<p><?php printf( esc_html_x( 'Shipments upload directory missing. Please manually create the folder %s and make sure that it is writeable.', 'shipments', 'woocommerce-germanized' ), '<i>wp-content/uploads/' . esc_html( $dirname ) . '</i>' ); ?></p>
		</div>
		<?php
	}

	private static function get_setting_key_by_id( $settings, $id, $type = '' ) {
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

	protected static function add_settings_after( $settings, $id, $insert = array(), $type = '' ) {
		$key = self::get_setting_key_by_id( $settings, $id, $type );

		if ( is_numeric( $key ) ) {
			++$key;
			$settings = array_merge( array_merge( array_slice( $settings, 0, $key, true ), $insert ), array_slice( $settings, $key, count( $settings ) - 1, true ) );
		} else {
			$settings += $insert;
		}

		return $settings;
	}

	public static function register_settings( $integrations ) {
		$integrations[] = new Tabs\Tabs();

		return $integrations;
	}

	public static function register_endpoint_settings( $settings, $current_section ) {
		if ( '' === $current_section ) {
			$endpoints = array(
				array(
					'title'    => _x( 'View Shipments', 'shipments', 'woocommerce-germanized' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; View shipments" page.', 'shipments', 'woocommerce-germanized' ),
					'id'       => 'woocommerce_shiptastic_view_shipments_endpoint',
					'type'     => 'text',
					'default'  => 'view-shipments',
					'desc_tip' => true,
				),
				array(
					'title'    => _x( 'View shipment', 'shipments', 'woocommerce-germanized' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; View shipment" page.', 'shipments', 'woocommerce-germanized' ),
					'id'       => 'woocommerce_shiptastic_view_shipment_endpoint',
					'type'     => 'text',
					'default'  => 'view-shipment',
					'desc_tip' => true,
				),
				array(
					'title'    => _x( 'Add Return Shipment', 'shipments', 'woocommerce-germanized' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; Add return shipment" page.', 'shipments', 'woocommerce-germanized' ),
					'id'       => 'woocommerce_shiptastic_add_return_shipment_endpoint',
					'type'     => 'text',
					'default'  => 'add-return-shipment',
					'desc_tip' => true,
				),
			);

			$settings = self::add_settings_after( $settings, 'woocommerce_myaccount_downloads_endpoint', $endpoints );
		}

		return $settings;
	}

	public static function menu_return_count() {
		global $submenu;

		if ( isset( $submenu['woocommerce'] ) ) {

			/**
			 * Filter to adjust whether to include requested return count in admin menu or not.
			 *
			 * @param boolean $show_count Whether to show count or not.
			 *
			 * @package Vendidero/Shiptastic
			 */
			if ( apply_filters( 'woocommerce_shiptastic_include_requested_return_count_in_menu', true ) && current_user_can( 'edit_others_shop_orders' ) ) {
				$return_count = wc_stc_get_shipment_count( 'requested', 'return' );

				if ( $return_count ) {
					foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
						if ( 0 === strpos( $menu_item[0], _x( 'Returns', 'shipments', 'woocommerce-germanized' ) ) ) {
							$submenu['woocommerce'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . esc_attr( $return_count ) . '"><span class="requested-count">' . number_format_i18n( $return_count ) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							break;
						}
					}
				}
			}
		}
	}

	public static function get_admin_shipment_item_columns( $shipment ) {
		$item_columns = array(
			'name'     => array(
				'title' => _x( 'Item', 'shipments', 'woocommerce-germanized' ),
				'size'  => 6,
				'order' => 5,
			),
			'quantity' => array(
				'title' => _x( 'Quantity', 'shipments', 'woocommerce-germanized' ),
				'size'  => 3,
				'order' => 10,
			),
			'action'   => array(
				'title' => _x( 'Actions', 'shipments', 'woocommerce-germanized' ),
				'size'  => 3,
				'order' => 15,
			),
		);

		if ( 'return' === $shipment->get_type() ) {
			$item_columns['return_reason'] = array(
				'title' => _x( 'Reason', 'shipments', 'woocommerce-germanized' ),
				'size'  => 3,
				'order' => 7,
			);

			$item_columns['name']['size']     = 5;
			$item_columns['quantity']['size'] = 2;
			$item_columns['action']['size']   = 2;
		}

		uasort( $item_columns, array( __CLASS__, 'sort_shipment_item_columns' ) );

		/**
		 * Filter to adjust shipment item columns shown in admin view.
		 *
		 * @param array    $item_columns The columns available.
		 * @param Shipment $shipment The shipment.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_meta_box_shipment_item_columns', $item_columns, $shipment );
	}

	protected static function sort_shipment_item_columns( $a, $b ) {
		if ( $a['order'] === $b['order'] ) {
			return 0;
		}

		return ( $a['order'] < $b['order'] ) ? -1 : 1;
	}

	public static function save_packaging_list( $settings, $current_section ) {
		if ( '' !== $current_section ) {
			return;
		}

		$current_key_list         = array();
		$packaging_ids_after_save = array();

		foreach ( wc_stc_get_packaging_list() as $pack ) {
			$current_key_list[] = $pack->get_id();
		}

		if ( isset( $_POST['packaging'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$packaging_post  = wc_clean( wp_unslash( $_POST['packaging'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order           = 0;
			$available_types = array_keys( wc_stc_get_packaging_types() );

			foreach ( $packaging_post as $packaging ) {
				$packaging     = wc_clean( $packaging );
				$packaging_id  = isset( $packaging['packaging_id'] ) ? absint( $packaging['packaging_id'] ) : 0;
				$packaging_obj = wc_stc_get_packaging( $packaging_id );

				if ( $packaging_obj ) {
					$packaging_obj->set_props(
						array(
							'type'                        => ! in_array( $packaging['type'], $available_types, true ) ? 'cardboard' : $packaging['type'],
							'weight'                      => empty( $packaging['weight'] ) ? 0 : PackagingSettings::to_packaging_weight( $packaging['weight'], $packaging_obj ),
							'description'                 => empty( $packaging['description'] ) ? '' : $packaging['description'],
							'length'                      => empty( $packaging['length'] ) ? 0 : PackagingSettings::to_packaging_dimension( $packaging['length'], $packaging_obj ),
							'width'                       => empty( $packaging['width'] ) ? 0 : PackagingSettings::to_packaging_dimension( $packaging['width'], $packaging_obj ),
							'height'                      => empty( $packaging['height'] ) ? 0 : PackagingSettings::to_packaging_dimension( $packaging['height'], $packaging_obj ),
							'max_content_weight'          => empty( $packaging['max_content_weight'] ) ? 0 : PackagingSettings::to_packaging_weight( $packaging['max_content_weight'], $packaging_obj ),
							'available_shipping_provider' => empty( $packaging['available_shipping_provider'] ) ? '' : array_filter( (array) $packaging['available_shipping_provider'] ),
							'order'                       => ++$order,
						)
					);

					if ( empty( $packaging_obj->get_description() ) ) {
						if ( $packaging_obj->get_id() > 0 ) {
							$packaging_obj->delete( true );
							continue;
						} else {
							continue;
						}
					}

					$packaging_obj->save();
					$packaging_ids_after_save[] = $packaging_obj->get_id();
				}
			}
		}

		$to_delete = array_diff( $current_key_list, $packaging_ids_after_save );

		if ( ! empty( $to_delete ) ) {
			foreach ( $to_delete as $delete_id ) {
				if ( $packaging = wc_stc_get_packaging( $delete_id ) ) {
					$packaging->delete( true );
				}
			}
		}
	}

	public static function save_return_reasons() {
		$reasons = array();

		if ( isset( $_POST['shipment_return_reason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$reasons_post = wc_clean( wp_unslash( $_POST['shipment_return_reason'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order        = 0;

			foreach ( $reasons_post as $reason ) {
				$code        = isset( $reason['code'] ) ? $reason['code'] : '';
				$reason_text = isset( $reason['reason'] ) ? $reason['reason'] : '';

				if ( empty( $code ) ) {
					$code = sanitize_title( $reason_text );
				}

				if ( ! empty( $reason_text ) ) {
					$reasons[] = array(
						'order'  => ++$order,
						'code'   => $code,
						'reason' => $reason_text,
					);
				}
			}
		}
		// phpcs:enable

		update_option( 'woocommerce_shiptastic_return_reasons', $reasons );
	}

	public static function output_return_reasons_field( $value ) {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html_x( 'Return reasons', 'shipments', 'woocommerce-germanized' ); ?></th>
			<td class="forminp" id="shipment_return_reasons">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th style="width: 10ch;"><?php echo esc_html_x( 'Reason code', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'The reason code is used to identify the reason.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
							<th><?php echo esc_html_x( 'Reason', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Choose a reason text.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
						</tr>
						</thead>
						<tbody class="shipment_return_reasons">
						<?php
						$count = 0;
						foreach ( wc_stc_get_return_shipment_reasons() as $reason ) :
							?>
							<tr class="item reason">
								<td class="sort"></td>
								<td style="width: 10ch;"><input type="text" value="<?php echo esc_attr( wp_unslash( $reason->get_code() ) ); ?>" name="shipment_return_reason[<?php echo esc_attr( $count ); ?>][code]" /></td>
								<td><input type="text" value="<?php echo esc_attr( wp_unslash( $reason->get_reason() ) ); ?>" name="shipment_return_reason[<?php echo esc_attr( $count ); ?>][reason]" /></td>
							</tr>
							<?php
							++$count;
						endforeach;
						?>
						</tbody>
						<tfoot>
						<tr>
							<th colspan="7"><a href="#" class="add button"><?php echo esc_html_x( '+ Add reason', 'shipments', 'woocommerce-germanized' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected reason(s)', 'shipments', 'woocommerce-germanized' ); ?></a></th>
						</tr>
						<tr class="item template" style="display: none">
							<td class="sort"></td>
							<td style="width: 10ch;"><input type="text" value="" name="shipment_return_reason[size][code]" /></td>
							<td><input type="text" value="" name="shipment_return_reason[size][reason]" /></td>
						</tr>
						</tfoot>
					</table>
				</div>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Register this custom country field as the builtin country field misses
	 * custom attributes (e.g. for custom show/hide logic)
	 *
	 * @param array $value
	 *
	 * @return void
	 */
	public static function output_custom_country_select( $value ) {
		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Description handling.
		$field_description = \WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		$country_setting = (string) $value['value'];

		if ( strstr( $country_setting, ':' ) ) {
			$country_setting = explode( ':', $country_setting );
			$country         = current( $country_setting );
			$state           = end( $country_setting );
		} else {
			$country = $country_setting;
			$state   = '*';
		}
		?>
		<tr class="<?php echo esc_attr( $value['row_class'] ); ?>">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
			</th>
			<td class="forminp">
				<select name="<?php echo esc_attr( $value['field_name'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php echo esc_attr_x( 'Choose a country / region&hellip;', 'shipments', 'woocommerce-germanized' ); ?>" aria-label="<?php echo esc_attr_x( 'Country / Region', 'shipments', 'woocommerce-germanized' ); ?>" class="wc-enhanced-select" <?php echo implode( ' ', $custom_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
				</select> <?php echo wp_kses_post( $description ); ?>
			</td>
		</tr>
		<?php
	}

	public static function output_packaging_reports( $value ) {
		$reports = ReportHelper::get_reports();
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><label for="wc_shiptastic_create_packaging_report_year"><?php echo esc_html_x( 'Packaging Reports', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Generate summary reports which contain information about the amount of packaging material used for your shipments.', 'shipments', 'woocommerce-germanized' ) ); ?></label></th>
			<td class="forminp" id="packaging_reports_wrapper">
				<div class="wc-shiptastic-create-packaging-report submit">
					<select name="report_year" id="wc_shiptastic_create_packaging_report_year">
						<?php
						foreach ( array_reverse( range( (int) date( 'Y' ) - 2, (int) date( 'Y' ) ) ) as $year ) : // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							$start_day = date( 'Y-m-d', strtotime( $year . '-01-01' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							?>
							<option value="<?php echo esc_html( $start_day ); ?>"><?php echo esc_html( $year ); ?></option>
						<?php endforeach; ?>
					</select>

					<button class="button" type="submit" name="save" value="create_report"><?php echo esc_html_x( 'Create report', 'shipments', 'woocommerce-germanized' ); ?></button>
				</div>

				<?php if ( ! empty( $reports ) ) : ?>
					<table class="widefat packaging_reports_table" cellspacing="0">
						<thead>
						<tr>
							<th style="width: 30ch;"><?php echo esc_html_x( 'Report', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 20ch;"><?php echo esc_html_x( 'Start', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 20ch;"><?php echo esc_html_x( 'End', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Total weight', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Count', 'shipments', 'woocommerce-germanized' ); ?></th>
						</tr>
						</thead>
						<tbody class="">
							<?php foreach ( $reports as $report ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( $report->get_url() ); ?>" target="_blank"><?php echo esc_html( $report->get_title() ); ?></a> <span class="packaging-report-status status-<?php echo esc_attr( $report->get_status() ); ?>"><?php echo esc_html( ReportHelper::get_report_status_title( $report->get_status() ) ); ?></span></td>
									<td>
										<?php
										$show_date = $report->get_date_start()->date_i18n( wc_date_format() );

										printf(
											'<time datetime="%1$s" title="%2$s">%3$s</time>',
											esc_attr( $report->get_date_start()->date( 'c' ) ),
											esc_html( $report->get_date_start()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
											esc_html( $show_date )
										);
										?>
									</td>
									<td>
										<?php
										$show_date = $report->get_date_end()->date_i18n( wc_date_format() );

										printf(
											'<time datetime="%1$s" title="%2$s">%3$s</time>',
											esc_attr( $report->get_date_end()->date( 'c' ) ),
											esc_html( $report->get_date_end()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
											esc_html( $show_date )
										);
										?>
									</td>
									<td>
										<?php echo esc_html( wc_stc_format_shipment_weight( $report->get_total_weight(), wc_stc_get_packaging_weight_unit() ) ); ?>
									</td>
									<td>
										<?php echo esc_html( $report->get_total_count() ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function output_packaging_list( $value ) {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html_x( 'Available packaging', 'shipments', 'woocommerce-germanized' ); ?></th>
			<td class="forminp" id="packaging_list_wrapper">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Description', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'A description to help you identify the packaging.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
							<th style="width: 10ch;"><?php echo esc_html_x( 'Type', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 5ch;"><?php printf( esc_html_x( 'Weight (%s)', 'shipments', 'woocommerce-germanized' ), esc_html( wc_stc_get_packaging_weight_unit() ) ); ?> <?php echo wc_help_tip( _x( 'The weight of the packaging.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
							<th style="width: 15ch;"><?php printf( esc_html_x( 'Dimensions (LxWxH, %s)', 'shipments', 'woocommerce-germanized' ), esc_html( wc_stc_get_packaging_dimension_unit() ) ); ?></th>
							<th style="width: 5ch;"><?php printf( esc_html_x( 'Load capacity (%s)', 'shipments', 'woocommerce-germanized' ), esc_html( wc_stc_get_packaging_weight_unit() ) ); ?> <?php echo wc_help_tip( _x( 'The maximum weight this packaging can hold. Leave empty to not restrict maximum weight.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
							<th style="width: 5ch;"><?php echo esc_html_x( 'Actions', 'shipments', 'woocommerce-germanized' ); ?></th>
						</tr>
						</thead>
						<tbody class="packaging_list">
						<?php
						$count = 0;
						foreach ( wc_stc_get_packaging_list() as $packaging ) :
							?>
							<tr class="item">
								<td class="sort"></td>
								<td style="width: 15ch;">
									<input type="text" name="packaging[<?php echo esc_attr( $count ); ?>][description]" value="<?php echo esc_attr( wp_unslash( $packaging->get_description() ) ); ?>" />
									<input type="hidden" name="packaging[<?php echo esc_attr( $count ); ?>][packaging_id]" value="<?php echo esc_attr( $packaging->get_id() ); ?>" />
								</td>
								<td style="width: 10ch;">
									<select name="packaging[<?php echo esc_attr( $count ); ?>][type]">
										<?php foreach ( wc_stc_get_packaging_types() as $type => $type_title ) : ?>
											<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $packaging->get_type(), $type ); ?>><?php echo esc_html( $type_title ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td style="width: 5ch;">
									<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][weight]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_weight() ) ); ?>" placeholder="0" />
								</td>
								<td style="width: 15ch;">
									<span class="input-inner-wrap">
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][length]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_length() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Length', 'shipments', 'woocommerce-germanized' ) ); ?>" />
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][width]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_width() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Width', 'shipments', 'woocommerce-germanized' ) ); ?>" />
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][height]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_height() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Height', 'shipments', 'woocommerce-germanized' ) ); ?>" />
									</span>
								</td>
								<td style="width: 5ch;">
									<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][max_content_weight]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_max_content_weight() ) ); ?>" placeholder="0" />
								</td>
								<td class="actions" style="width: 5ch;">
									<a class="button wc-stc-shipment-action-button wc-stc-packaging-label-edit edit tip" aria-label="<?php echo esc_html_x( 'Edit packaging configuration', 'shipments', 'woocommerce-germanized' ); ?>" href="<?php echo esc_url( PackagingSettings::get_settings_url( $packaging->get_id() ) ); ?>"><?php echo esc_html_x( 'Edit packaging configuration', 'shipments', 'woocommerce-germanized' ); ?></a>
								</td>
							</tr>
							<?php
							++$count;
						endforeach;
						?>
						</tbody>
						<tfoot>
						<tr>
							<th colspan="7"><a href="#" class="add button"><?php echo esc_html_x( '+ Add packaging', 'shipments', 'woocommerce-germanized' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected packaging', 'shipments', 'woocommerce-germanized' ); ?></a></th>
						</tr>
						<tr class="template item" style="display: none">
							<td class="sort"></td>
							<td style="width: 15ch;"><input type="text" name="packaging[size][description]" value="" /></td>
							<td style="width: 10ch;">
								<select name="packaging[size][type]">
									<?php foreach ( wc_stc_get_packaging_types() as $type => $type_title ) : ?>
										<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_attr( $type_title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td style="width: 5ch;">
								<input class="wc_input_decimal" type="text" name="packaging[size][weight]" placeholder="0" />
							</td>
							<td style="width: 15ch;">
								<span class="input-inner-wrap">
									<input class="wc_input_decimal" type="text" name="packaging[size][length]" value="" placeholder="<?php echo esc_attr( _x( 'Length', 'shipments', 'woocommerce-germanized' ) ); ?>" />
									<input class="wc_input_decimal" type="text" name="packaging[size][width]" value="" placeholder="<?php echo esc_attr( _x( 'Width', 'shipments', 'woocommerce-germanized' ) ); ?>" />
									<input class="wc_input_decimal" type="text" name="packaging[size][height]" value="" placeholder="<?php echo esc_attr( _x( 'Height', 'shipments', 'woocommerce-germanized' ) ); ?>" />
								</span>
							</td>
							<td style="width: 5ch;">
								<input class="wc_input_decimal" type="text" name="packaging[size][max_content_weight]" placeholder="0" />
							</td>
							<td style="width: 5ch;"></td>
						</tr>
						</tfoot>
					</table>
				</div>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function register_screen_ids( $screen_ids ) {
		$screen_ids = array_merge( $screen_ids, self::get_core_screen_ids() );

		return $screen_ids;
	}

	public static function menu_highlight() {
		global $parent_file, $submenu_file;

		if ( isset( $_GET['page'] ) && in_array( wp_unslash( $_GET['page'] ), array( 'shipment-packaging', 'shipment-packaging-report' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$parent_file  = 'woocommerce'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu_file = 'wc-settings'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	public static function handle_order_bulk_actions( $redirect_to, $action, $ids ) {
		$ids           = apply_filters( 'woocommerce_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$changed       = 0;
		$report_action = '';

		if ( 'stc_create_shipments' === $action ) {
			foreach ( $ids as $id ) {
				$order         = wc_get_order( $id );
				$report_action = 'stc_created_shipments';

				if ( $order ) {
					Automation::create_shipments( $id );
					++$changed;
				}
			}
		}

		if ( $changed ) {
			$redirect_query_args = array(
				'post_type'   => 'shop_order',
				'bulk_action' => $report_action,
				'changed'     => $changed,
				'ids'         => join( ',', $ids ),
			);

			if ( Package::is_hpos_enabled() ) {
				unset( $redirect_query_args['post_type'] );
				$redirect_query_args['page'] = 'wc-orders';
			}

			$redirect_to = add_query_arg(
				$redirect_query_args,
				$redirect_to
			);

			return esc_url_raw( $redirect_to );
		} else {
			return $redirect_to;
		}
	}

	public static function define_order_bulk_actions( $actions ) {
		$actions['stc_create_shipments'] = _x( 'Create shipments', 'shipments', 'woocommerce-germanized' );

		return $actions;
	}

	public static function set_screen_option( $new_value, $option, $value ) {
		if ( in_array( $option, array( 'woocommerce_page_wc_stc_shipments_per_page', 'woocommerce_page_wc_stc_return_shipments_per_page' ), true ) ) {
			return absint( $value );
		}

		return $new_value;
	}

	public static function shipments_menu() {
		add_submenu_page( 'woocommerce', _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), 'edit_others_shop_orders', 'wc-stc-shipments', array( __CLASS__, 'shipments_page' ) );
		add_submenu_page( 'woocommerce', _x( 'Returns', 'shipments', 'woocommerce-germanized' ), _x( 'Returns', 'shipments', 'woocommerce-germanized' ), 'edit_others_shop_orders', 'wc-stc-return-shipments', array( __CLASS__, 'returns_page' ) );
	}

	/**
	 * @param Shipment $shipment
	 */
	public static function get_shipment_tracking_html( $shipment ) {
		$tracking_html = '';

		if ( $tracking_id = $shipment->get_tracking_id() ) {

			if ( $tracking_url = $shipment->get_tracking_url() ) {
				$tracking_html = '<a class="shipment-tracking-number" href="' . esc_url( $tracking_url ) . '" target="_blank">' . $tracking_id . '</a>';
			} else {
				$tracking_html = '<span class="shipment-tracking-number">' . $tracking_id . '</span>';
			}
		}

		return $tracking_html;
	}

	/**
	 * @param Table $table
	 */
	protected static function setup_table( $table ) {
		global $wp_list_table;

		$wp_list_table = $table; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$doaction      = $wp_list_table->current_action();

		if ( $doaction ) {
			check_admin_referer( 'bulk-shipments' );

			$pagenum     = $wp_list_table->get_pagenum();
			$parent_file = $wp_list_table->get_main_page();
			$sendback    = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

			if ( ! $sendback ) {
				$sendback = admin_url( $parent_file );
			}

			$sendback     = add_query_arg( 'paged', $pagenum, $sendback );
			$shipment_ids = array();

			if ( isset( $_REQUEST['ids'] ) ) {
				$shipment_ids = array_map( 'absint', explode( ',', wp_unslash( $_REQUEST['ids'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} elseif ( ! empty( $_REQUEST['shipment'] ) ) {
				$shipment_ids = array_map( 'absint', wp_unslash( $_REQUEST['shipment'] ) );
			}

			if ( ! empty( $shipment_ids ) ) {
				$sendback = $wp_list_table->handle_bulk_actions( $doaction, $shipment_ids, $sendback );
			}

			$sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'shipment' ), $sendback );

			wp_safe_redirect( esc_url_raw( $sendback ) );
			exit();

		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			exit;
		}

		$wp_list_table->set_bulk_notice();
		$wp_list_table->prepare_items();

		add_screen_option( 'per_page' );
	}

	public static function setup_shipments_table() {
		$table = new Table();

		self::setup_table( $table );
	}

	public static function setup_returns_table() {
		$table = new ReturnTable( array( 'type' => 'return' ) );

		self::setup_table( $table );
	}

	public static function shipments_page() {
		global $wp_list_table;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Shipments', 'shipments', 'woocommerce-germanized' ); ?></h1>
			<hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notice();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=wc-stc-shipments' ) ) );
			?>

			<?php $wp_list_table->views(); ?>

			<form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( _x( 'Search shipments', 'shipments', 'woocommerce-germanized' ), 'shipment' ); ?>

				<input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
				<input type="hidden" name="shipment_type" class="shipment_type" value="simple" />

				<input type="hidden" name="type" class="type_page" value="shipment" />
				<input type="hidden" name="page" value="wc-stc-shipments" />

				<?php $wp_list_table->display(); ?>
			</form>

			<div id="ajax-response"></div>
			<br class="clear" />
		</div>
		<?php
	}

	public static function returns_page() {
		global $wp_list_table;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Returns', 'shipments', 'woocommerce-germanized' ); ?></h1>
			<hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notice();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=wc-stc-shipments' ) ) );
			?>

			<?php $wp_list_table->views(); ?>

			<form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( _x( 'Search returns', 'shipments', 'woocommerce-germanized' ), 'shipment' ); ?>

				<input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
				<input type="hidden" name="shipment_type" class="shipment_type" value="return" />

				<input type="hidden" name="type" class="type_page" value="shipment" />
				<input type="hidden" name="page" value="wc-stc-return-shipments" />

				<?php $wp_list_table->display(); ?>
			</form>

			<div id="ajax-response"></div>
			<br class="clear" />
		</div>
		<?php
	}

	public static function add_meta_boxes() {
		$order_type_screen_ids = array_merge( wc_get_order_types( 'order-meta-boxes' ), array( self::get_order_screen_id() ) );

		// Orders.
		foreach ( $order_type_screen_ids as $type ) {
			add_meta_box( 'woocommerce-stc-order-shipments', _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), array( MetaBox::class, 'output' ), $type, 'normal', 'high' );
		}
	}

	public static function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'woocommerce_shiptastic_admin', Package::get_assets_url( 'static/admin-styles.css' ), array( 'woocommerce_admin_styles' ), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_screen_ids(), true ) ) {
			wp_enqueue_style( 'woocommerce_shiptastic_admin' );
		}
	}

	public static function admin_scripts() {
		global $post, $theorder;

		$screen               = get_current_screen();
		$screen_id            = $screen ? $screen->id : '';
		$post_id              = isset( $post->ID ) ? $post->ID : '';
		$order_or_post_object = $post;

		if ( ( $theorder instanceof \WC_Order ) && self::is_order_meta_box_screen( $screen_id ) ) {
			$order_or_post_object = $theorder;
		}

		wp_register_script( 'wc-shiptastic-admin', Package::get_assets_url( 'static/admin.js' ), array( 'jquery', 'woocommerce_admin' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-settings', Package::get_assets_url( '/static/admin-settings.js' ), array( 'wc-shiptastic-admin' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		wp_register_script( 'wc-shiptastic-admin-shipment-modal', Package::get_assets_url( 'static/admin-shipment-modal.js' ), array( 'wc-shiptastic-admin', 'wc-backbone-modal' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-shipment-label', Package::get_assets_url( 'static/admin-shipment-label.js' ), array( 'wc-shiptastic-admin' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-shipment', Package::get_assets_url( 'static/admin-shipment.js' ), array( 'wc-shiptastic-admin-shipment-modal', 'wc-shiptastic-admin-shipment-label' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-shipments', Package::get_assets_url( 'static/admin-shipments.js' ), array( 'wc-admin-order-meta-boxes', 'wc-shiptastic-admin-shipment' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-shipments-table', Package::get_assets_url( 'static/admin-shipments-table.js' ), array( 'wc-shiptastic-admin', 'wc-shiptastic-admin-shipment-modal', 'wc-shiptastic-admin-shipment-label' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		wp_register_script( 'wc-shiptastic-admin-shipping-rules', Package::get_assets_url( '/static/admin-shipping-rules.js' ), array( 'wc-shiptastic-admin', 'jquery-ui-sortable', 'wp-util', 'underscore', 'backbone' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-packaging', Package::get_assets_url( '/static/admin-packaging.js' ), array( 'wc-shiptastic-admin-settings' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-shipping-providers', Package::get_assets_url( 'static/admin-shipping-providers.js' ), array( 'wc-shiptastic-admin', 'jquery-ui-sortable' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-shiptastic-admin-shipping-provider-method', Package::get_assets_url( 'static/admin-shipping-provider-method.js' ), array( 'wc-shiptastic-admin-settings' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		// Orders.
		if ( self::is_order_meta_box_screen( $screen_id ) ) {
			wp_enqueue_script( 'wc-shiptastic-admin-shipments' );
			wp_enqueue_script( 'wc-shiptastic-admin-shipment' );

			$order_order_post_id = $post_id;

			if ( self::is_order_meta_box_screen( $screen_id ) && isset( $order_or_post_object ) && is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'get_post_or_order_id' ) ) ) {
				$order_order_post_id = \Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_order_id( $order_or_post_object );
			}

			wp_localize_script(
				'wc-shiptastic-admin-shipments',
				'wc_shiptastic_admin_shipments_params',
				array(
					'ajax_url'                           => admin_url( 'admin-ajax.php' ),
					'edit_shipments_nonce'               => wp_create_nonce( 'edit-shipments' ),
					'order_id'                           => $order_order_post_id,
					'shipment_locked_excluded_fields'    => array( 'status' ),
					'i18n_remove_shipment_notice'        => _x( 'Do you really want to delete the shipment?', 'shipments', 'woocommerce-germanized' ),
					'remove_label_nonce'                 => wp_create_nonce( 'remove-shipment-label' ),
					'edit_label_nonce'                   => wp_create_nonce( 'edit-shipment-label' ),
					'send_return_notification_nonce'     => wp_create_nonce( 'send-return-shipment-notification' ),
					'refresh_packaging_nonce'            => wp_create_nonce( 'refresh-shipment-packaging' ),
					'confirm_return_request_nonce'       => wp_create_nonce( 'confirm-return-request' ),
					'add_return_shipment_load_nonce'     => wp_create_nonce( 'add-return-shipment-load' ),
					'add_return_shipment_submit_nonce'   => wp_create_nonce( 'add-return-shipment-submit' ),
					'add_shipment_item_load_nonce'       => wp_create_nonce( 'add-shipment-item-load' ),
					'add_shipment_item_submit_nonce'     => wp_create_nonce( 'add-shipment-item-submit' ),
					'create_shipment_label_load_nonce'   => wp_create_nonce( 'create-shipment-label-load' ),
					'create_shipment_label_submit_nonce' => wp_create_nonce( 'create-shipment-label-submit' ),
					'i18n_remove_label_notice'           => _x( 'Do you really want to delete the label?', 'shipments', 'woocommerce-germanized' ),
					'i18n_save_before_create'            => _x( 'Please save the shipment first', 'shipments', 'woocommerce-germanized' ),
				)
			);
		}

		// Settings
		if ( 'woocommerce_page_shipment-packaging' === $screen_id ) {
			wp_enqueue_script( 'wc-shiptastic-admin-packaging' );
		}

		// Table
		if ( 'woocommerce_page_wc-stc-shipments' === $screen_id || 'woocommerce_page_wc-stc-return-shipments' === $screen_id ) {
			wp_enqueue_script( 'wc-shiptastic-admin-shipments-table' );

			$bulk_actions = array();

			foreach ( self::get_bulk_action_handlers() as $handler ) {
				$bulk_actions[ sanitize_key( $handler->get_action() ) ] = array(
					'title' => $handler->get_title(),
					'nonce' => wp_create_nonce( $handler->get_nonce_name() ),
				);
			}

			wp_localize_script(
				'wc-shiptastic-admin-shipments-table',
				'wc_shiptastic_admin_shipments_table_params',
				array(
					'ajax_url'                           => admin_url( 'admin-ajax.php' ),
					'search_orders_nonce'                => wp_create_nonce( 'search-orders' ),
					'search_shipping_provider_nonce'     => wp_create_nonce( 'search-shipping-provider' ),
					'bulk_actions'                       => $bulk_actions,
					'create_shipment_label_load_nonce'   => wp_create_nonce( 'create-shipment-label-load' ),
					'create_shipment_label_submit_nonce' => wp_create_nonce( 'create-shipment-label-submit' ),
					'preview_shipment_load_nonce'        => wp_create_nonce( 'preview-shipment-load' ),
				)
			);
		}

		wp_localize_script(
			'wc-shiptastic-admin',
			'wc_shiptastic_admin_params',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		wp_localize_script(
			'wc-shiptastic-admin-shipment-modal',
			'wc_shiptastic_admin_shipment_modal_params',
			array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'i18n_modal_close' => _x( 'Close', 'shipments-close-modal', 'woocommerce-germanized' ),
				'load_nonce'       => wp_create_nonce( 'load-modal' ),
				'submit_nonce'     => wp_create_nonce( 'submit-modal' ),
			)
		);

		wp_localize_script(
			'wc-shiptastic-admin-settings',
			'wc_shiptastic_admin_settings_params',
			self::get_admin_settings_params()
		);

		// Shipping provider settings
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shiptastic-shipping_provider' === $_GET['tab'] && empty( $_GET['provider'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_script( 'wc-shiptastic-admin-shipping-providers' );

			wp_localize_script(
				'wc-shiptastic-admin-shipping-providers',
				'wc_shiptastic_admin_shipping_providers_params',
				array(
					'ajax_url'                             => admin_url( 'admin-ajax.php' ),
					'edit_shipping_providers_nonce'        => wp_create_nonce( 'edit-shipping-providers' ),
					'remove_shipping_provider_nonce'       => wp_create_nonce( 'remove-shipping-provider' ),
					'sort_shipping_provider_nonce'         => wp_create_nonce( 'sort-shipping-provider' ),
					'install_extension_nonce'              => wp_create_nonce( 'install-shipping-provider-extension' ),
					'i18n_remove_shipping_provider_notice' => _x( 'Do you really want to delete the shipping provider? Some of your existing shipments might be linked to that provider and might need adjustments.', 'shipments', 'woocommerce-germanized' ),
				)
			);
		}

		// Shipping provider method
		if ( self::is_shipping_settings_request() ) {
			/**
			 * Older third-party shipping methods may not support instance-settings and will have their settings
			 * output in a separate section under Settings > Shipping.
			 */
			if ( ( isset( $_GET['zone_id'] ) || isset( $_GET['instance_id'] ) ) || ( isset( $_GET['section'] ) && ! MethodHelper::method_is_excluded( wc_clean( wp_unslash( $_GET['section'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_script( 'wc-shiptastic-admin-shipping-provider-method' );
				$providers = array_filter( array_keys( wc_stc_get_shipping_provider_select() ) );

				wp_localize_script(
					'wc-shiptastic-admin-shipping-provider-method',
					'wc_shiptastic_admin_shipping_provider_method_params',
					array(
						'shipping_providers' => $providers,
					)
				);
			}
		}

		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && strstr( wc_clean( wp_unslash( $_GET['tab'] ) ), 'shipments' ) && isset( $_GET['tutorial'] ) && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab_name  = wc_clean( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab_clean = str_replace( 'shipments-', '', $tab_name );

			Tutorial::setup_pointers_for_settings( $tab_clean );
		}
	}

	protected static function is_shipping_settings_request() {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		$screen_id = $screen ? $screen->id : '';

		return 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shipping' === $_GET['tab']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	private static function get_admin_settings_params() {
		$params = array(
			'packaging_types' => wc_stc_get_packaging_types(),
		);

		if ( self::is_shipping_settings_request() ) {
			$params['clean_input_callback'] = 'shiptastic.admin.shipping_provider_method.getCleanInputId';
		}

		return $params;
	}

	/**
	 * @return BulkActionHandler[] $handler
	 */
	public static function get_bulk_action_handlers() {
		if ( is_null( self::$bulk_handlers ) ) {
			self::$bulk_handlers = array();

			/**
			 * Filter to register new BulkActionHandler for certain Shipment bulk actions.
			 *
			 * @param array $handlers Array containing key => classname.
			 *
			 * @package Vendidero/Shiptastic
			 */
			$handlers = apply_filters(
				'woocommerce_shiptastic_table_bulk_action_handlers',
				array(
					'labels' => '\Vendidero\Shiptastic\Admin\BulkLabel',
				)
			);

			foreach ( $handlers as $key => $handler ) {
				if ( is_a( $handler, 'Vendidero\Shiptastic\Admin\BulkActionHandler' ) ) {
					self::$bulk_handlers[ $key ] = $handler;
				} else {
					self::$bulk_handlers[ $key ] = new $handler();
				}
			}
		}

		return self::$bulk_handlers;
	}

	public static function get_bulk_action_handler( $action ) {
		$handlers = self::get_bulk_action_handlers();

		return array_key_exists( $action, $handlers ) ? $handlers[ $action ] : false;
	}

	/**
	 * Helper function to determine whether the current screen is an order edit screen.
	 *
	 * @param string $screen_id Screen ID.
	 *
	 * @return bool Whether the current screen is an order edit screen.
	 */
	protected static function is_order_meta_box_screen( $screen_id ) {
		return in_array( str_replace( 'edit-', '', $screen_id ), self::get_order_screen_ids(), true );
	}

	public static function get_order_screen_id() {
		return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	protected static function get_order_screen_ids() {
		$screen_ids = array();

		foreach ( wc_get_order_types() as $type ) {
			$screen_ids[] = $type;
			$screen_ids[] = 'edit-' . $type;
		}

		$screen_ids[] = self::get_order_screen_id();

		return array_filter( $screen_ids );
	}

	public static function get_core_screen_ids() {
		$screen_ids = array(
			'woocommerce_page_wc-stc-shipments',
			'woocommerce_page_wc-stc-return-shipments',
			'woocommerce_page_shipment-packaging',
			'woocommerce_page_shipment-packaging-report',
		);

		return $screen_ids;
	}

	public static function get_screen_ids() {
		$other_screen_ids = array(
			'woocommerce_page_wc-settings',
			'product',
		);

		return array_merge( self::get_core_screen_ids(), self::get_order_screen_ids(), $other_screen_ids );
	}
}
