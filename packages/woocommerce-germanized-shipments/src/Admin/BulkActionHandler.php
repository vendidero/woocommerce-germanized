<?php

namespace Vendidero\Germanized\Shipments\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class       WC_GZD_Shipment_Order
 * @version     1.0.0
 * @author      Vendidero
 */
abstract class BulkActionHandler {

	/**
	 * Step being handled
	 *
	 * @var integer
	 */
	protected $step = 1;

	protected $ids = array();

	protected $notices = array();

	protected $type = 'simple';

	public function __construct() {
		$this->notices = array_filter( (array) get_user_meta( get_current_user_id(), $this->get_notice_option_name(), true ) );
	}

	protected function get_notice_option_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}_bulk_notices";
	}

	abstract public function get_title();

	public function get_nonce_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}";
	}

	public function get_shipment_type() {
		return $this->type;
	}

	public function set_shipment_type( $type ) {
		$this->type = $type;
	}

	public function get_success_redirect_url() {
		$page = 'wc-gzd-shipments';

		if ( 'simple' !== $this->get_shipment_type() ) {
			$page = 'wc-gzd-' . $this->get_shipment_type() . '-shipments';
		}

		return admin_url( 'admin.php?page=' . $page . '&bulk_action_handling=finished&current_bulk_action=' . sanitize_key( $this->get_action() ) );
	}

	public function get_step() {
		return $this->step;
	}

	public function set_step( $step ) {
		$this->step = $step;
	}

	public function get_notices( $type = 'error' ) {
		$notices = array_key_exists( $type, $this->notices ) ? $this->notices[ $type ] : array();

		return $notices;
	}

	public function get_success_message() {
		return _x( 'Successfully processed shipments.', 'shipments', 'woocommerce-germanized' );
	}

	public function admin_handled() {

	}

	public function admin_after_error() {

	}

	public function add_notice( $notice, $type = 'error' ) {
		if ( ! isset( $this->notices[ $type ] ) ) {
			$this->notices[ $type ] = array();
		}

		$this->notices[ $type ][] = $notice;
	}

	public function update_notices() {
		update_user_meta( get_current_user_id(), $this->get_notice_option_name(), $this->notices );
	}

	public function reset( $is_new = false ) {
		delete_user_meta( get_current_user_id(), $this->get_notice_option_name() );
	}

	abstract public function get_action();

	public function get_max_step() {
		return (int) ceil( count( $this->get_ids() ) / $this->get_limit() );
	}

	abstract public function get_limit();

	public function get_total() {
		return count( $this->get_ids() );
	}

	abstract public function handle();

	public function set_ids( $ids ) {
		$this->ids = $ids;
	}

	public function get_ids() {
		return $this->ids;
	}

	public function get_current_ids() {
		return array_slice( $this->get_ids(), ( $this->get_step() - 1 ) * $this->get_limit(), $this->get_limit() );
	}

	/**
	 * Get count of records exported.
	 *
	 * @since 3.0.6
	 * @return int
	 */
	public function get_total_processed() {
		return ( $this->get_step() * $this->get_limit() );
	}

	/**
	 * Get total % complete.
	 *
	 * @since 3.0.6
	 * @return int
	 */
	public function get_percent_complete() {
		return floor( ( $this->get_total_processed() / $this->get_total() ) * 100 );
	}

	public function is_last_step() {
		$current_step = $this->get_step();
		$max_step     = $this->get_max_step();

		if ( $max_step === $current_step ) {
			return true;
		}

		return false;
	}
}
