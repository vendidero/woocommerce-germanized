<?php

class WC_GZD_Trusted_Shops_Template_Hooks {

	protected static $_instance = null;

	public $base = null;

	public static function instance( $base ) {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self( $base );
		return self::$_instance;
	}

	private function __construct( $base ) {
		
		$this->base = $base;

		// Template actions
		if ( $this->base->is_enabled() )
			add_action( 'after_setup_theme', array( $this, 'template_hooks' ), 13 );

		if ( $this->base->is_product_reviews_enabled() ) {
			add_filter( 'woocommerce_product_tabs', array( $this, 'remove_review_tab' ), 40, 1 );
		}

		if ( $this->base->is_product_sticker_enabled() ) {
			add_filter( 'woocommerce_product_tabs', array( $this, 'review_tab' ), 50, 1 );
		}
		
		if ( $this->base->is_product_widget_enabled() ) {
			add_filter( 'woocommerce_gzd_template_name', array( $this, 'set_product_widget_template' ), 50, 1 );
		}

	}

	public function template_hooks() {
		
		add_action( 'woocommerce_thankyou', array( $this, 'template_thankyou' ), 10, 1 );
		add_action( 'wp_footer', array( $this, 'template_trustbadge' ), PHP_INT_MAX );

	}

	public function set_product_widget_template( $template ) {
		
		if ( in_array( $template, array( 'single-product/rating.php' ) ) )
			$template = 'trusted-shops/product-widget.php';

		return $template;

	}

	public function remove_review_tab( $tabs ) {
		
		if ( isset( $tabs[ 'reviews' ] ) )
			unset( $tabs[ 'reviews' ] );
		return $tabs;
	
	}

	public function review_tab( $tabs ) {
		$tabs[ 'trusted_shops_reviews' ] = array(
			'title' => _x( 'Reviews', 'trusted-shops', 'woocommerce-germanized' ),
			'priority' => 30,
			'callback' => array( $this, 'template_product_sticker' ),
		);
		return $tabs;
	}

	public function template_product_sticker( $template ) {
		wc_get_template( 'trusted-shops/product-sticker.php' );
	}

	public function template_trustbadge() {
		wc_get_template( 'trusted-shops/trustbadge.php' );
	}

	public function template_thankyou( $order_id ) {
		wc_get_template( 'trusted-shops/thankyou.php', array( 
			'order_id' => $order_id,
			'gtin_attribute' => $this->base->gtin_attribute,
			'brand_attribute' => $this->base->brand_attribute,
			'mpn_attribute' => $this->base->mpn_attribute,
		) );
	}

}