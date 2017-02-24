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
class WC_GZD_Taxonomy {

	private $taxonomy = '';

	/**
	 * Adds the units from i18n template
	 */
	public function __construct( $taxonomy = '' ) {
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Get units by key
	 *
	 * @param mixed   $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get_term( $key );
	}

	public function get_term_object( $key, $by = 'slug' ) {

	    do_action( 'woocommerce_gzd_get_term', $key, $by, $this->taxonomy );

	    $term = get_term_by( $by, $key, $this->taxonomy );

	    if ( ! $term || is_wp_error( $term ) )
	        $term = false;

        do_action( 'woocommerce_gzd_after_get_term', $key, $by, $this->taxonomy );

	    return $term;
	}

	public function get_term( $key, $by = 'slug' ) {
		if ( $term = $this->get_term_object( $key, $by ) ) 
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
	public function get_terms() {
		$list = array();
		$terms = get_terms( $this->taxonomy, array( 'hide_empty' => false ) );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term )
				$list[ $term->slug ] = $term->name;
		}
		return $list;
	}
}
