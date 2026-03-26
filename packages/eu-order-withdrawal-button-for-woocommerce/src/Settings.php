<?php

namespace Vendidero\OrderWithdrawalButton;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Settings {

	public static function get_sections() {
		return array(
			'' => _x( 'General', 'owb', 'woocommerce-germanized' ),
		);
	}

	public static function get_description() {
		return sprintf( _x( 'Configure your EU-compliant order withdrawal button. <a href="https://vendidero.com/implementing-legally-compliant-eu-order-withdrawal-button-in-woocommerce" target="_blank">Need help?</a>', 'owb', 'woocommerce-germanized' ) );
	}

	public static function get_help_url() {
		return 'https://vendidero.com/implementing-legally-compliant-eu-order-withdrawal-button-in-woocommerce';
	}

	public static function get_settings( $current_section = '' ) {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'owb_options',
				'desc'  => Package::is_integration() ? '' : self::get_description(),
			),

			array(
				'title'    => _x( 'Withdrawal page', 'owb', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_withdraw_from_contract_page_id',
				'type'     => 'single_select_page_with_search',
				'class'    => 'wc-page-search',
				'desc_tip' => _x( 'This page should contain your withdrawal form shortcode.', 'owb', 'woocommerce-germanized' ),
				'args'     => array(
					'exclude' => array(),
				),
				'default'  => '',
				'css'      => 'min-width:300px;',
				'autoload' => false,
			),

			array(
				'title'    => _x( 'Embed in footer', 'owb', 'woocommerce-germanized' ),
				'desc'     => _x( 'Embed the withdrawal button directly in the footer. Disable this option if you plan to embed the page manually.', 'owb', 'woocommerce-germanized' ),
				'id'       => 'eu_owb_woocommerce_enable_embed',
				'type'     => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'  => 'yes',
				'autoload' => true,
			),

			array(
				'title'    => _x( 'Partial withdrawals', 'owb', 'woocommerce-germanized' ),
				'desc'     => _x( 'Allow your customers to select which order items to withdraw.', 'owb', 'woocommerce-germanized' ),
				'id'       => 'eu_owb_woocommerce_enable_partial_withdrawals',
				'type'     => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'  => 'yes',
				'autoload' => false,
			),

			array(
				'title'    => _x( 'Non-refundable', 'owb', 'woocommerce-germanized' ),
				'desc'     => _x( 'Choose certain product types to exclude from being withdrawn.', 'owb', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'       => 'eu_owb_woocommerce_excluded_product_types',
				'class'    => 'wc-enhanced-select',
				'type'     => 'multiselect',
				'options'  => self::get_product_type_options(),
				'default'  => array( 'virtual' ),
				'autoload' => false,
			),

			array(
				'title'    => _x( 'Withdrawal period', 'owb', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Choose the number of days, starting with the orders\' completed date, to accept withdrawals for orders.', 'owb', 'woocommerce-germanized' ),
				'desc'     => _x( 'Days', 'owb', 'woocommerce-germanized' ),
				'css'      => 'max-width: 60px;',
				'type'     => 'number',
				'id'       => 'eu_owb_woocommerce_number_of_days_to_withdraw',
				'default'  => '14',
				'autoload' => false,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'owb_options',
			),
		);

		return $settings;
	}

	protected static function get_product_type_options() {
		$product_types        = wc_get_product_types();
		$product_type_options = array_merge(
			array(
				'virtual'      => _x( 'Virtual Product', 'owb', 'woocommerce-germanized' ),
				'downloadable' => _x( 'Downloadable Product', 'owb', 'woocommerce-germanized' ),
			),
			$product_types
		);

		return apply_filters( 'eu_owb_woocommerce_product_type_options', $product_type_options );
	}

	public static function before_save() {
	}

	public static function after_save() {
	}

	public static function get_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=owb' );
	}
}
