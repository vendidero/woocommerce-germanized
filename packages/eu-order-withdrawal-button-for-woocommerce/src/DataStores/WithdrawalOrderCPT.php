<?php

namespace Vendidero\OrderWithdrawalButton\DataStores;

use Vendidero\OrderWithdrawalButton\Package;

defined( 'ABSPATH' ) || exit;

class WithdrawalOrderCPT extends \Abstract_WC_Order_Data_Store_CPT implements \WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_order_currency',
		'_cart_discount',
		'_cart_discount_tax',
		'_order_shipping',
		'_order_shipping_tax',
		'_order_tax',
		'_order_total',
		'_order_version',
		'_prices_include_tax',
		'_payment_tokens',
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
		'_customer_id',
		'_email',
		'_order_number',
		'_order_key',
		'_customer_ip_address',
		'_customer_user_agent',
	);

	/**
	 * Delete a withdrawal - no trash is supported.
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $order Order object.
	 * @param array     $args Array of args to pass to the delete method.
	 */
	public function delete( &$withdrawal, $args = array() ) {
		$id   = $withdrawal->get_id();
		$args = wp_parse_args(
			$args,
			array(
				'force_delete'     => false,
				'suppress_filters' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			do_action( 'eu_owb_woocommerce_before_delete_withdrawal', $id, $withdrawal );

			wp_delete_post( $id );

			$parent_order_id = $withdrawal->get_parent_id();

			if ( $parent_order_id > 0 ) {
				$withdrawal_cache_key = \WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'withdrawals' . $parent_order_id;
				wp_cache_delete( $withdrawal_cache_key, 'orders' );
			}

			$withdrawal->set_id( 0 );

			do_action( 'eu_owb_woocommerce_withdrawal_order_deleted', $withdrawal );
		} else {
			do_action( 'eu_owb_woocommerce_before_trash_withdrawal', $id, $withdrawal );

			wp_trash_post( $id );
			$withdrawal->set_status( 'trash' );

			do_action( 'eu_owb_woocommerce_withdrawal_order_trashed', $id );
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
	 * Read withdrawal data. Can be overridden by child classes to load other props.
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal Withdrawal object.
	 * @param object          $post_object Post object.
	 * @since 3.0.0
	 */
	protected function read_order_data( &$withdrawal, $post_object ) {
		parent::read_order_data( $withdrawal, $post_object );
		$id        = $withdrawal->get_id();
		$post_meta = get_post_meta( $id );
		$props     = Package::get_withdrawal_order_props( true );

		foreach ( $props as $meta_key => $prop ) {
			$setter = "set_{$prop}";

			if ( is_callable( array( $withdrawal, $setter ) ) ) {
				$value = metadata_exists( 'post', $id, $meta_key ) ? $post_meta[ $meta_key ][0] : null;

				$withdrawal->{$setter}( $value );
			}
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
		wp_untrash_post( $order->get_id() );

		return true;
	}

	/**
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal
	 *
	 * @return void
	 * @throws \WC_Data_Exception
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

		parent::create( $withdrawal );

		do_action( 'eu_owb_woocommerce_new_withdrawal_order', $withdrawal->get_id(), $withdrawal );
	}

	/**
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal
	 *
	 * @return void
	 */
	public function update( &$withdrawal ) {
		parent::update( $withdrawal );

		do_action( 'eu_owb_woocommerce_withdrawal_order_updated', $withdrawal->get_id(), $withdrawal );
	}

	/**
	 * Search order data for a term and return ids.
	 *
	 * @param  string $term Searched term.
	 * @return array of ids
	 */
	public function search_orders( $term ) {
		global $wpdb;

		$order_ids = array();

		$search_fields = array_map(
			'wc_clean',
			array(
				'_billing_address_index',
			)
		);

		if ( is_numeric( $term ) ) {
			$order_ids[] = absint( $term );
		}

		if ( ! empty( $search_fields ) ) {
			$order_ids = array_unique(
				array_merge(
					$order_ids,
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')", // @codingStandardsIgnoreLine
							'%' . $wpdb->esc_like( wc_clean( $term ) ) . '%'
						)
					)
				)
			);
		}

		return array_map( 'absint', $order_ids );
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal Withdrawal object.
	 *
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$withdrawal ) {
		$props_changed = $withdrawal->get_changes();
		$search_props  = $withdrawal->get_search_props();

		foreach ( $search_props as $prop => $search_value ) {
			if ( array_key_exists( $prop, $props_changed ) ) {
				$withdrawal->update_meta_data( '_billing_address_index', implode( ' ', array_filter( array_values( $search_props ) ) ) );
				break;
			}
		}

		parent::update_post_meta( $withdrawal );

		$updated_props     = array();
		$meta_key_to_props = Package::get_withdrawal_order_props( true );

		$props_to_update = $this->get_props_to_update( $withdrawal, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $withdrawal->{"get_$prop"}( 'edit' );

			switch ( $meta_key ) {
				case '_is_partial':
				case '_has_verified_email':
				case '_is_update':
				case '_is_guest':
					$value = wc_bool_to_string( $value );
					break;
				case '_date_confirmed':
				case '_date_rejected':
					$value = $value ? $value->getTimestamp() : null;
					break;
			}

			update_post_meta( $withdrawal->get_id(), $meta_key, $value );
			$updated_props[] = $prop;
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
