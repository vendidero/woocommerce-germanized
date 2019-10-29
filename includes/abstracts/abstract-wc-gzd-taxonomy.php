<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * @param mixed $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get_term( $key );
	}

	public function get_term_object( $key, $by = 'slug' ) {

		$taxonomy = $this->taxonomy;

		/**
		 * Before retrieving a certain term.
		 *
		 * Executes before retrieving a certain term (e.g. a delivery time).
		 *
		 * @param string $key The identifier e.g. term slug.
		 * @param string $by Indicates how to identify the term e.g. by slug.
		 * @param string $taxonomy The taxonomy linked to the term e.g. delivery_time.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_get_term', $key, $by, $taxonomy );

		$term = get_term_by( $by, $key, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			$term = false;
		}

		/**
		 * After retrieving a certain term.
		 *
		 * Executes after retrieving a certain term (e.g. a delivery time).
		 *
		 * @param string $key The identifier e.g. term slug.
		 * @param string $by Indicates how to identify the term e.g. by slug.
		 * @param string $taxonomy The taxonomy linked to the term e.g. delivery_time.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_after_get_term', $key, $by, $taxonomy );

		return $term;
	}

	public function get_term( $key, $by = 'slug' ) {
		if ( $term = $this->get_term_object( $key, $by ) ) {
			return $term->name;
		}

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
		$list  = array();
		$terms = get_terms( $this->taxonomy, array( 'hide_empty' => false ) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

			foreach ( $terms as $term ) {
				$list[ $term->slug ] = $term->name;
			}
		}

		return $list;
	}
}
