<?php

namespace Vendidero\OrderWithdrawalButton\DataStores;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Vendidero\OrderWithdrawalButton\Package;
use WC_Cache_Helper;
use WC_Meta_Data;

/**
 * Class OrdersTableRefundDataStore.
 */
class WithdrawalOrder extends OrdersTableDataStore {

	/**
	 * Data stored in meta keys, but not considered "meta" for refund.
	 *
	 * @var string[]
	 */
	protected $internal_meta_keys = array(
		'_withdrawal_number',
		'_date_confirmed',
		'_date_rejected',
		'_original_status',
		'_rejection_reason',
		'_is_partial',
		'_has_verified_email',
		'_is_update',
		'_is_guest',
		'_refund_id',
		'_first_name',
		'_last_name',
		'_email',
		'_order_number',
	);

	/**
	 * We do not have and use all the getters and setters from OrderTableDataStore, so we only select the props we actually need.
	 *
	 * @var \string[][]
	 */
	protected $operational_data_column_mapping = array(
		'id'                        => array( 'type' => 'int' ),
		'order_id'                  => array( 'type' => 'int' ),
		'woocommerce_version'       => array(
			'type' => 'string',
			'name' => 'version',
		),
		'prices_include_tax'        => array(
			'type' => 'bool',
			'name' => 'prices_include_tax',
		),
		'coupon_usages_are_counted' => array(
			'type' => 'bool',
			'name' => 'recorded_coupon_usage_counts',
		),
		'shipping_tax_amount'       => array(
			'type' => 'decimal',
			'name' => 'shipping_tax',
		),
		'shipping_total_amount'     => array(
			'type' => 'decimal',
			'name' => 'shipping_total',
		),
		'discount_tax_amount'       => array(
			'type' => 'decimal',
			'name' => 'discount_tax',
		),
		'discount_total_amount'     => array(
			'type' => 'decimal',
			'name' => 'discount_total',
		),
		'order_key'                 => array(
			'type' => 'string',
			'name' => 'order_key',
		),
	);

	public $meta_type = 'wc_orders_';

	/**
	 * Delete a withdrawal order from database.
	 *
	 * @param \WC_Order $withdrawal Withdrawal object to delete.
	 * @param array     $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$withdrawal, $args = array() ) {
		$withdrawal_id = $withdrawal->get_id();

		if ( ! $withdrawal_id ) {
			return;
		}

		$args = wp_parse_args(
			$args,
			array(
				'force_delete'     => false,
				'suppress_filters' => false,
			)
		);

		if ( $args['force_delete'] ) {
			do_action( 'eu_owb_woocommerce_before_delete_withdrawal', $withdrawal_id, $withdrawal );

			$withdrawal_cache_key = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'withdrawals' . $withdrawal->get_parent_id();
			wp_cache_delete( $withdrawal_cache_key, 'orders' );

			$this->delete_order_data_from_custom_order_tables( $withdrawal_id );
			$withdrawal->set_id( 0 );

			do_action( 'eu_owb_woocommerce_withdrawal_order_deleted', $withdrawal );
		} else {
			do_action( 'eu_owb_woocommerce_before_trash_withdrawal', $withdrawal_id, $withdrawal );

			$this->trash_order( $withdrawal );

			do_action( 'eu_owb_woocommerce_withdrawal_order_trashed', $withdrawal_id );
		}
	}

	/**
	 * Attempts to restore the specified order back to its original status (after having been trashed).
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $order The order to be untrashed.
	 *
	 * @return bool If the operation was successful.
	 */
	public function untrash_withdrawal( $order ) {
		$id     = $order->get_id();
		$status = $order->get_status();

		if ( 'trash' !== $status ) {
			return false;
		}

		$previous_status           = $order->get_meta( '_wp_trash_meta_status' );
		$valid_statuses            = Package::get_withdrawal_statuses();
		$previous_state_is_invalid = ! array_key_exists( $previous_status, $valid_statuses );

		if ( $previous_state_is_invalid ) {
			$previous_status = 'requested';
		}

		do_action( 'eu_owb_woocommerce_untrash_withdrawal', $order->get_id(), $previous_status );

		$order->set_status( $previous_status );
		$order->save();

		// Was the status successfully restored? Let's clean up the meta and indicate success...
		if ( 'wc-' . $order->get_status() === $previous_status ) {
			$order->delete_meta_data( '_wp_trash_meta_status' );
			$order->delete_meta_data( '_wp_trash_meta_time' );
			$order->delete_meta_data( '_wp_trash_meta_comments_status' );
			$order->save_meta_data();

			return true;
		}

		return false;
	}

	/**
	 * Helper method to set refund props.
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal Refund object.
	 * @param object           $data   DB data object.
	 *
	 * @since 8.0.0
	 */
	protected function set_order_props_from_data( &$withdrawal, $data ) {
		parent::set_order_props_from_data( $withdrawal, $data );
		foreach ( $data->meta_data as $meta ) {
			$setter = "set{$meta->meta_key}";

			if ( is_callable( array( $withdrawal, $setter ) ) ) {
				$withdrawal->{$setter}( $meta->meta_value );
			}
		}
	}

