<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy base class.
 *
 * @class    WC_GZD_Taxonomy
 * @version  1.0.0
 * @author   vendidero
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

	/**
	 * @param $key
	 * @param $by
	 *
	 * @return false|WP_Term
	 */
	public function get_term_object( $key, $by = 'slug' ) {
		$taxonomy = $this->get_taxonomy();

		/**
		 * In case a numeric key is available, prefer retrieving by id over slug.
		 */
		if ( 'slug_fallback' === $by ) {
			$by = 'slug';

			if ( is_numeric( $key ) ) {
				$term = $this->get_term_object( $key, 'id' );

				if ( $term ) {
					return $term;
				}
			}
		}

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

		if ( is_array( $term ) ) {
			$term = $term[0];
		}

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

	/**
	 * Returns the term name.
	 *
	 * @param $key
	 * @param $by
	 *
	 * @return false|string
	 */
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
	 * Returns a list of terms slug=>name
	 *
	 * @return string[] terms as array
	 */
	public function get_terms( $args = array() ) {
		if ( isset( $args['as'] ) ) {
			$args['fields'] = $args['as'];
		}

		$args = wp_parse_args(
			$args,
			array(
				'hide_empty' => false,
				'fields'     => 'slug=>name',
				'taxonomy'   => $this->get_taxonomy(),
			)
		);

		$fields        = $args['fields'];
		$is_core_field = in_array( $fields, array( 'all', 'all_with_object_id', 'ids', 'tt_ids', 'names', 'slugs', 'count', 'id=>parent', 'id=>name', 'id=>slug' ), true );

		if ( ! $is_core_field ) {
			$args['fields'] = 'all';
		}

		$list  = array();
		$terms = get_terms( array_diff_key( $args, array( 'as' => '' ) ) );

		if ( ! $is_core_field ) {
			$as_data = array_map( 'trim', explode( '=>', $fields ) );
			$key     = isset( $as_data[0] ) ? $as_data[0] : 'slug';
			$value   = isset( $as_data[1] ) ? $as_data[1] : 'name';

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$list[ ( isset( $term->{$key} ) ? $term->{$key} : $term->slug ) ] = $this->format_term_value( $value, $term );
				}
			}
		} elseif ( ! is_wp_error( $terms ) ) {
			$list = $terms;
		}

		return $list;
	}

	/**
	 * @param string $value_to_extract
	 * @param WP_Term $term
	 *
	 * @return mixed
	 */
	protected function format_term_value( $value_to_extract, $term ) {
		return ( isset( $term->{$value_to_extract} ) ? $term->{$value_to_extract} : $term->name );
	}
}
