<?php

class WC_GZD_Trusted_Shops_Widgets {

	protected static $_instance = null;

	public $base = null;

	public static function instance( $base ) {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self( $base );
		return self::$_instance;
	}

	private function __construct( $base ) {
		$this->base = $base;

		add_action( 'widgets_init', array( $this, 'include_widgets' ), 25 );
	}

	public function include_widgets() {
		if ( $this->base->is_review_sticker_enabled() ) {
			$this->register_widget( 'review_sticker' );
		}
	}

	private function register_widget( $name ) {
		$classname = $this->base->get_dependency_name( 'widget_' . $name );
		include_once( 'widgets/class-' . strtolower( str_replace( '_', '-', $classname ) ) . '.php' );
		register_widget( $classname );
	}

}