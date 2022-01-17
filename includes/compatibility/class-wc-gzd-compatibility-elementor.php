<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Compatibility_Elementor extends WC_GZD_Compatibility {

	protected static $readd_elementor_filter = false;

	public static function get_name() {
		return 'Elementor';
	}

	public static function get_path() {
		return 'elementor/elementor.php';
	}

	/**
	 * Disable elementor HTML wrap for plain content output (e.g. within emails).
	 *
	 * @return void
	 */
	public function load() {
		add_action( 'woocommerce_gzd_before_get_post_plain_content', function() {
			if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->frontend && is_callable( array( \Elementor\Plugin::$instance->frontend, 'remove_content_filter' ) ) ) {
				if ( has_filter( 'the_content', array( \Elementor\Plugin::$instance->frontend, 'apply_builder_in_content' ) ) ) {
					\Elementor\Plugin::$instance->frontend->remove_content_filter();

					self::$readd_elementor_filter = true;
				}
			}
		} );

		add_action( 'woocommerce_gzd_after_get_post_plain_content', function() {
			if ( self::$readd_elementor_filter && class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->frontend && is_callable( array( \Elementor\Plugin::$instance->frontend, 'add_content_filter' ) ) ) {
				\Elementor\Plugin::$instance->frontend->add_content_filter();

				self::$readd_elementor_filter = false;
			}
		} );
	}
}