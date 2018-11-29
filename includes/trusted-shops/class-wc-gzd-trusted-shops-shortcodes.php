<?php

class WC_GZD_Trusted_Shops_Shortcodes {

	protected static $_instance = null;

	public $base = null;

	public static function instance( $base ) {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self( $base );
		return self::$_instance;
	}

	private function __construct( $base ) {
		$this->base = $base;

		add_action( 'init', array( $this, 'init' ), 3 );
	}

	public function init() {

		// Define shortcodes
		$shortcodes = array(
			'trusted_shops_rich_snippets'  => array( $this, 'trusted_shops_rich_snippets' ),
			'trusted_shops_review_sticker' => array( $this, 'trusted_shops_review_sticker' ),
			'trusted_shops_badge'		   => array( $this, 'trusted_shops_badge' ),
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

	}

	/**
	 * Returns Trusted Shops rich snippet review html
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public function trusted_shops_rich_snippets( $atts ) {
		ob_start();
		wc_get_template( 'trusted-shops/rich-snippets.php', array(
			'plugin'    => $this->base
		) );
		$html = ob_get_clean();

		return $this->base->is_enabled() ? $html : '';
	}

	/**
	 * Returns Trusted Shops reviews graphic
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public function trusted_shops_review_sticker( $atts ) {
		$atts = wp_parse_args( $atts, array(
			'element' => '#ts_review_sticker',
		) );

		ob_start();
		wc_get_template( 'trusted-shops/review-sticker.php', array(
			'plugin'    => $this->base,
			'element'   => $atts['element'],
		) );
		$html = ob_get_clean();
		return $this->base->is_enabled() ? '<div class="woocommerce woocommerce-gzd">' . $html . '</div>' : '';
	}

	/**
	 * Returns Trusted Shops Badge html
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public function trusted_shops_badge( $atts ) {
		extract( shortcode_atts( array('width' => ''), $atts ) );

		return $this->base->is_enabled() ? '<a class="trusted-shops-badge" style="' . ( $width ? 'background-size:' . ( $width - 1 ) . 'px auto; width: ' . $width . 'px; height: ' . $width . 'px;' : '' ) . '" href="' . $this->base->get_certificate_link() . '" target="_blank"></a>' : '';
	}

}