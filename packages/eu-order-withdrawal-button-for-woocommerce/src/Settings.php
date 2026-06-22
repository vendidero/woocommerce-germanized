<?php

namespace Vendidero\OrderWithdrawalButton;

use Vendidero\OrderWithdrawalButton\Admin\Admin;

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
		return _x( 'Configure your EU-compliant order withdrawal button. <a target="_blank" href="https://vendidero.com/implementing-legally-compliant-eu-order-withdrawal-button-in-woocommerce">Learn more</a>', 'owb', 'woocommerce-germanized' ) . ' <a class="page-title-action" href="' . esc_url( \Vendidero\OrderWithdrawalButton\Package::get_withdrawals_url() ) . '">' . _x( 'Withdrawals', 'owb', 'woocommerce-germanized' ) . '</a>';
	}

	public static function get_help_url() {
		return 'https://vendidero.com/implementing-legally-compliant-eu-order-withdrawal-button-in-woocommerce';
	}

	public static function get_settings( $current_section = '' ) {
		$default_email = get_option( 'admin_email' );
		$woo_mail      = sanitize_email( get_option( 'woocommerce_email_from_address' ) );
		$page_status   = Admin::get_current_withdrawal_page_status();

		if ( $woo_mail ) {
			$default_email = $woo_mail;
		}

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
				'desc'     => '<a href="' . esc_url( $page_status['edit_url'] ) . '" class="withdrawal-page-status withdrawal-page-' . esc_attr( $page_status['status'] ) . '">' . ( 'valid' === $page_status['status'] ? esc_html_x( 'Valid', 'owb', 'woocommerce-germanized' ) : sprintf( esc_html_x( 'Invalid: %1$s', 'owb', 'woocommerce-germanized' ), esc_html( $page_status['invalid_reason'] ) ) ) . '</a>',
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
				'title'             => _x( 'Display everywhere', 'owb', 'woocommerce-germanized' ),
				'desc'              => _x( 'Display the button on every page, not just on shop-related pages.', 'owb', 'woocommerce-germanized' ),
				'id'                => 'eu_owb_woocommerce_embed_everywhere',
				'type'              => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'           => 'no',
				'autoload'          => true,
				'custom_attributes' => array(
					'data-show_if_eu_owb_woocommerce_enable_embed' => '',
				),
			),

			array(
				'title'    => _x( 'Partial withdrawals', 'owb', 'woocommerce-germanized' ),
				'desc'     => _x( 'Allow customers and verified guests to select which order items to withdraw.', 'owb', 'woocommerce-germanized' ),
				'id'       => 'eu_owb_woocommerce_enable_partial_withdrawals',
				'type'     => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'  => 'yes',
				'autoload' => false,
			),

			array(
				'title'    => _x( 'Mandatory fields', 'owb', 'woocommerce-germanized' ),
				'id'       => 'eu_owb_woocommerce_mandatory_fields',
				'desc_tip' => _x( 'Select fields that should be mandatory while submitting a new withdrawal', 'owb', 'woocommerce-germanized' ),
				'type'     => 'multiselect',
				'class'    => 'wc-enhanced-select',
				'css'      => 'width: 400px;',
				'options'  => array(
					'first_name'             => _x( 'First name', 'owb', 'woocommerce-germanized' ),
					'last_name'              => _x( 'Last name', 'owb', 'woocommerce-germanized' ),
					'order_number'           => _x( 'Contract Identification', 'owb', 'woocommerce-germanized' ),
					'additional_information' => _x( 'Additional information', 'owb', 'woocommerce-germanized' ),
				),
				'default'  => array(
					'order_number',
					'first_name',
					'last_name',
				),
				'autoload' => false,
			),

			array(
				'title'    => _x( 'Additional information', 'owb', 'woocommerce-germanized' ),
				'desc'     => _x( 'Allow guests to enter additional information in a separate text field.', 'owb', 'woocommerce-germanized' ),
				'id'       => 'eu_owb_woocommerce_enable_additional_information',
				'type'     => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'  => 'no',
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
				'title'     => _x( 'Withdrawal period', 'owb', 'woocommerce-germanized' ),
				'desc_tip'  => _x( 'Choose the number of days, starting with the orders\' completed date, to accept withdrawals for orders.', 'owb', 'woocommerce-germanized' ),
				'desc'      => _x( 'Days', 'owb', 'woocommerce-germanized' ) . '<div class="eu-owb-settings-additional-desc">' . sprintf( _x( 'Keep in mind that the withdrawal period does not begin until the customer receives the order. If necessary add a buffer period depending on your shipping process. Use 0 to make withdrawals available at any time.', 'owb', 'woocommerce-germanized' ), esc_url( get_admin_url( null, 'admin.php?page=wc-orders&unverified_withdrawals=yes' ) ) ) . '</div>',
				'css'       => 'max-width: 60px;',
				'row_class' => 'withdrawal-period',
				'type'      => 'number',
				'id'        => 'eu_owb_woocommerce_number_of_days_to_withdraw',
				'default'   => '14',
				'autoload'  => false,
			),

			array(
				'title'    => _x( 'Unverified requests', 'owb', 'woocommerce-germanized' ),
				'desc'     => _x( 'Do not adjust order status for unverified requests.', 'owb', 'woocommerce-germanized' ) . '<div class="eu-owb-settings-additional-desc">' . _x( 'For some requests, the email address differs from the original stored within the order. Make sure these requests do not automatically set the pending withdrawal order status.', 'owb', 'woocommerce-germanized' ) . '</div>',
				'id'       => 'eu_owb_woocommerce_separately_store_unverified_withdrawal_requests',
				'type'     => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'  => 'yes',
				'autoload' => false,
			),

			array(
				'title'        => _x( 'Contact email', 'owb', 'woocommerce-germanized' ),
				'desc_tip'     => _x( 'Please provide an email address where customers can contact you if they have any issues.', 'owb', 'woocommerce-germanized' ),
				'id'           => 'eu_owb_woocommerce_contact_email_address',
				'type'         => 'email',
				'placeholder'  => $default_email,
				'default'      => '',
				'skip_install' => true,
				'autoload'     => false,
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
		return Package::is_integration() ? admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=withdrawal_button' ) : admin_url( 'admin.php?page=wc-settings&tab=owb' );
	}
}
