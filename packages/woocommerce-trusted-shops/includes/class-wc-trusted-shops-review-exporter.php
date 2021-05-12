<?php
/**
 * Handles CSV export.
 *
 * @package  WooCommerce/Export
 * @version  3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_CSV_Exporter', false ) ) {
	require_once WC_ABSPATH . 'includes/export/abstract-wc-csv-exporter.php';
}

/**
 * WC_CSV_Exporter Class.
 */
class WC_Trusted_Shops_Review_Exporter extends WC_CSV_Exporter {

	/**
	 * Type of export used in filter names.
	 *
	 * @var string
	 */
	protected $export_type = 'trusted_shops_reviews';

	/**
	 * Filename to export to.
	 *
	 * @var string
	 */
	protected $filename = 'trusted-shops-reviews.csv';

	/**
	 * Batch limit.
	 *
	 * @var integer
	 */
	protected $limit = 50;

	protected $days_to_send = 5;

	protected $days_interval = 30;

	protected $statuses = array();

	protected $lang = '';

	public function __construct() {
	    $this->statuses     = array_keys( wc_get_order_statuses() );
		$this->column_names = $this->get_default_column_names();
	}

	/**
	 * Return an array of columns to export.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_default_column_names() {
		return apply_filters( "woocommerce_gzd_{$this->export_type}_default_columns", array(
			'id'                 => 'reference',
			'date'               => 'date',
			'days'               => 'days',
			'billing_email'      => 'email',
			'billing_first_name' => 'firstName',
			'billing_last_name'  => 'lastName',
		) );
	}

	public function get_interval_days() {
		return absint( $this->days_interval );
	}

	public function set_interval_days( $days ) {
		$this->days_interval = absint( $days );
	}

	public function get_days_until_send() {
		return $this->days_to_send;
	}

	public function set_days_until_send( $days ) {
		$this->days_to_send = absint( $days );
	}

	public function get_statuses() {
	    return $this->statuses;
    }

    public function set_statuses( $statuses ) {
        $this->statuses = (array) $statuses;
    }

    public function set_lang( $lang ) {
	    $this->lang = $lang;
    }

    public function get_lang() {
	    return $this->lang;
    }

	/**
	 * Prepare data that will be exported.
	 */
	public function prepare_data_to_export() {
		$columns  = $this->get_column_names();
		$date     = date( 'Y-m-d', strtotime( '-' . $this->get_interval_days() . ' days' ) );
		$args     =  array(
            'post_type'   => 'shop_order',
            'post_status' => $this->get_statuses(),
            'showposts'   => -1,
            'date_query'  => array(
                array(
                    'after' => $date,
                ),
            ),
        );

        if ( $this->get_lang() !== '' && 'all' !== $this->get_lang() ) {
            $args['meta_query']         = array();
            $args['meta_query']['wpml'] = array(
                'key'     => 'wpml_language',
                'compare' => '=',
                'value'   => $this->get_lang(),
            );
        }

		$order_query      = new WP_Query( apply_filters( "woocommerce_gzd_{$this->export_type}_query_args", $args ) );
		$this->total_rows = $order_query->found_posts;
		$this->row_data   = array();

		while ( $order_query->have_posts() ) {
			$order_query->next_post();

			$order = wc_get_order( $order_query->post->ID );
			$row   = array();

			foreach ( $columns as $column_id => $column_name ) {
				$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
				$value     = '';

				if ( is_callable( array( $this, "get_column_value_{$column_id}" ) ) ) {
					// Handle special columns which don't map 1:1 to order data.
					$value = $this->{"get_column_value_{$column_id}"}( $order );

				} elseif ( wc_ts_get_crud_data( $order, $column_id ) ) {
					// Default and custom handling.
					$value = wc_ts_get_crud_data( $order, $column_id );
				}

				$row[ $column_id ] = $value;
			}

			$this->row_data[] = apply_filters( 'woocommerce_gzd_trusted_shops_review_export_row_data', $row, $order );
		}
	}

	public function get_column_value_date( $order ) {
		return wc_ts_get_order_date( $order, 'd.m.Y' );
	}

	public function get_column_value_days( $order ) {
		return $this->get_days_until_send();
	}
}
