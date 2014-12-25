<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Product External Class
 *
 * @class 		WC_GZD_Product_External
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Product_External extends WC_Product_External {
	
	/**
	 * contains the WC_GZD_Product abstract class
	 * @var object
	 */
	private $base = null;

	/**
	 * Construct the Product by calling parent constructor and injecting WC_GZD_Product
	 *
	 * @param int|WC_Product|WP_Post $product Product ID, post object, or product object
	 */
	public function __construct( $product ) {
		parent::__construct( $product );
		$this->base = new WC_GZD_Product( $this );
	}

	/**
	 * Deligate method calls to WC_GZD_Product
	 *
	 * @param  method name $name
	 * @param  arguments for calling method $args
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		return call_user_func_array( array( $this->base, $name ), $args );
	}

}
