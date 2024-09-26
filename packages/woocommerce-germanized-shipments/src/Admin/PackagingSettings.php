<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packaging;

defined( 'ABSPATH' ) || exit;

class PackagingSettings {

	/**
	 * @param Packaging $packaging
	 *
	 * @return array
	 */
	public static function get_tabs( $packaging ) {
		$tabs = array(
			'' => _x( 'General', 'shipments-packaging-tab', 'woocommerce-germanized' ),
		);

		foreach ( $packaging->get_available_shipping_provider() as $provider_name ) {
			if ( $provider = wc_gzd_get_shipping_provider( $provider_name ) ) {
				if ( $provider->is_activated() && ! $provider->is_manual_integration() ) {
					$tabs[ $provider_name ] = $provider->get_title();
				}
			}
		}

		return $tabs;
	}

	/**
	 * @param Packaging $packaging
	 * @param string $tab
	 *
	 * @return array
	 */
	public static function get_sections( $packaging, $tab = '' ) {
		$sections = array();

		if ( ! empty( $tab ) ) {
			if ( $current_provider = wc_gzd_get_shipping_provider( $tab ) ) {
				foreach ( array_keys( $current_provider->get_packaging_label_settings( $packaging ) ) as $shipment_type ) {
					$sections[ $shipment_type ] = wc_gzd_get_shipment_label_title( $shipment_type, true );
				}
			}
		}

		return $sections;
	}

	public static function get_settings_url( $packaging_id, $tab = '', $section = '' ) {
		$args = array( 'packaging' => absint( $packaging_id ) );

		if ( ! empty( $tab ) ) {
			$args['tab'] = $tab;
		}

		if ( ! empty( $section ) ) {
			$args['section'] = $section;
		}

		return esc_url_raw( add_query_arg( $args, admin_url( 'admin.php?page=shipment-packaging' ) ) );
	}

	/**
	 * @param Packaging $packaging
	 * @param string $tab
	 * @param string $section
	 *
	 * @return array
	 */
	public static function get_settings( $packaging, $tab, $section = '' ) {
		$settings = array();
		$tab      = empty( $tab ) ? 'general' : $tab;

		if ( is_callable( array( __CLASS__, "get_{$tab}_settings" ) ) ) {
			$settings = call_user_func_array(
				array( __CLASS__, "get_{$tab}_settings" ),
				array(
					'packaging' => $packaging,
					'section'   => $section,
				)
			);
		} elseif ( $current_provider = wc_gzd_get_shipping_provider( $tab ) ) {
			$all_settings          = $current_provider->get_packaging_label_settings( $packaging );
			$current_shipment_type = empty( $section ) ? 'simple' : $section;
			$settings              = isset( $all_settings[ $current_shipment_type ] ) ? $all_settings[ $current_shipment_type ] : array();
		}

		return $settings;
	}

	public static function is_provider( $tab ) {
		$tab         = empty( $tab ) ? 'general' : $tab;
		$is_provider = false;

		if ( ! is_callable( array( __CLASS__, "get_{$tab}_settings" ) ) ) {
			if ( wc_gzd_get_shipping_provider( $tab ) ) {
				$is_provider = true;
			}
		}

		return $is_provider;
	}

	/**
	 * @param Packaging $packaging
	 * @param string $section
	 *
	 * @return array
	 */
	public static function get_general_settings( $packaging, $section = '' ) {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'packaging_general_options',
			),
			array(
				'title'   => _x( 'Description', 'shipments', 'woocommerce-germanized' ),
				'type'    => 'text',
				'id'      => 'description',
				'default' => '',
				'value'   => $packaging->get_description(),
			),
			array(
				'title'   => _x( 'Type', 'shipments', 'woocommerce-germanized' ),
				'type'    => 'select',
				'id'      => 'type',
				'default' => '',
				'options' => wc_gzd_get_packaging_types(),
				'value'   => $packaging->get_type(),
			),
			array(
				'title'             => _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'multiselect',
				'id'                => 'available_shipping_provider',
				'desc_tip'          => _x( 'Choose which shipping provider support the packaging.', 'shipments', 'woocommerce-germanized' ),
				'class'             => 'wc-enhanced-select',
				'default'           => array(),
				'options'           => wc_gzd_get_shipping_provider_select( false ),
				'value'             => $packaging->get_available_shipping_provider( 'edit' ),
				'custom_attributes' => array(
					'data-placeholder' => _x( 'All shipping provider', 'shipments', 'woocommerce-germanized' ),
				),
			),
			array(
				'title'             => _x( 'Shipping Classes', 'shipments', 'woocommerce-germanized' ),
				'type'              => 'multiselect',
				'desc_tip'          => _x( 'You may restrict the packaging to only support items with certain shipping class(es).', 'shipments', 'woocommerce-germanized' ),
				'id'                => 'available_shipping_classes',
				'class'             => 'wc-enhanced-select',
				'default'           => array(),
				'options'           => Package::get_shipping_classes(),
				/**
				 * Woo explicitly casts option values to strings before comparing.
				 * @see \WC_Admin_Settings::output_fields()
				 */
				'value'             => array_map( 'strval', $packaging->get_available_shipping_classes( 'edit' ) ),
				'custom_attributes' => array(
					'data-placeholder' => _x( 'All shipping classes', 'shipments', 'woocommerce-germanized' ),
				),
			),
			array(
				'title'     => _x( 'Weight', 'shipments', 'woocommerce-germanized' ),
				'type'      => 'text',
				'css'       => 'max-width: 60px;',
				'class'     => 'wc_input_decimal',
				'row_class' => 'with-suffix',
				'desc_tip'  => _x( 'The weight of the packaging.', 'shipments', 'woocommerce-germanized' ),
				'id'        => 'weight',
				'desc'      => wc_gzd_get_packaging_weight_unit(),
				'default'   => '',
				'value'     => wc_format_localized_decimal( $packaging->get_weight() ),
			),
			array(
				'title' => _x( 'Dimensions', 'shipments', 'woocommerce-germanized' ),
				'type'  => 'dimensions',
				'id'    => 'dimensions',
				'desc'  => wc_gzd_get_packaging_dimension_unit(),
				'value' => array(
					'length' => wc_format_localized_decimal( $packaging->get_length() ),
					'width'  => wc_format_localized_decimal( $packaging->get_width() ),
					'height' => wc_format_localized_decimal( $packaging->get_height() ),
				),
			),
			array(
				'title'       => _x( 'Inner Dimensions', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'dimensions',
				'id'          => 'inner_dimensions',
				'desc'        => wc_gzd_get_packaging_dimension_unit(),
				'value'       => array(
					'length' => $packaging->get_inner_length( 'edit' ) ? wc_format_localized_decimal( $packaging->get_inner_length() ) : '',
					'width'  => $packaging->get_inner_width( 'edit' ) ? wc_format_localized_decimal( $packaging->get_inner_width() ) : '',
					'height' => $packaging->get_inner_height( 'edit' ) ? wc_format_localized_decimal( $packaging->get_inner_height() ) : '',
				),
				'placeholder' => array(
					'length' => wc_format_localized_decimal( $packaging->get_length() ),
					'width'  => wc_format_localized_decimal( $packaging->get_width() ),
					'height' => wc_format_localized_decimal( $packaging->get_height() ),
				),
			),
			array(
				'title'     => _x( 'Load Capacity', 'shipments', 'woocommerce-germanized' ),
				'type'      => 'text',
				'css'       => 'max-width: 60px;',
				'class'     => 'wc_input_decimal',
				'row_class' => 'with-suffix',
				'desc_tip'  => _x( 'The maximum weight this packaging can hold. Leave empty to not restrict maximum weight.', 'shipments', 'woocommerce-germanized' ),
				'id'        => 'max_content_weight',
				'desc'      => wc_gzd_get_packaging_weight_unit(),
				'default'   => '',
				'value'     => wc_format_localized_decimal( $packaging->get_max_content_weight() ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'packaging_general_options',
			),
		);
	}

