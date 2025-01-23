<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Vendidero\Germanized\Shipments\Admin\Tabs\Tab;
use Vendidero\Germanized\Shipments\Admin\Tabs\Tabs;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packaging\ReportHelper;
use Vendidero\Germanized\Shipments\Packaging\ReportQueue;
use Vendidero\Germanized\Shipments\SecretBox;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Settings {

	public static function get_main_breadcrumb() {
		$current_tab = isset( $_GET['tab'] ) ? wc_clean( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$breadcrumb  = apply_filters(
			'woocommerce_gzd_shipments_settings_main_breadcrumb',
			array(
				array(
					'class' => 'main',
					'href'  => 'shipments' === $current_tab ? '' : admin_url( 'admin.php?page=wc-settings&tab=shipments' ),
					'title' => _x( 'Shipments', 'shipments-settings-page-title', 'woocommerce-germanized' ),
				),
			)
		);

		return $breadcrumb;
	}

	/**
	* @param string $name
	*
	* @return bool|Tab
	 */
	public static function get_tab( $name ) {
		$setting_pages = \WC_Admin_Settings::get_settings_pages();
		$setting_page  = false;

		foreach ( $setting_pages as $page ) {
			if ( is_a( $page, '\Vendidero\Germanized\Shipments\Admin\Tabs\Tabs' ) ) {
				$setting_page = $page;
				break;
			}
		}

		if ( ! $setting_page ) {
			$setting_page = new Tabs();
		}

		return $setting_page->get_tab_by_name( $name );
	}

	public static function get_settings_url( $tab = '', $section = '' ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=shipments' );

		if ( ! empty( $tab ) ) {
			$url = add_query_arg( array( 'tab' => 'shipments-' . $tab ), $url );
		}

		if ( ! empty( $section ) ) {
			$url = add_query_arg( array( 'section' => $section ), $url );
		}

		return esc_url_raw( $url );
	}

	public static function get_sanitized_settings( $settings, $data = null ) {
		if ( is_null( $data ) ) {
			$data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( empty( $data ) ) {
			return false;
		}

		$settings_to_save = array();

		// Loop options and get values to save.
		foreach ( $settings as $option ) {
			if ( ! isset( $option['id'] ) || empty( $option['id'] ) || ! isset( $option['type'] ) || in_array( $option['type'], array( 'title', 'sectionend' ), true ) || ( isset( $option['is_option'] ) && false === $option['is_option'] ) ) {
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
					$value = is_null( $raw_value ) ? '' : addslashes( $raw_value );
					$value = trim( $value );

					$encrypted = SecretBox::encrypt( $value );

					if ( ! is_wp_error( $encrypted ) ) {
						$value = $encrypted;
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

	public static function render_label_fields( $settings, $shipment, $do_echo = false ) {
		$missing_div_closes = 0;
		ob_start();
		foreach ( $settings as $setting ) {
			$setting = wp_parse_args(
				$setting,
				array(
					'id'                => '',
					'type'              => 'text',
					'custom_attributes' => array(),
				)
			);

			if ( has_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['id']}" ) ) {
				do_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['id']}", $setting, $shipment );
			} elseif ( 'select' === $setting['type'] ) {
				woocommerce_wp_select( $setting );
			} elseif ( 'multiselect' === $setting['type'] ) {
				$setting['class']             = 'select short wc-enhanced-select';
				$setting['custom_attributes'] = array_merge( $setting['custom_attributes'], array( 'multiple' => 'multiple' ) );

				if ( ! strstr( $setting['id'], '[]' ) ) {
					$setting['name'] = $setting['id'] . '[]';
				}

				woocommerce_wp_select( $setting );
			} elseif ( 'checkbox' === $setting['type'] ) {
				$field_name  = isset( $setting['name'] ) ? $setting['name'] : $setting['id'];
				$field_value = isset( $setting['value'] ) ? $setting['value'] : 'no';

				// Use a placeholder checkbox to force transmitting non-checked checkboxes with a no value to make sure default props are overridden.
				echo ( ( 'yes' === $field_value ) ? '<input type="hidden" value="no" name="' . esc_attr( $field_name ) . '" />' : '' );
				woocommerce_wp_checkbox( $setting );
			} elseif ( 'textarea' === $setting['type'] ) {
				woocommerce_wp_textarea_input( $setting );
			} elseif ( 'text' === $setting['type'] ) {
				woocommerce_wp_text_input( $setting );
			} elseif ( 'date' === $setting['type'] ) {
				$setting['class'] = 'datepicker';
				$setting['type']  = 'date';

				woocommerce_wp_text_input( $setting );
			} elseif ( 'number' === $setting['type'] ) {
				woocommerce_wp_text_input( $setting );
			} elseif ( 'services_start' === $setting['type'] ) {
				$hide_default = isset( $setting['hide_default'] ) ? wc_string_to_bool( $setting['hide_default'] ) : false;
				++$missing_div_closes;
				?>
				<p class="show-services-trigger show-more-trigger">
					<a href="#" class="show-more show-further-services <?php echo ( ! $hide_default ? 'hide-default' : '' ); ?>">
						<span class="dashicons dashicons-plus"></span> <?php echo esc_html_x( 'More services', 'shipments', 'woocommerce-germanized' ); ?>
					</a>
					<a class="show-fewer show-fewer-services <?php echo ( $hide_default ? 'hide-default' : '' ); ?>" href="#">
						<span class="dashicons dashicons-minus"></span> <?php echo esc_html_x( 'Fewer services', 'shipments', 'woocommerce-germanized' ); ?>
					</a>
				</p>
				<div class="<?php echo ( $hide_default ? 'hide-default' : '' ); ?> show-more-wrapper show-if-further-services" data-trigger=".show-services-trigger">
				<?php
			} elseif ( 'columns' === $setting['type'] ) {
				++$missing_div_closes;
				?>
				<div class="columns <?php echo esc_attr( isset( $setting['class'] ) ? $setting['class'] : '' ); ?>">
				<?php
			} elseif ( 'wrapper' === $setting['type'] ) {
				++$missing_div_closes;
				?>
					<div class="wc-gzd-shipment-label-wrapper" id="wc-gzd-shipment-label-wrapper-<?php echo esc_attr( $setting['id'] ); ?>">
				<?php
			} elseif ( in_array( $setting['type'], array( 'columns_end', 'services_end', 'wrapper_end' ), true ) ) {
				--$missing_div_closes;
				?>
					</div>
				<?php
			} else {
				do_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['type']}", $setting, $shipment );
			}
		}

		if ( $missing_div_closes > 0 ) {
			while ( $missing_div_closes > 0 ) {
				--$missing_div_closes;
				echo '</div>';
			}
		}

		$html = ob_get_clean();

		if ( ! $do_echo ) {
			return $html;
		} else {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
