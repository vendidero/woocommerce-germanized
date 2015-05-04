<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * The Units Class stores units/measurements data.
 *
 * @class WC_Germanized_Units
 * @version  1.0.0
 * @author   Vendidero
 */
class WC_GZD_Units {

	/**
	 * array containg units
	 *
	 * @var array
	 */
	private $units = array();
	private $taxonomy = 'product_unit';

	/**
	 * Adds the units from i18n template
	 */
	public function __construct() {
		
	}

	/**
	 * Get units by key
	 *
	 * @param mixed   $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get_unit( $key );
	}

	public function get_unit_object( $key, $by = 'slug' ) {
		if ( $term = get_term_by( $by, $key, $this->taxonomy ) ) 
			return $term;
		return false;
	}

	public function get_unit( $key, $by = 'slug' ) {
		if ( $term = $this->get_unit_object( $key, $by ) ) 
			return $term->name;
		return false;
	}

	public function get_taxonomy() {
		return $this->taxonomy;
	}

	/**
	 * Returns mixed units array
	 *
	 * @return mixed units as array
	 */
	public function get_units() {
		$list = array();
		$terms = get_terms( $this->taxonomy, array( 'hide_empty' => false ) );
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term )
				$list[ $term->slug ] = $term->name;
		}
		return $list;
	}
}
