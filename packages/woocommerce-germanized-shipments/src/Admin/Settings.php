<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Exception;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShippingProvider\Helper;
use WC_Admin_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Settings {

	public static function get_section_description( $section ) {
		return '';
	}

	public static function get_pointers( $section ) {
		$pointers = array();
		$next_url = admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider&tutorial=yes' );

		if ( '' === $section ) {
			$pointers = array(
				'pointers' => array(
					'menu'             => array(
						'target'       => '.wc-gzd-settings-breadcrumb .page-title-action:last',
						'next'         => 'default',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Manage shipments', 'shipments', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'To view all your existing shipments in a list you might follow this link or click on the shipments link within the WooCommerce sub-menu.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'default'          => array(
						'target'       => '#woocommerce_gzd_shipments_notify_enable-toggle',
						'next'         => 'auto',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'E-Mail Notification', 'shipments', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'By enabling this option customers receive an email notification as soon as a shipment is marked as shipped.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'auto'          => array(
						'target'       => '#woocommerce_gzd_shipments_auto_enable-toggle',
						'next'         => 'returns',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Automation', 'shipments', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Decide whether you want to automatically create shipments to orders reaching a specific status. You can always adjust your shipments by manually editing the shipment within the edit order screen.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'returns'          => array(
						'target'       => '#shipments_return_options-description',
						'next'         => '',
						'next_url'     => $next_url,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Returns', 'shipments', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . sprintf( _x( 'Germanized can help you to minimize manual work while handling customer returns. Learn more about returns within our %s.', 'shipments', 'woocommerce-germanized' ), '<a href="https://vendidero.de/dokument/retouren-konfigurieren-und-verwalten" target="_blank">' . _x( 'documentation', 'shipments', 'woocommerce-germanized' ) .'</a>' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'top',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}

	protected static function get_general_settings() {

		$statuses = array_diff_key( wc_gzd_get_shipment_statuses(), array_flip( array( 'gzd-requested' ) ) );

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'shipments_options' ),

			array(
				'title' 	        => _x( 'Notify', 'shipments', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Notify customers about new shipments.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Notify customers by email as soon as a shipment is marked as shipped. %s the notification email.', 'shipments', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_gzd_email_customer_shipment' ) . '" target="_blank">' . _x( 'Manage', 'shipments notification', 'woocommerce-germanized' ) .'</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_shipments_notify_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Default provider', 'shipments', 'woocommerce-germanized' ),
				'desc_tip' 		    => _x( 'Select a default shipping provider which will be selected by default in case no provider could be determined automatically.', 'shipments', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_shipments_default_shipping_provider',
				'default'	        => '',
				'type'              => 'select',
				'options'           => wc_gzd_get_shipping_provider_select(),
				'class'             => 'wc-enhanced-select',
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_options' ),

			array( 'title' => _x( 'Automation', 'shipments', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipments_auto_options' ),

			array(
				'title' 	        => _x( 'Enable', 'shipments', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Automatically create shipments for orders.', 'shipments', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Order statuses', 'shipments', 'woocommerce-germanized' ),
				'desc_tip' 		    => _x( 'Create shipments as soon as the order reaches one of the following status(es).', 'shipments', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_statuses',
				'default'	        => array( 'wc-processing', 'wc-on-hold' ),
				'class' 	        => 'wc-enhanced-select-nostd',
				'options'           => wc_get_order_statuses(),
				'type'              => 'multiselect',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_auto_enable' => '',
					'data-placeholder' => _x( 'On new order creation', 'shipments', 'woocommerce-germanized' )
				),
			),

			array(
				'title' 	        => _x( 'Default status', 'shipments', 'woocommerce-germanized' ),
				'desc_tip' 		    => _x( 'Choose a default status for the automatically created shipment.', 'shipments', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_default_status',
				'default'	        => 'gzd-processing',
				'class' 	        => 'wc-enhanced-select',
				'options'           => $statuses,
				'type'              => 'select',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_auto_enable' => '',
				),
			),

			array(
				'title' 	        => _x( 'Update status', 'shipments', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Mark order as completed after order is fully shipped.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'This option will automatically update the order status to completed as soon as all required shipments have been marked as shipped.', 'shipments', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_shipments_auto_order_shipped_completed_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Mark as shipped', 'shipments', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Mark shipments as shipped after order completion.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x( 'This option will automatically update contained shipments to shipped (if possible, e.g. not yet delivered) as soon as the order was marked as completed.', 'shipments', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_shipments_auto_order_completed_shipped_enable',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_auto_options' ),

			array( 'title' => _x( 'Returns', 'shipments', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipments_return_options', 'desc' => sprintf( _x( 'Returns can be added manually by the shop manager or by the customer. Decide what suits you best by turning customer-added returns on or off in your %s.', 'shipments', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider' ) . '">' . _x( 'shipping provider settings', 'shipments', 'woocommerce-germanized' ) . '</a>' ) ),

			array(
				'type' => 'shipment_return_reasons',
			),

			array(
				'title'             => _x( 'Days to return', 'shipments', 'woocommerce-germanized' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'In case one of your %s supports returns added by customers you might want to limit the number of days a customer is allowed to add returns to an order. The days are counted starting with the date the order was shipped, completed or created (by checking for existance in this order).', 'shipments', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider' ) . '">' . _x( 'shipping providers', 'shipments', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'css'               => 'max-width: 60px;',
				'type'              => 'number',
				'id' 		        => 'woocommerce_gzd_shipments_customer_return_open_days',
				'default'           => '14',
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_return_options' ),

			array( 'title' => _x( 'Customer Account', 'shipments', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipments_customer_options' ),

			array(
				'title' 	        => _x( 'List', 'shipments', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'List shipments on customer account order screen.', 'shipments', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_shipments_customer_account_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_customer_options' ),
		);

		return $settings;
	}

	protected static function get_address_settings() {
		$settings = array(
            array( 'title' => _x( 'Shipper Address', 'shipments', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipments_shipper_address' ),

			array(
				'title'             => _x( 'First Name', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_first_name',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Last Name', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_last_name',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Company', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_company',
				'default'           => get_bloginfo( 'name' ),
			),

			array(
				'title'             => _x( 'Address 1', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_address_1',
				'default'           => get_option( 'woocommerce_store_address' ),
			),

			array(
				'title'             => _x( 'Address 2', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_address_2',
				'default'           => get_option( 'woocommerce_store_address_2' ),
			),

			array(
				'title'             => _x( 'City', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_city',
				'default'           => get_option( 'woocommerce_store_city' ),
			),

			array(
				'title'             => _x( 'Country / State', 'shipments', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_shipments_shipper_address_country',
				'default'           => get_option( 'woocommerce_default_country' ),
				'type'              => 'single_select_country',
				'desc_tip'          => true,
			),

			array(
				'title'             => _x( 'Postcode', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_postcode',
				'default'           => get_option( 'woocommerce_store_postcode' ),
			),

			array(
				'title'             => _x( 'Phone', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_phone',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Email', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_email',
				'default'           => get_option( 'woocommerce_email_from_address' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_shipper_address' ),

			array( 'title' => _x( 'Return Address', 'shipments', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipments_return_address' ),

			array(
				'title'             => _x( 'First Name', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_first_name',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_first_name' )
			),

			array(
				'title'             => _x( 'Last Name', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_last_name',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_last_name' )
			),

			array(
				'title'             => _x( 'Company', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_company',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_company' )
			),

			array(
				'title'             => _x( 'Address 1', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_address_1',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_address_1' )
			),

			array(
				'title'             => _x( 'Address 2', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_address_2',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_address_2' )
			),

			array(
				'title'             => _x( 'City', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_city',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_city' )
			),

			array(
				'title'             => _x( 'Country / State', 'shipments', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_shipments_return_address_country',
				'default'           => '',
				'type'              => 'single_select_country',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_country' ),
				'desc_tip'          => true,
			),

			array(
				'title'             => _x( 'Postcode', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_postcode',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_postcode' )
			),

			array(
				'title'             => _x( 'Phone', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_shipper_address_phone',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_phone' )
			),

			array(
				'title'             => _x( 'Email', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_email',
				'default'           => '',
				'placeholder'       => get_option( 'woocommerce_gzd_shipments_shipper_address_email' )
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_return_address' ),
		);

		return $settings;
	}

	protected static function get_packaging_settings() {
		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'packaging_options' ),

			array(
				'type' => 'packaging_list',
			),

			array(
				'title' 	        => _x( 'Default packaging', 'shipments', 'woocommerce-germanized' ),
				'desc_tip' 		    => _x( 'Choose a packaging which serves as fallback or default in case no suitable packaging could be matched for a certain shipment.', 'shipments', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_shipments_default_packaging',
				'default'	        => '',
				'type'              => 'select',
				'options'           => wc_gzd_get_packaging_select(),
				'class'             => 'wc-enhanced-select',
			),

			array( 'type' => 'sectionend', 'id' => 'packaging_options' ),
		);

		return $settings;
	}

	public static function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = self::get_general_settings();
		} elseif ( 'packaging' === $current_section ) {
			$settings = self::get_packaging_settings();
		} elseif ( 'address' === $current_section ) {
			$settings = self::get_address_settings();
		}

		return $settings;
	}

	public static function get_additional_breadcrumb_items( $breadcrumb ) {
		return $breadcrumb;
	}

	public static function get_sections() {
		return array(
			''          => _x( 'General', 'shipments', 'woocommerce-germanized' ),
			'packaging' => _x( 'Packaging', 'shipments', 'woocommerce-germanized' ),
			'address'   => _x( 'Addresses', 'shipments', 'woocommerce-germanized' ),
		);
	}

	public static function get_sanitized_settings( $settings, $data = null ) {
		if ( is_null( $data ) ) {
			$data = $_POST; // WPCS: input var okay, CSRF ok.
		}

		if ( empty( $data ) ) {
			return false;
		}

		$settings_to_save = array();

		// Loop options and get values to save.
		foreach ( $settings as $option ) {

			if ( ! isset( $option['id'] ) || empty( $option['id'] ) || ! isset( $option['type'] ) || in_array( $option['type'], array( 'title', 'sectionend' ) ) || ( isset( $option['is_option'] ) && false === $option['is_option'] ) ) {
				continue;
			}

			$option_key = $option['id'];
			$raw_value  = isset( $data[ $option_key ] ) ? wp_unslash( $data[ $option_key ] ) : null;

			// Format the value based on option type.
			switch ( $option['type'] ) {
				case 'checkbox':
					$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
					break;
				case 'textarea':
					$value = wp_kses_post( trim( $raw_value ) );
					break;
				case 'password':
					$value     = is_null( $raw_value ) ? '' : addslashes( $raw_value );
					$value     = trim( $value );

					if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
					    $encrypted = \WC_GZD_Secret_Box_Helper::encrypt( $value );

                        if ( ! is_wp_error( $encrypted ) ) {
                            $value = $encrypted;
                        }
					}
					break;
				case 'multiselect':
				case 'multi_select_countries':
					$value = array_filter( array_map( 'wc_clean', (array) $raw_value ) );
					break;
				case 'image_width':
					$value = array();
					if ( isset( $raw_value['width'] ) ) {
						$value['width']  = wc_clean( $raw_value['width'] );
						$value['height'] = wc_clean( $raw_value['height'] );
						$value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
					} else {
						$value['width']  = $option['default']['width'];
						$value['height'] = $option['default']['height'];
						$value['crop']   = $option['default']['crop'];
					}
					break;
				case 'select':
					$allowed_values = empty( $option['options'] ) ? array() : array_map( 'strval', array_keys( $option['options'] ) );
					if ( empty( $option['default'] ) && empty( $allowed_values ) ) {
						$value = null;
						break;
					}
					$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
					$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
					break;
				case 'relative_date_selector':
					$value = wc_parse_relative_date_option( $raw_value );
					break;
				default:
					$value = wc_clean( $raw_value );
					break;
			}

			/**
			 * Sanitize the value of an option.
			 *
			 * @since 2.4.0
			 */
			$value = apply_filters( 'woocommerce_admin_settings_sanitize_option', $value, $option, $raw_value );

			$settings_to_save[ $option_key ] = $value;
		}

		return $settings_to_save;
	}

	public static function render_label_fields( $settings, $shipment, $echo = false ) {
		ob_start();
		foreach( $settings as $setting ) {
			$setting = wp_parse_args( $setting, array( 'id' => '', 'type' => 'text' ) );

			if ( has_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['id']}" ) ) {
				do_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['id']}", $setting, $shipment );
			} elseif ( 'select' === $setting['type'] ) {
				woocommerce_wp_select( $setting );
			} elseif( 'checkbox' === $setting['type'] ) {
				woocommerce_wp_checkbox( $setting );
			} elseif( 'textarea' === $setting['type'] ) {
				woocommerce_wp_textarea_input( $setting );
			} elseif( 'text' === $setting['type'] ) {
				woocommerce_wp_text_input( $setting );
			} elseif( 'services_start' === $setting['type'] ) {
			    $hide_default = isset( $setting['hide_default'] ) ? wc_string_to_bool( $setting['hide_default'] ) : false;
		        $missing_div_closes++;
			    ?>
	            <p class="show-services-trigger">
	                <a href="#" class="show-further-services <?php echo ( ! $hide_default ? 'hide-default' : '' ); ?>">
	                    <span class="dashicons dashicons-plus"></span> <?php _ex(  'More services', 'shipments', 'woocommerce-germanized' ); ?>
	                </a>
	                <a class="show-fewer-services <?php echo ( $hide_default ? 'hide-default' : '' ); ?>" href="#">
	                    <span class="dashicons dashicons-minus"></span> <?php _ex(  'Fewer services', 'shipments', 'woocommerce-germanized' ); ?>
	                </a>
	            </p>
	            <div class="<?php echo ( $hide_default ? 'hide-default' : '' ); ?> show-if-further-services">
	            <?php
			} elseif( 'columns' === $setting['type'] ) {
			    if ( $missing_div_closes > 0 ) {
			        echo '</div>';
				    $missing_div_closes--;
	            }

	            $missing_div_closes++;
	            ?>
	            <div class="columns">
	            <?php
	        } elseif( 'wrapper' === $setting['type'] ) {
	            if ( $missing_div_closes > 0 ) {
		            echo '</div>';
		            $missing_div_closes--;
	            }

	            $missing_div_closes++;
	            ?>
	                <div class="wc-gzd-shipment-label-wrapper" id="wc-gzd-shipment-label-wrapper-<?php echo esc_attr( $setting['id'] ); ?>">
	            <?php
	        } elseif( in_array( $setting['type'], array( 'columns_end', 'services_end', 'wrapper_end' ) ) ) {
	            $missing_div_closes--;
	            ?>
	                </div>
	            <?php
	        } else {
				do_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['type']}", $setting, $shipment );
			}
		}

		if ( $missing_div_closes > 0 ) {
			while( $missing_div_closes > 0 ) {
				$missing_div_closes--;
				echo '</div>';
			}
		}

		$html = ob_get_clean();

		if ( ! $echo ) {
			return $html;
		} else {
			echo $html;
		}
	}
}
