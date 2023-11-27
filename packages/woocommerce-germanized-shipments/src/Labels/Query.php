<?php

namespace Vendidero\Germanized\Shipments\Labels;

use WC_Object_Query;
use WC_Data_Store;
use WP_Date_Query;
use WP_Meta_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract WC Object Query Class
 *
 * Extended by classes to provide a query abstraction layer for safe object searching.
 *
 * @version  3.1.0
 * @package  WooCommerce/Abstracts
 */
class Query extends WC_Object_Query {

	protected $args = array();

	protected $query_fields = array();

	protected $query_from = '';

	protected $query_where = '';

	protected $meta_query = null;

	protected $query_limit = '';

	protected $query_orderby = '';

	protected $request = '';

	protected $results = null;

	protected $total_labels = 0;

	/**
	 * Get the default allowed query vars.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'limit'             => 10,
			'shipment_id'       => '',
			'product_id'        => '',
			'shipping_provider' => '',
			'parent_id'         => '',
			'type'              => wc_gzd_get_shipment_label_types(),
			'number'            => '',
			'order'             => 'DESC',
			'orderby'           => 'date_created',
			'return'            => 'objects',
			'page'              => 1,
			'offset'            => '',
			'paginate'          => false,
			'search'            => '',
			'search_columns'    => array(),
		);
	}

	/**
	 * Get labels matching the current query vars.
	 *
	 * @return Label[] objects
	 *
	 * @throws \Exception When WC_Data_Store validation fails.
	 */
	public function get_labels() {
		/**
		 * Filter to adjust query paramaters for a DHL label query.
		 *
		 * @param array $query_vars The query arguments.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		$args = apply_filters( 'woocommerce_gzd_shipment_label_query_args', $this->get_query_vars() );
		$args = WC_Data_Store::load( 'shipment-label' )->get_query_args( $args );

		$this->query( $args );

		/**
		 * Filter to adjust query result data for a DHL label query.
		 *
		 * @param Label[] $results The results.
		 * @param array   $args The query arguments.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_shipment_label_query', $this->results, $args );
	}

	public function get_total() {
		return $this->total_labels;
	}

	/**
	 * Query shipments.
	 *
	 * @param array $query_args
	 */
	protected function query( $query_args ) {
		global $wpdb;

		$this->args = $query_args;
		$this->parse_query();
		$this->prepare_query();

		$qv =& $this->args;

		$this->results = null;

		if ( null === $this->results ) {
			$this->request = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

			if ( is_array( $qv['fields'] ) || 'objects' === $qv['fields'] ) {
				$this->results = $wpdb->get_results( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$this->results = $wpdb->get_col( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
				$found_labels_query = 'SELECT FOUND_ROWS()';
				$this->total_labels = (int) $wpdb->get_var( $found_labels_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		if ( ! $this->results ) {
			return;
		}

		if ( 'objects' === $qv['fields'] ) {
			foreach ( $this->results as $key => $label ) {
				$this->results[ $key ] = wc_gzd_get_shipment_label( $label );
			}
		}
	}

	/**
	 * Parse the query before preparing it.
	 */
	protected function parse_query() {

		if ( isset( $this->args['shipment_id'] ) ) {
			$this->args['shipment_id'] = absint( $this->args['shipment_id'] );
		}

		if ( isset( $this->args['shipping_provider'] ) ) {
			$this->args['shipping_provider'] = sanitize_key( $this->args['shipping_provider'] );
		}

		if ( isset( $this->args['product_id'] ) ) {
			$this->args['product_id'] = wc_clean( $this->args['product_id'] );
		}

		if ( isset( $this->args['parent_id'] ) && ! empty( $this->args['parent_id'] ) ) {
			$this->args['parent_id'] = absint( $this->args['parent_id'] );
		}

		if ( isset( $this->args['number'] ) ) {
			$this->args['number'] = sanitize_key( $this->args['number'] );
		}

		if ( isset( $this->args['type'] ) ) {
			$this->args['type'] = (array) $this->args['type'];
			$this->args['type'] = array_map( 'wc_clean', $this->args['type'] );
		}

		if ( isset( $this->args['search'] ) ) {
			$this->args['search'] = wc_clean( $this->args['search'] );

			if ( ! isset( $this->args['search_columns'] ) ) {
				$this->args['search_columns'] = array();
			}
		}
	}

	/**
	 * Prepare the query for DB usage.
	 */
	protected function prepare_query() {
		global $wpdb;

		if ( is_array( $this->args['fields'] ) ) {
			$this->args['fields'] = array_unique( $this->args['fields'] );

			$this->query_fields = array();

			foreach ( $this->args['fields'] as $field ) {
				$field                = 'ID' === $field ? 'label_id' : sanitize_key( $field );
				$this->query_fields[] = "$wpdb->gzd_shipment_labels.$field";
			}

			$this->query_fields = implode( ',', $this->query_fields );

		} elseif ( 'objects' === $this->args['fields'] ) {
			$this->query_fields = "$wpdb->gzd_shipment_labels.*";
		} else {
			$this->query_fields = "$wpdb->gzd_shipment_labels.label_id";
		}

		if ( isset( $this->args['count_total'] ) && $this->args['count_total'] ) {
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		}

		$this->query_from  = "FROM $wpdb->gzd_shipment_labels";
		$this->query_where = 'WHERE 1=1';

		// order id
		if ( isset( $this->args['shipment_id'] ) ) {
			$this->query_where .= $wpdb->prepare( ' AND label_shipment_id = %d', $this->args['shipment_id'] );
		}

		// parent id
		if ( isset( $this->args['parent_id'] ) ) {
			$this->query_where .= $wpdb->prepare( ' AND label_parent_id = %d', $this->args['parent_id'] );
		}

		// Number
		if ( isset( $this->args['number'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND label_number IN ('%s')", $this->args['number'] ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder
		}

		// Shipping provider
		if ( isset( $this->args['shipping_provider'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND label_shipping_provider IN ('%s')", $this->args['shipping_provider'] ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder
		}

		// Shipping provider
		if ( isset( $this->args['product_id'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND label_product_id IN ('%s')", $this->args['product_id'] ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder
		}

		// type
		if ( isset( $this->args['type'] ) ) {
			$types   = $this->args['type'];
			$p_types = array();

			foreach ( $types as $type ) {
				$p_types[] = $wpdb->prepare( 'label_type = %s', $type );
			}

			$where_type = implode( ' OR ', $p_types );

			if ( ! empty( $where_type ) ) {
				$this->query_where .= " AND ($where_type)";
			}
		}

		// Search
		$search = '';

		if ( isset( $this->args['search'] ) ) {
			$search = trim( $this->args['search'] );
		}

		if ( $search ) {

			$leading_wild  = ( ltrim( $search, '*' ) !== $search );
			$trailing_wild = ( rtrim( $search, '*' ) !== $search );

			if ( $leading_wild && $trailing_wild ) {
				$wild = 'both';
			} elseif ( $leading_wild ) {
				$wild = 'leading';
			} elseif ( $trailing_wild ) {
				$wild = 'trailing';
			} else {
				$wild = false;
			}
			if ( $wild ) {
				$search = trim( $search, '*' );
			}

			$search_columns = array();

			if ( $this->args['search_columns'] ) {
				$search_columns = array_intersect( $this->args['search_columns'], array( 'label_id', 'label_path', 'label_number', 'label_shipment_id', 'label_product_id' ) );
			}

			if ( ! $search_columns ) {
				if ( is_numeric( $search ) ) {
					$search_columns = array( 'label_id', 'label_shipment_id' );
				} else {
					$search_columns = array( 'label_id', 'label_path', 'label_number', 'label_shipment_id', 'label_product_id' );
				}
			}

			/**
			 * Filters the columns to search in a DHL LabelQuery search.
			 *
			 * The default columns depend on the search term, and include 'label_id',
			 * 'label_shipment_id', 'label_path' and 'label_number'.
			 *
			 * @since 3.0.0
			 *
			 * @param string[]   $search_columns Array of column names to be searched.
			 * @param string     $search         Text being searched.
			 * @param LabelQuery $this  The current LabelQuery instance.
			 *
			 * @package Vendidero/Germanized/DHL
			 */
			$search_columns = apply_filters( 'woocommerce_gzd_shipment_label_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( ! empty( $this->args['include'] ) ) {
			$include = wp_parse_id_list( $this->args['include'] );
		} else {
			$include = false;
		}

		// Meta query.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->args );

		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses            = $this->meta_query->get_sql( 'gzd_shipment_label', $wpdb->gzd_shipment_labels, 'label_id', $this );
			$this->query_from  .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		}

		// sorting
		$this->args['order'] = isset( $this->args['order'] ) ? strtoupper( $this->args['order'] ) : '';
		$order               = $this->parse_order( $this->args['order'] );

		if ( empty( $this->args['orderby'] ) ) {
			// Default order is by 'user_login'.
			$ordersby = array( 'date_created' => $order );
		} elseif ( is_array( $this->args['orderby'] ) ) {
			$ordersby = $this->args['orderby'];
		} else {
			// 'orderby' values may be a comma- or space-separated list.
			$ordersby = preg_split( '/[,\s]+/', $this->args['orderby'] );
		}

		$orderby_array = array();

		foreach ( $ordersby as $_key => $_value ) {
			if ( ! $_value ) {
				continue;
			}

			if ( is_int( $_key ) ) {
				// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order   = $order;
			} else {
				// Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order   = $_value;
			}

			$parsed = $this->parse_orderby( $_orderby );

			if ( ! $parsed ) {
				continue;
			}

			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by user_login.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "label_id $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset( $this->args['posts_per_page'] ) && $this->args['posts_per_page'] > 0 ) {
			if ( isset( $this->args['offset'] ) ) {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $this->args['offset'], $this->args['posts_per_page'] );
			} else {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $this->args['posts_per_page'] * ( $this->args['page'] - 1 ), $this->args['posts_per_page'] );
			}
		}

		if ( ! empty( $include ) ) {
			// Sanitized earlier.
			$ids                = implode( ',', $include );
			$this->query_where .= " AND $wpdb->gzd_shipment_labels.label_id IN ($ids)";
		} elseif ( ! empty( $this->args['exclude'] ) ) {
			$ids                = implode( ',', wp_parse_id_list( $this->args['exclude'] ) );
			$this->query_where .= " AND $wpdb->gzd_shipment_labels.label_id NOT IN ($ids)";
		}

		// Date queries are allowed for the user_registered field.
		if ( ! empty( $this->args['date_query'] ) && is_array( $this->args['date_query'] ) ) {
			$date_query         = new WP_Date_Query( $this->args['date_query'], 'label_date_created' );
			$this->query_where .= $date_query->get_sql();
		}
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @since 3.0.6
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $string
	 * @param array  $cols
	 * @param bool   $wild   Whether to allow wildcard searches. Default is false for Network Admin, true for single site.
	 *                       Single site allows leading and trailing wildcards, Network Admin only trailing.
	 * @return string
	 */
	protected function get_search_sql( $string, $cols, $wild = false ) {
		global $wpdb;

		$searches      = array();
		$leading_wild  = ( 'leading' === $wild || 'both' === $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' === $wild || 'both' === $wild ) ? '%' : '';
		$like          = $leading_wild . $wpdb->esc_like( $string ) . $trailing_wild;

		foreach ( $cols as $col ) {
			if ( 'ID' === $col ) {
				$searches[] = $wpdb->prepare( "$col = %s", $string ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		return ' AND (' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Parse orderby statement.
	 *
	 * @param string $orderby
	 * @return string
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		$meta_query_clauses = $this->meta_query->get_clauses();

		$_orderby = '';

		if ( in_array( $orderby, array( 'number', 'shipment_id', 'date_created' ), true ) ) {
			$_orderby = 'label_' . $orderby;
		} elseif ( 'ID' === $orderby || 'id' === $orderby ) {
			$_orderby = 'label_id';
		} elseif ( 'meta_value' === $orderby || $this->get( 'meta_key' ) === $orderby ) {
			$_orderby = "$wpdb->gzd_shipment_labelmeta.meta_value";
		} elseif ( 'meta_value_num' === $orderby ) {
			$_orderby = "$wpdb->gzd_shipment_labelmeta.meta_value+0";
		} elseif ( 'include' === $orderby && ! empty( $this->args['include'] ) ) {
			$include     = wp_parse_id_list( $this->args['include'] );
			$include_sql = implode( ',', $include );
			$_orderby    = "FIELD( $wpdb->gzd_shipment_labels.label_id, $include_sql )";
		} elseif ( isset( $meta_query_clauses[ $orderby ] ) ) {
			$meta_clause = $meta_query_clauses[ $orderby ];
			$_orderby    = sprintf( 'CAST(%s.meta_value AS %s)', esc_sql( $meta_clause['alias'] ), esc_sql( $meta_clause['cast'] ) );
		}

		return $_orderby;
	}

	/**
	 * Parse order statement.
	 *
	 * @param string $order
	 * @return string
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
}
