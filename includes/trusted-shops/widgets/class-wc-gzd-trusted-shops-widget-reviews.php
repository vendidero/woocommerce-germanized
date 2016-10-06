<?php
/**
 * Trusted Shops Reviews Widget
 *
 * Displays Trusted Shops reviews as graphic
 *
 * @author 		Vendidero
 * @category 	Widgets
 * @version 	1.0
 * @extends 	WC_Widget
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_GZD_Trusted_Shops_Widget_Reviews extends WC_Widget {

	public function __construct() {
		$this->widget_cssclass    = 'woocommerce woocommerce_gzd widget_trusted_shops_reviews';
		$this->widget_description = _x( "Display your Trusted Shops Reviews as graphic.", 'trusted-shops', 'woocommerce-germanized' );
		$this->widget_id          = 'woocommerce_gzd_widget_trusted_shops_reviews';
		$this->widget_name        = _x( 'Trusted Shops Reviews', 'trusted-shops', 'woocommerce-germanized' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => _x( 'Trusted Shops Reviews', 'trusted-shops', 'woocommerce-germanized' ),
				'label' => _x( 'Title', 'trusted-shops', 'woocommerce-germanized' ),
			),
		);
		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {

		extract( $args );

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? _x( 'Trusted Shops Reviews', 'trusted-shops', 'woocommerce-germanized' ) : $instance['title'], $instance, $this->id_base );

		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		echo '<div class="widget_trusted_shops_reviews_graphic_content">';

		echo do_shortcode( '[trusted_shops_reviews]' );

		echo '</div>';

		echo $after_widget;
	}
}

?>