<?php

use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Compatibility_Elementor_Pro extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'Elementor Pro';
	}

	public static function get_path() {
		return 'elementor-pro/elementor-pro.php';
	}

	public function load() {
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'init_widgets' ), 10 );
	}

	public function init_widgets() {
		if ( ! class_exists( 'ElementorPro\Modules\Woocommerce\Widgets\Products_Base' ) ) {
			return;
		}

		include_once 'elementor/widgets/abstact-class-wc-gzd-elementor-widget.php';

		$widgets = array(
			'WC_GZD_Elementor_Widget_Product_Tax_Notice',
			'WC_GZD_Elementor_Widget_Product_Shipping_Notice',
			'WC_GZD_Elementor_Widget_Product_Unit_Price',
			'WC_GZD_Elementor_Widget_Product_Units',
			'WC_GZD_Elementor_Widget_Product_Delivery_Time',
		);

		$widget_manager = Plugin::$instance->widgets_manager;

		foreach ( $widgets as $widget ) {
			$classname = 'class-' . str_replace( '_', '-', strtolower( $widget ) ) . '.php';

			include_once 'elementor/widgets/' . $classname;
			$widget_manager->register_widget_type( new $widget() );
		}
	}
}