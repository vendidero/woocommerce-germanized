<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized B2B settings (pro feature placeholder).
 *
 * @class        WC_GZD_Settings_Tab_B2B
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_B2B extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Sell B2B: customer group & tiered prices, a separate registration with manual review and more.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'B2B', 'woocommerce-germanized' ) . ' <span class="wc-gzd-new">' . __( 'new', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'b2b';
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/woocommerce-germanized/b2b-einrichten';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return $this->get_pro_preview_settings(
			array(
				'title'        => __( 'Sell to business customers with Germanized Pro', 'woocommerce-germanized' ),
				'desc'         => __( 'Turn your shop into a B2B or hybrid shop and offer business customers their own prices and conditions.', 'woocommerce-germanized' ),
				'features'     => array(
					__( 'Customer groups with individual prices, discounts and tiered prices', 'woocommerce-germanized' ),
					__( 'Separate B2B registration with manual review', 'woocommerce-germanized' ),
					__( 'Restrict products, payment and shipping methods to certain customer groups', 'woocommerce-germanized' ),
					__( 'Net price display and minimum order values per customer group', 'woocommerce-germanized' ),
				),
				'href'         => 'https://vendidero.de/woocommerce-germanized/features#b2b',
				'placeholders' => array(
					array(
						'title' => 'General',
						'type'  => 'title',
					),
					array(
						'title' => 'Enable',
						'type'  => 'toggle',
						'value' => 'yes',
					),
					array(
						'title' => 'B2B shop',
						'type'  => 'toggle',
						'value' => 'no',
						'desc'  => 'Sell to business customers only.',
					),
					array(
						'title' => 'Tier quantity',
						'type'  => 'select',
						'value' => 'Per product',
					),
					array(
						'title' => 'Discount base',
						'type'  => 'select',
						'value' => 'Regular price',
					),
					array(
						'title' => 'Tier table',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Show a table containing tiered prices on the product page.',
					),
					array(
						'title' => 'Savings',
						'type'  => 'toggle',
						'value' => 'yes',
					),
					array(
						'title' => 'Tier messages',
						'type'  => 'toggle',
						'value' => 'no',
					),
					array(
						'title' => 'Hidden price text',
						'type'  => 'text',
						'value' => 'Please login to see prices',
					),
					array(
						'title' => 'Registration',
						'type'  => 'title',
					),
					array(
						'title' => 'Registration page',
						'type'  => 'select',
						'value' => 'Business registration',
					),
					array(
						'title' => 'Default customer group',
						'type'  => 'select',
						'value' => 'Business customer',
					),
					array(
						'title' => 'Manual review',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Review and approve new business accounts manually.',
					),
				),
			)
		);
	}

	public function is_enabled() {
		return false;
	}
}