	/**
	 * @param $weight
	 * @param Packaging $packaging
	 *
	 * @return string
	 */
	public static function to_packaging_weight( $weight, $packaging ) {
		return wc_get_weight( (float) wc_format_decimal( $weight ), $packaging->get_weight_unit(), wc_gzd_get_packaging_weight_unit() );
	}

	/**
	 * @param $dimension
	 * @param Packaging $packaging
	 *
	 * @return string
	 */
	public static function to_packaging_dimension( $dimension, $packaging ) {
		return wc_get_dimension( (float) wc_format_decimal( $dimension ), $packaging->get_dimension_unit(), wc_gzd_get_packaging_dimension_unit() );
	}

	/**
	 * @param Packaging $packaging
	 * @param string $tab
	 * @param string $section
	 *
	 * @return void
	 */
	public static function save_settings( $packaging, $tab, $section = '' ) {
		$settings = self::get_settings( $packaging, $tab, $section );

		if ( ! empty( $settings ) ) {
			if ( self::is_provider( $tab ) && ( $shipping_provider = wc_gzd_get_shipping_provider( $tab ) ) ) {
				$current_shipment_type = empty( $section ) ? 'simple' : $section;

				$packaging->reset_configuration_sets(
					array(
						'shipping_provider_name' => $shipping_provider->get_name(),
						'shipment_type'          => $current_shipment_type,
					)
				);

				add_filter(
					'woocommerce_admin_settings_sanitize_option',
					function ( $value, $setting, $raw_value ) use ( $packaging ) {
						$setting_id = $setting['id'];
						$args       = $packaging->get_configuration_set_args_by_id( $setting_id );
						$value      = wc_clean( $value );

						if ( 'override' === $args['setting_name'] && wc_string_to_bool( $value ) ) {
							if ( $config_set = $packaging->get_or_create_configuration_set( $args ) ) {
								$config_set->update_setting( $setting_id, $value );
							}
						} elseif ( $config_set = $packaging->get_configuration_set( $args ) ) {
							$config_set->update_setting( $setting_id, $value );
						}
					},
					1001,
					3
				);

				foreach ( $settings as $location => $inner_settings ) {
					\WC_Admin_Settings::save_fields( $inner_settings );
				}

				remove_all_filters( 'woocommerce_admin_settings_sanitize_option', 1001 );
			} else {
				add_filter(
					'pre_update_option',
					function ( $value, $option, $old_value ) use ( $packaging ) {
						if ( is_callable( array( $packaging, "set_{$option}" ) ) ) {
							$setter = "set_{$option}";

							/**
							 * Convert dimensions/weight to packaging unit
							 */
							if ( ! empty( $value ) && in_array( $option, array( 'length', 'width', 'height', 'inner_length', 'inner_width', 'inner_height' ), true ) ) {
								$value = self::to_packaging_dimension( $value, $packaging );
							} elseif ( ! empty( $value ) && in_array( $option, array( 'weight', 'max_content_weight' ), true ) ) {
								$value = self::to_packaging_weight( $value, $packaging );
							}

							$packaging->$setter( $value );
						}

						return $old_value;
					},
					1001,
					3
				);

				\WC_Admin_Settings::save_fields( $settings );

				remove_all_filters( 'pre_update_option', 1001 );
			}
		}

		$packaging->save();
	}
}
