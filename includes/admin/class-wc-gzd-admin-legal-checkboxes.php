<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_GZD_Admin_Legal_Checkboxes {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_filter(
			'woocommerce_gzd_legal_checkbox_age_verification_settings',
			array(
				$this,
				'additional_age_verification_fields',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_gzd_legal_checkbox_download_settings',
			array(
				$this,
				'additional_download_fields',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_gzd_legal_checkbox_parcel_delivery_settings',
			array(
				$this,
				'additional_parcel_delivery_fields',
			),
			10,
			2
		);
	}

	public function additional_age_verification_fields( $fields, $checkbox ) {
		$fields = WC_GZD_Admin::instance()->insert_setting_after(
			$fields,
			$checkbox->get_form_field_id( 'is_enabled' ),
			array(
				array(
					'title'    => __( 'Global minimum age', 'woocommerce-germanized' ),
					'id'       => $checkbox->get_form_field_id( 'min_age' ),
					'type'     => 'select',
					'options'  => wc_gzd_get_age_verification_min_ages_select(),
					'desc_tip' => __( 'Choose a global minimum age necessary to buy your products. Can be overridden by product specific settings.', 'woocommerce-germanized' ),
				),
			)
		);

		return $fields;
	}

	public function additional_download_fields( $fields, $checkbox ) {
		$product_types = wc_get_product_types();

		$digital_type_options = array_merge(
			array(
				'downloadable' => __( 'Downloadable Product', 'woocommerce-germanized' ),
				'virtual'      => __( 'Virtual Product', 'woocommerce-germanized' ),
				'service'      => __( 'Service', 'woocommerce-germanized' ),
			),
			$product_types
		);

		$fields = array_merge(
			$fields,
			array(
				array(
					'title'    => __( 'Digital Product types', 'woocommerce-germanized' ),
					'desc'     => __( 'Select product types for which the loss of the right of withdrawal notice is shown. Product types like "simple product" may be redundant because they include virtual and downloadable products.', 'woocommerce-germanized' ),
					'desc_tip' => true,
					'id'       => $checkbox->get_form_field_id( 'types' ),
					'default'  => array( 'downloadable' ),
					'class'    => 'chosen_select',
					'options'  => $digital_type_options,
					'type'     => 'multiselect',
				),
			)
		);

		return $fields;
	}

	public function additional_parcel_delivery_fields( $fields, $checkbox ) {
		$shipping_methods_options = WC_GZD_Admin::instance()->get_shipping_method_instances_options();

		$fields = array_merge(
			$fields,
			array(
				array(
					'title'    => __( 'Show checkbox', 'woocommerce-germanized' ),
					'desc_tip' => __( 'Choose whether you like to always show the parcel delivery checkbox or do only show for certain shipping methods.', 'woocommerce-germanized' ),
					'id'       => $checkbox->get_form_field_id( 'show_special' ),
					'default'  => 'always',
					'class'    => 'chosen_select',
					'options'  => array(
						'shipping_methods' => __( 'For certain shipping methods.', 'woocommerce-germanized' ),
						'always'           => __( 'Always show.', 'woocommerce-germanized' ),
					),
					'type'     => 'select',
				),
				array(
					'title'             => __( 'Shipping Methods', 'woocommerce-germanized' ),
					'desc'              => __( 'Select shipping methods which are applicable for the Opt-In Checkbox.', 'woocommerce-germanized' ),
					'desc_tip'          => true,
					'id'                => $checkbox->get_form_field_id( 'show_shipping_methods' ),
					'default'           => array(),
					'class'             => 'chosen_select',
					'custom_attributes' => array(
						'data-show_if_' . $checkbox->get_form_field_id( 'show_special' ) => 'shipping_methods',
					),
					'options'           => $shipping_methods_options,
					'type'              => 'multiselect',
				),
			)
		);

		return $fields;
	}

	public function disable_include( $enable, $current_section ) {
		if ( 'checkboxes' === $current_section ) {
			return false;
		}

		return $enable;
	}
}

WC_GZD_Admin_Legal_Checkboxes::instance();