	/**
	 * Method to create a withdrawal in the database.
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal Withdrawal object.
	 */
	public function create( &$withdrawal ) {
		if ( ! $withdrawal->get_withdrawal_number( 'edit' ) ) {
			$withdrawal->set_withdrawal_number( md5( uniqid( '', true ) ) );
		}

		if ( '' === $withdrawal->get_order_key() ) {
			$withdrawal->set_order_key( wc_generate_order_key() );
		}

		if ( '' === $withdrawal->get_order_number( 'edit' ) ) {
			if ( $parent = $withdrawal->get_parent() ) {
				$withdrawal->set_order_number( $parent->get_order_number() );
			}
		}

		$this->persist_save( $withdrawal, false, false );

		do_action( 'eu_owb_woocommerce_new_withdrawal_order', $withdrawal->get_id(), $withdrawal );
	}

	/**
	 * Update withdrawal in database.
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal Withdrawal object.
	 */
	public function update( &$withdrawal ) {
		$this->persist_updates( $withdrawal, false );
		$withdrawal->apply_changes();

		do_action( 'eu_owb_woocommerce_withdrawal_order_updated', $withdrawal->get_id(), $withdrawal );
	}

	/**
	 * Helper method that updates post meta based on an refund object.
	 * Mostly used for backwards compatibility purposes in this datastore.
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal Withdrawal object.
	 */
	public function update_order_meta( &$withdrawal ) {
		$props_changed = $withdrawal->get_changes();
		$search_props  = $withdrawal->get_search_props();

		foreach ( $search_props as $prop => $search_value ) {
			if ( array_key_exists( $prop, $props_changed ) ) {
				$withdrawal->update_meta_data( '_billing_address_index', implode( ' ', array_filter( array_values( $search_props ) ) ) );
				break;
			}
		}

		parent::update_order_meta( $withdrawal );

		// Update additional props.
		$updated_props      = array();
		$internal_meta_keys = array_keys( Package::get_withdrawal_order_props() );

		foreach ( $internal_meta_keys as $meta_key ) {
			$prop          = substr( $meta_key, 1 );
			$existing_meta = $this->data_store_meta->get_metadata_by_key( $withdrawal, $meta_key );

			if ( array_key_exists( $prop, $props_changed ) || ! $existing_meta ) {
				$meta_object        = new WC_Meta_Data();
				$meta_object->key   = $meta_key;
				$meta_object->value = $withdrawal->{"get_$prop"}( 'edit' );

				switch ( $meta_key ) {
					case '_is_partial':
					case '_has_verified_email':
					case '_is_update':
					case '_is_guest':
						$meta_object->value = wc_bool_to_string( $meta_object->value );
						break;
					case '_date_confirmed':
					case '_date_rejected':
						$meta_object->value = $meta_object->value ? $meta_object->value->getTimestamp() : null;
						break;
				}

				if ( $existing_meta ) {
					$existing_meta   = $existing_meta[0];
					$meta_object->id = $existing_meta->id;
					$this->update_meta( $withdrawal, $meta_object );
				} else {
					$this->add_meta( $withdrawal, $meta_object );
				}

				$updated_props[] = $prop;
			}
		}
	}

	public function clear_caches( &$order ) {
		parent::clear_caches( $order );

		$parent_order_id = $order->get_parent_id();

		if ( $parent_order_id > 0 ) {
			$withdrawal_cache_key = \WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'withdrawals' . $parent_order_id;
			wp_cache_delete( $withdrawal_cache_key, 'orders' );
		}
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @return string
	 */
	protected function get_post_title() {
		return sprintf(
			/* translators: %s: Order date */
			_x( 'Withdrawal &ndash; %s', 'owb', 'woocommerce-germanized' ),
			( new \DateTime( 'now' ) )->format( _x( 'M d, Y @ h:i A', 'owb-order-date-format', 'woocommerce-germanized' ) ) // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.UnorderedPlaceholdersText
		);
	}

	/**
	 * Returns data store object to use backfilling.
	 *
	 * @return \WC_Order_Refund_Data_Store_CPT
	 */
	protected function get_post_data_store_for_backfill() {
		return null;
	}

	public function backfill_post_record( $order ) {}

	/**
	 * Get the status to save to the post object.
	 *
	 * Plugins extending the order classes can override this to change the stored status/add prefixes etc.
	 *
	 * @since 3.6.0
	 * @param  \Vendidero\OrderWithdrawalButton\WithdrawalOrder $order Order object.
	 * @return string
	 */
	protected function get_post_status( $withdrawal ) {
		$status = parent::get_post_status( $withdrawal );

		if ( ! $withdrawal->get_status( 'edit' ) ) {
			$status = 'wc-owb-requested';
		}

		return $status;
	}
}
