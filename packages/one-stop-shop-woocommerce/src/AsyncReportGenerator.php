<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

class AsyncReportGenerator {

	protected $args = array();

	protected $type = '';

	public function __construct( $type = 'quarterly', $args = array() ) {
		$this->type = $type;

		$default_end   = new \WC_DateTime();
		$default_start = new \WC_DateTime( 'now' );
		$default_start->modify( '-1 year' );

		$args = wp_parse_args( $args, array(
			'start'  => $default_start->format( 'Y-m-d' ),
			'end'    => $default_end->format( 'Y-m-d' ),
			'limit'  => Queue::get_batch_size(),
			'status' => Queue::get_order_statuses(),
			'offset' => 0,
			'orders_processed' => 0,
		) );

		foreach( array( 'start', 'end' ) as $date_field ) {
			if ( is_a( $args[ $date_field ], 'WC_DateTime' ) ) {
				$args[ $date_field ] = $args[ $date_field ]->format( 'Y-m-d' );
			} elseif( is_numeric( $args[ $date_field ] ) ) {
				$date = new \WC_DateTime( '@' . $args[ $date_field ] );
				$args[ $date_field ] = $date->format( 'Y-m-d' );
			}
		}

		$this->args = $args;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_args() {
		return $this->args;
	}

	public function get_id() {
		return sanitize_key( 'oss_' . $this->type . '_report_' . $this->args['start'] . '_' . $this->args['end'] );
	}

	public function delete() {
		$report = new Report( $this->get_id() );
		$report->delete();

		delete_option( $this->get_id() . '_tmp_result' );
	}

	public function start() {
		$report = new Report( $this->get_id() );
		$report->reset();
		$report->save();

		return $report;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return mixed
	 */
	protected function get_order_taxable_country( $order ) {
		$taxable_country_type = ! empty( $order->get_shipping_country() ) ? 'shipping' : 'billing';
		$taxable_country      = 'shipping' === $taxable_country_type ? $order->get_shipping_country() : $order->get_billing_country();

		return $taxable_country;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return mixed
	 */
	protected function get_order_taxable_postcode( $order ) {
		$taxable_type     = ! empty( $order->get_shipping_postcode() ) ? 'shipping' : 'billing';
		$taxable_postcode = 'shipping' === $taxable_type ? $order->get_shipping_postcode() : $order->get_billing_postcode();

		return $taxable_postcode;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return bool
	 */
	protected function include_order( $order ) {
		$taxable_country  = $this->get_order_taxable_country( $order );
		$taxable_postcode = $this->get_order_taxable_postcode( $order );
		$included         = true;

		if ( ! Package::country_supports_eu_vat( $taxable_country, $taxable_postcode ) ) {
			$included = false;
		}

		if ( $order->get_total_tax() == 0 ) {
			$included = false;
		}

		return apply_filters( "oss_woocommerce_report_include_order", $included, $order );
	}

	protected function get_taxable_country_iso( $country ) {
		if ( 'GB' === $country ) {
			$country = 'XI';
		}

		return $country;
	}

	/**
	 * @return true|\WP_Error
	 */
	public function next() {
		$date_key         = Queue::use_date_paid() ? 'date_paid' : 'date_created';
		$args             = $this->args;
		$query_args       = Queue::get_order_query_args( $args, $date_key );
		$orders_processed = 0;

		Package::extended_log( sprintf( 'Building next order query: %s', wc_print_r( $query_args, true ) ) );

		$orders   = wc_get_orders( $query_args );
		$tax_data = $this->get_temporary_result();

		Package::extended_log( sprintf( '%d applicable orders found', sizeof( $orders ) ) );

		if ( ! empty( $orders ) ) {
			foreach( $orders as $order ) {
				$taxable_country = $this->get_order_taxable_country( $order );

				if ( ! $this->include_order( $order ) ) {
					Package::extended_log( sprintf( 'Skipping order #%1$s based on taxable country %2$s, tax total: %3$s', $order->get_order_number(), $taxable_country, $order->get_total_tax() ) );
					continue;
				}

				$country_iso = $this->get_taxable_country_iso( $taxable_country );

				Package::extended_log( sprintf( 'Processing order #%1$s based on taxable country %2$s', $order->get_order_number(), $country_iso ) );

				if ( ! isset( $tax_data[ $country_iso ] ) ) {
					$tax_data[ $country_iso ] = array();
				}

				foreach ( $order->get_taxes() as $key => $tax ) {
					$refunded    = (float) $order->get_total_tax_refunded_by_rate_id( $tax->get_rate_id() );
					$tax_percent = (float) Tax::get_tax_rate_percent( $tax->get_rate_id(), $order );
					$tax_total   = (float) $tax->get_tax_total() + (float) $tax->get_shipping_tax_total() - $refunded;

					if ( $tax_percent <= 0 || $tax_total == 0 ) {
						continue;
					}

					if ( ! isset( $tax_data[ $country_iso ][ $tax_percent ] ) ) {
						$tax_data[ $country_iso ][ $tax_percent ] = array(
							'tax_total' => 0,
							'net_total' => 0,
						);
					}

					$net_total = ( $tax_total / ( (float) $tax_percent / 100 ) );

					Package::extended_log( sprintf( 'Refunded tax %1$s = %2$s', $tax_percent, $refunded ) );
					Package::extended_log( sprintf( 'Tax total %1$s = %2$s', $tax_percent, $tax_total ) );
					Package::extended_log( sprintf( 'Net total %1$s = %2$s', $tax_percent, $net_total ) );

					$net_total = wc_add_number_precision( $net_total, false );
					$tax_total = wc_add_number_precision( $tax_total, false );

					$tax_data[ $country_iso ][ $tax_percent ]['tax_total'] = (float) $tax_data[ $country_iso ][ $tax_percent ]['tax_total'];
					$tax_data[ $country_iso ][ $tax_percent ]['tax_total'] += $tax_total;

					$tax_data[ $country_iso ][ $tax_percent ]['net_total'] = (float) $tax_data[ $country_iso ][ $tax_percent ]['net_total'];
					$tax_data[ $country_iso ][ $tax_percent ]['net_total'] += $net_total;

					$orders_processed++;
				}
			}

			$this->args['orders_processed'] = absint( $this->args['orders_processed'] ) + $orders_processed;

			update_option( $this->get_id() . '_tmp_result', $tax_data );

			return true;
		} else {
			return new \WP_Error( 'empty', _x( 'No orders found.', 'oss', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @return Report
	 */
	public function complete() {
		Package::extended_log( sprintf( 'Completed called' ) );

		$tmp_result = $this->get_temporary_result();
		$report     = new Report( $this->get_id() );
		$tax_total  = 0;
		$net_total  = 0;

		foreach( $tmp_result as $country => $tax_data ) {
			foreach( $tax_data as $percent => $totals ) {
				$tax_total += (float) $totals['tax_total'];
				$net_total += (float) $totals['net_total'];

				$report->set_country_net_total( $country, $percent, (float) wc_remove_number_precision( $totals['net_total'] ) );
				$report->set_country_tax_total( $country, $percent, (float) wc_remove_number_precision( $totals['tax_total'] ) );
			}
		}

		$report->set_net_total( wc_remove_number_precision( $net_total ) );
		$report->set_tax_total( wc_remove_number_precision( $tax_total ) );
		$report->set_status( 'completed' );
		$report->save();

		return $report;
	}

	protected function get_temporary_result() {
		return (array) get_option( $this->get_id() . '_tmp_result', array() );
	}

	/**
	 * @param $rate_id
	 * @param \WC_Order $order
	 */
	protected function get_rate_percent( $rate_id, $order ) {
		$taxes      = $order->get_taxes();
		$percentage = null;

		foreach( $taxes as $tax ) {
			if ( $tax->get_rate_id() == $rate_id ) {
				if ( is_callable( array( $tax, 'get_rate_percent' ) ) ) {
					$percentage = $tax->get_rate_percent();
				}
			}
		}

		/**
		 * WC_Order_Item_Tax::get_rate_percent returns null by default.
		 * Fallback to global tax rates (DB) in case the percentage is not available within order data.
		 */
		if ( is_null( $percentage ) || '' === $percentage ) {
			$percentage = \WC_Tax::get_rate_percent_value( $rate_id );
		}

		if ( ! is_numeric( $percentage ) ) {
			$percentage = 0;
		}

		return $percentage;
	}
}
