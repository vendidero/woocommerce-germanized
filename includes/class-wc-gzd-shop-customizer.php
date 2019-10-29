<?php
/**
 * Adds options to the customizer for WooCommerce.
 *
 * @version 3.3.0
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Shop_Customizer class.
 */
class WC_GZD_Shop_Customizer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'remove_settings' ), 50 );
	}

	/**
	 * Add settings to the customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function remove_settings( $wp_customize ) {
		$settings = array(
			'woocommerce_checkout_terms_and_conditions_checkbox_text',
			'woocommerce_checkout_privacy_policy_text',
		);

		foreach ( $settings as $setting ) {
			if ( $wp_customize->get_setting( $setting ) ) {
				$wp_customize->remove_setting( $setting );
			}
			if ( $wp_customize->get_control( $setting ) ) {
				$wp_customize->remove_control( $setting );
			}
		}
	}
}

new WC_GZD_Shop_Customizer();
