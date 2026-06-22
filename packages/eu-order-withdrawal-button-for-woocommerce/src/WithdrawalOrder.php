<?php

namespace Vendidero\OrderWithdrawalButton;

defined( 'ABSPATH' ) || exit;

class WithdrawalOrder extends \WC_Abstract_Order implements \ArrayAccess {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'order-withdrawal';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'order_withdrawal';

	/**
	 * @var null|\WC_Order
	 */
	protected $parent = null;

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'withdrawal_number'   => '',
		'date_confirmed'      => null,
		'date_rejected'       => null,
		'original_status'     => '',
		'rejection_reason'    => '',
		'is_partial'          => false,
		'is_guest'            => false,
		'has_verified_email'  => false,
		'order_number'        => '',
		'is_update'           => false,
		'refund_id'           => 0,
		'customer_id'         => 0,
		'verification_code'   => '',
		'email'               => '',
		'first_name'          => '',
		'last_name'           => '',
		'order_key'           => '',
		'customer_note'       => '',
		'customer_ip_address' => '',
		'customer_user_agent' => '',
		'billing_email'       => '',
	);

	protected $legacy_datastore_props = array();

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	public function __construct( $order = 0 ) {
		/**
		 * Use a tweak to prevent overriding the actual prop which has PHP 7.3 features in newer WC versions
		 */
		$this->item_types_to_group = array(
			'withdrawal' => 'withdrawal_lines',
		);

		parent::__construct( $order );
	}

	/**
	 * Get internal type (post type.)
	 *
	 * @return string
	 */
	public function get_type() {
		return 'shop_order_withdraw';
	}

	public function has_changes() {
		$has_changes = ! empty( $this->get_changes() );

		foreach ( $this->get_items() as $item ) {
			if ( ! empty( $item->get_changes() ) ) {
				$has_changes = true;
				break;
			}
		}

		return $has_changes;
	}

	public function get_withdrawal_number( $context = 'view' ) {
		return $this->get_prop( 'withdrawal_number', $context );
	}

	public function set_withdrawal_number( $value ) {
		$this->set_prop( 'withdrawal_number', $value );
	}

	public function get_customer_note( $context = 'view' ) {
		return $this->get_prop( 'customer_note', $context );
	}

	public function set_customer_note( $value ) {
		$this->set_prop( 'customer_note', $value );
	}

	public function get_additional_information( $context = 'view' ) {
		return $this->get_customer_note( $context );
	}

	public function set_additional_information( $value ) {
		$this->set_customer_note( $value );
	}

	public function get_email( $context = 'view' ) {
		$value = $this->get_prop( 'email', $context );

		if ( 'view' === $context && empty( $value ) ) {
			if ( $parent = $this->get_parent() ) {
				$value = $parent->get_billing_email();
			}
		}

		return $value;
	}

	public function set_email( $value ) {
		$this->set_prop( 'email', $value );
		$this->set_billing_email( $value );
	}

	public function set_billing_email( $value ) {
		$this->set_prop( 'billing_email', $value );
	}

	public function get_billing_email( $context = 'view' ) {
		return $this->get_email( $context );
	}

	public function get_current_verification_code() {
		if ( empty( $this->get_id() ) ) {
			return '';
		}

		$item_ids = array();

		foreach ( $this->get_items() as $item ) {
			$item_ids[] = (string) $item->get_parent_id() > 0 ? $item->get_parent_id() : $item->get_id();
		}

		$to_hash = implode(
			'|',
			array(
				(string) $this->get_id(),
				$this->get_formatted_full_name( false, 'edit' ),
				$this->get_email( 'edit' ),
				$this->get_additional_information(),
				$this->get_order_number(),
				(string) $this->get_date_received() ? $this->get_date_received()->getTimestamp() : 0,
				implode( '|', $item_ids ),
			)
		);

		return hash( 'sha256', $to_hash );
	}

	public function get_verification_code( $context = 'view' ) {
		$value = $this->get_prop( 'verification_code', $context );

		if ( '' === $value && 'view' === $context ) {
			$value = $this->get_current_verification_code();
		}

		return $value;
	}

	public function set_verification_code( $value ) {
		$this->set_prop( 'verification_code', $value );
	}

	public function get_customer_ip_address( $context = 'view' ) {
		return $this->get_prop( 'customer_ip_address', $context );
	}

	/**
	 * Set customer ip address.
	 *
	 * @param string $value Customer ip address.
	 * @return void
	 */
	public function set_customer_ip_address( $value ) {
		$this->set_prop( 'customer_ip_address', $value );
	}

	public function get_customer_user_agent( $context = 'view' ) {
		return $this->get_prop( 'customer_user_agent', $context );
	}

	/**
	 * Set customer user agent.
	 *
	 * @param string $value Customer user agent.
	 * @return void
	 */
	public function set_customer_user_agent( $value ) {
		$this->set_prop( 'customer_user_agent', $value );
	}

	protected function has_first_or_last_name() {
		return ! empty( $this->get_first_name( 'edit' ) ) || ! empty( $this->get_last_name( 'edit' ) );
	}

	public function get_billing_first_name( $context = 'view' ) {
		return $this->get_first_name( $context );
	}

	public function get_first_name( $context = 'view' ) {
		$value = $this->get_prop( 'first_name', $context );

		if ( 'view' === $context && empty( $value ) && ! $this->has_first_or_last_name() && $this->has_verified_email() ) {
			if ( $parent = $this->get_parent() ) {
				$value = $parent->get_billing_first_name();
			}
		}

		return $value;
	}

	public function set_first_name( $value ) {
		$this->set_prop( 'first_name', $value );
	}

	public function get_last_name( $context = 'view' ) {
		$value = $this->get_prop( 'last_name', $context );

		if ( 'view' === $context && empty( $value ) && ! $this->has_first_or_last_name() && $this->has_verified_email() ) {
			if ( $parent = $this->get_parent() ) {
				$value = $parent->get_billing_last_name();
			}
		}

		return $value;
	}

	public function get_billing_last_name( $context = 'view' ) {
		return $this->get_last_name( $context );
	}

	public function set_last_name( $value ) {
		$this->set_prop( 'last_name', $value );
	}

	public function has_items() {
		return count( $this->get_items() ) > 0;
	}

	/**
	 * Get a formatted full name.
	 *
	 * @return string
	 */
	public function get_formatted_full_name( $placeholder = false, $context = 'view' ) {
		$full_name_placeholder = $placeholder ? ( is_bool( $placeholder ) ? _x( 'Not specified', 'owb-full-name-placeholder', 'woocommerce-germanized' ) : $placeholder ) : '';
		$first_name            = $this->get_first_name( $context );
		$last_name             = $this->get_last_name( $context );

		if ( empty( $last_name ) && ! empty( $first_name ) ) {
			/* translators: 1: first name */
			$full_name = sprintf( _x( '%1$s', 'owb-first-name', 'woocommerce-germanized' ), $first_name ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
		} elseif ( empty( $first_name ) && ! empty( $last_name ) ) {
			/* translators: 1: last name */
			$full_name = sprintf( _x( '%1$s', 'owb-last-name', 'woocommerce-germanized' ), $last_name ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
		} elseif ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			/* translators: 1: last name 2: last name */
			$full_name = sprintf( _x( '%1$s %2$s', 'owb-full-name', 'woocommerce-germanized' ), $first_name, $last_name );
		}

		if ( empty( $full_name ) ) {
			$full_name = $full_name_placeholder;
		}

		return $full_name;
	}

	/**
	 * @param $context
	 *
	 * @return null|\WC_DateTime
	 */
	public function get_date_confirmed( $context = 'view' ) {
		return $this->get_prop( 'date_confirmed', $context );
	}

	public function set_date_confirmed( $date = null ) {
		$this->set_date_prop( 'date_confirmed', $date );
	}

	/**
	 * @param $context
	 *
	 * @return null|\WC_DateTime
	 */
	public function get_date_rejected( $context = 'view' ) {
		return $this->get_prop( 'date_rejected', $context );
	}

	public function set_date_rejected( $date = null ) {
		$this->set_date_prop( 'date_rejected', $date );
	}

	/**
	 * @param $context
	 *
	 * @return null|\WC_DateTime
	 */
	public function get_date_received( $context = 'view' ) {
		return $this->get_date_created( $context );
	}

	public function set_date_received( $date = null ) {
		$this->set_date_created( $date );
	}

	public function get_original_status( $context = 'view' ) {
		return $this->get_prop( 'original_status', $context );
	}

	public function set_original_status( $status ) {
		$this->set_prop( 'original_status', $status );
	}

	public function get_rejection_reason( $context = 'view' ) {
		return $this->get_prop( 'rejection_reason', $context );
	}

	public function set_rejection_reason( $reason ) {
		$this->set_prop( 'rejection_reason', $reason );
	}

	public function get_is_partial( $context = 'view' ) {
		return $this->get_prop( 'is_partial', $context );
	}

	public function is_partial() {
		return $this->get_is_partial();
	}

	public function set_is_partial( $is_partial ) {
		$this->set_prop( 'is_partial', wc_string_to_bool( $is_partial ) );
	}

	public function get_is_guest( $context = 'view' ) {
		$is_guest = $this->get_prop( 'is_guest', $context );

		if ( 'view' === $context && ! empty( $this->get_customer_id() ) ) {
			$is_guest = false;
		}

		return $is_guest;
	}

	public function is_guest() {
		return $this->get_is_guest();
	}

	public function set_is_guest( $is_guest ) {
		$this->set_prop( 'is_guest', wc_string_to_bool( $is_guest ) );
	}

	public function get_has_verified_email( $context = 'view' ) {
		return $this->get_prop( 'has_verified_email', $context );
	}

	public function has_verified_email() {
		return $this->get_has_verified_email();
	}

	public function set_has_verified_email( $verified_email ) {
		$this->set_prop( 'has_verified_email', wc_string_to_bool( $verified_email ) );
	}

	public function get_order_number( $context = 'view' ) {
		$value = $this->get_prop( 'order_number', $context );

		if ( 'view' === $context && '' === $value ) {
			if ( $parent = $this->get_parent() ) {
				$value = $parent->get_order_number();
			} else {
				$value = _x( 'Not specified', 'owb-order-number', 'woocommerce-germanized' );
			}
		}

		return $value;
	}

	public function set_order_number( $order_number ) {
		$this->set_prop( 'order_number', $order_number );
	}

	public function has_parent() {
		return $this->get_parent() ? true : false;
	}

	public function get_parent() {
		if ( is_null( $this->parent ) && $this->get_parent_id() > 0 ) {
			$this->parent = wc_get_order( $this->get_parent_id() );
		}

		return $this->parent;
	}

	public function get_is_update( $context = 'view' ) {
		return $this->get_prop( 'is_update', $context );
	}

	public function is_update() {
		return $this->get_is_update();
	}

	public function set_is_update( $is_update ) {
		$this->set_prop( 'is_update', wc_string_to_bool( $is_update ) );
	}

	public function get_refund_id( $context = 'view' ) {
		return $this->get_prop( 'refund_id', $context );
	}

	public function set_refund_id( $refund_id ) {
		$this->set_prop( 'refund_id', absint( $refund_id ) );
	}

	/**
	 * Get customer_id.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_customer_id( $context = 'view' ) {
		return $this->get_prop( 'customer_id', $context );
	}

	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_id', absint( $value ) );
	}

	/**
	 * Alias for get_customer_id().
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_customer_id( $context );
	}

	/**
	 * Get the user associated with the order. False for guests.
	 *
	 * @return \WP_User|false
	 */
	public function get_user() {
		return $this->get_user_id() ? get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Get order key.
	 *
	 * @since  3.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_order_key( $context = 'view' ) {
		return $this->get_prop( 'order_key', $context );
	}

	/**
	 * Set order key.
	 *
	 * @param string $value Max length 22 chars.
	 * @return void
	 */
	public function set_order_key( $value ) {
		$this->set_prop( 'order_key', substr( $value, 0, 22 ) );
	}

	public function set_parent_id( $value, $trigger_change = true ) {
		$parent_id_changed = false;

		if ( true === $this->object_read && $this->get_id() > 0 && $value !== $this->get_parent_id( 'edit' ) ) {
			$parent_id_changed = true;
		}

		parent::set_parent_id( $value );

		$this->parent = null;

		/**
		 * Update props automatically in case parent order changes.
		 */
		if ( $parent_id_changed && $trigger_change ) {
			$this->update_parent( $this->get_parent() );
		}
	}

	public function remove_item( $item_id ) {
		if ( ! is_numeric( $item_id ) && isset( $this->items['withdrawal_lines'][ $item_id ] ) ) {
			unset( $this->items['withdrawal_lines'][ $item_id ] );
		} else {
			return parent::remove_item( $item_id );
		}
	}

	public function update_items( $items = array() ) {
		$is_full_withdrawal = true;

		if ( $parent = $this->get_parent() ) {
			if ( ! empty( $items ) ) {
				$items_available = eu_owb_get_withdrawable_order_items( $parent );

				foreach ( $items_available as $item_id => $item ) {
					if ( ! array_key_exists( $item_id, $items ) ) {
						$is_full_withdrawal = false;
						continue;
					}

					$item_data = wp_parse_args(
						$items[ $item_id ],
						array(
							'quantity' => 1,
						)
					);

					$quantity = min( $item_data['quantity'], $item['quantity'] );

					if ( $quantity < $item['quantity'] ) {
						$is_full_withdrawal = false;
					}

					$items[ $item_id ]['quantity'] = eu_owb_get_stock_amount( $quantity );
				}

				$items = array_intersect_key( $items, $items_available );
			} else {
				$items = eu_owb_get_withdrawable_order_items( $parent );
			}

			foreach ( $items as $item_id => $item_data ) {
				if ( $item = $parent->get_item( $item_id, false ) ) {
					if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
						continue;
					}

					$withdrawal_item = $this->get_item_by_parent_id( $item_id );
					$is_new          = false;

					if ( ! $withdrawal_item ) {
						$withdrawal_item = new \Vendidero\OrderWithdrawalButton\WithdrawalItem();
						$is_new          = true;
					}

					$withdrawal_item->from_order_item( $item );
					$withdrawal_item->set_quantity( $item_data['quantity'] );

					if ( $is_new ) {
						$this->add_item( $withdrawal_item );
					}
				}
			}

			/**
			 * Remove non-existing items
			 */
			foreach ( $this->get_items() as $item_key => $item ) {
				$parent_id = $item->get_parent_id();

				if ( ! array_key_exists( $parent_id, $items ) ) {
					$this->remove_item( $item->get_id() > 0 ? $item->get_id() : $item_key );
				}
			}
		} else {
			foreach ( $this->get_items() as $item_key => $item ) {
				if ( $item->get_parent_id() > 0 ) {
					$this->remove_item( $item->get_id() > 0 ? $item->get_id() : $item_key );
				}
			}

			$is_full_withdrawal = empty( $this->get_items() ) ? true : false;
		}

		$this->set_is_partial( ! $is_full_withdrawal );
	}

	/**
	 * @return WithdrawalItem|false
	 */
	public function get_item_by_parent_id( $item_parent_id ) {
		foreach ( $this->get_items() as $item ) {
			if ( $item->get_parent_id() === $item_parent_id ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * @param \WC_Order|null $order
	 *
	 * @return void
	 */
	public function update_parent( $order, $items = array() ) {
		if ( $order ) {
			$this->set_parent_id( $order->get_id(), false );
			$this->set_order_number( $order->get_order_number() );
			$this->set_has_verified_email( eu_owb_custom_email_matches_order_email( $order, $this->get_email() ) );
			$this->set_original_status( $order->get_status() );
		} else {
			$this->set_parent_id( 0, false );
			$this->set_has_verified_email( false );
			$this->set_original_status( '' );
		}

		$this->delete_meta_data( '_original_request_order_id' );
		$this->delete_meta_data( '_has_multiple_matching_orders' );
		$this->set_is_update( false );
		$this->set_is_partial( false );

		$this->update_items( $items );
	}

	public function calculate_taxes( $args = array() ) {}

	public function calculate_shipping() {}

	public function calculate_totals( $and_taxes = true ) {}

	public function recalculate_coupons() {}

	/**
	 * Return the order statuses without wc- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {
			$status = 'owb-requested';
		}

		return $status;
	}

	/**
	 * Set order status.
	 *
	 * @since 3.0.0
	 * @param string       $new_status    Status to change the order to. No internal wc- prefix is required.
	 * @param bool         $manual_update Is this a manual order status change?.
	 * @return array
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		/**
		 * Backwards compatibility when $manual was the 2. argument.
		 */
		if ( is_bool( $note ) ) {
			$manual_update = $note;
			$note          = '';
		}

		$new_status = 'trash' === $new_status ? $new_status : 'owb-' . Package::maybe_remove_withdrawal_order_status_prefix( $new_status );
		$result     = parent::set_status( $new_status );

		if ( true === $this->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->status_transition = array(
				'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'manual' => (bool) $manual_update,
				'note'   => $note,
			);

			$this->maybe_set_date_rejected();
			$this->maybe_set_date_confirmed();
		}

		return $result;
	}

	/**
	 * Maybe set date rejected.
	 *
	 * @return void
	 */
	public function maybe_set_date_rejected() {
		if ( $this->has_status( 'rejected' ) ) {
			$this->set_date_rejected( time() );
		}
	}

	public function get_search_props() {
		return array(
			'order_number'      => $this->get_order_number(),
			'withdrawal_number' => $this->get_withdrawal_number(),
			'id'                => $this->get_id(),
			'first_name'        => $this->get_first_name(),
			'last_name'         => $this->get_last_name(),
			'email'             => $this->get_email(),
		);
	}

	public function has_status( $status ) {
		$statuses = (array) $status;

		foreach ( $statuses as $k => $status ) {
			if ( 'owb-' !== substr( $status, 0, 4 ) ) {
				$statuses[ $k ] = 'owb-' . $status;
			}
		}

		return apply_filters( 'eu_owb_woocommerce_order_withdrawal_has_status', in_array( $this->get_status(), $statuses, true ), $this, $statuses );
	}

	/**
	 * Maybe set date confirmed.
	 *
	 * @return void
	 */
	public function maybe_set_date_confirmed() {
		if ( $this->has_status( 'confirmed' ) ) {
			$this->set_date_confirmed( time() );
		}
	}

	public function get_edit_order_url() {
		if ( $parent = $this->get_parent() ) {
			return $parent->get_edit_order_url();
		} else {
			return add_query_arg( array( 's' => $this->get_id() ), Package::get_withdrawals_url() );
		}
	}

	/**
	 * Adds a note (comment) to the order. Order must exist.
	 *
	 * @param  string $note              Note to add.
	 * @param  int    $is_customer_note  Is this a note for the customer?.
	 * @param  bool   $added_by_user     Was the note added by a user?.
	 * @param  array  $meta_data         Optional meta data to add to the note. Key value pairs.
	 * @return int                       Comment ID.
	 */
	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false, $meta_data = array() ) {
		if ( ! $this->get_id() ) {
			return 0;
		}

		if ( is_user_logged_in() && current_user_can( 'edit_shop_orders', $this->get_id() ) && $added_by_user ) {
			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
		} else {
			$comment_author        = _x( 'WooCommerce', 'owb', 'woocommerce-germanized' );
			$comment_author_email  = strtolower( _x( 'WooCommerce', 'owb', 'woocommerce-germanized' ) ) . '@';
			$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : 'noreply.com'; // WPCS: input var ok.
			$comment_author_email  = sanitize_email( $comment_author_email );
		}
		$commentdata = apply_filters(
			'eu_owb_woocommerce_new_order_note_data',
			array(
				'comment_post_ID'      => $this->get_id(),
				'comment_author'       => $comment_author,
				'comment_author_email' => $comment_author_email,
				'comment_author_url'   => '',
				'comment_content'      => $note,
				'comment_agent'        => 'WooCommerce',
				'comment_type'         => 'order_note',
				'comment_parent'       => 0,
				'comment_approved'     => 1,
			),
			array(
				'order_id'         => $this->get_id(),
				'is_customer_note' => $is_customer_note,
			)
		);

		$comment_id = wp_insert_comment( $commentdata );

		if ( $is_customer_note ) {
			add_comment_meta( $comment_id, 'is_customer_note', 1 );

			do_action(
				'eu_owb_woocommerce_new_customer_note',
				array(
					'order_id'      => $this->get_id(),
					'customer_note' => $commentdata['comment_content'],
				)
			);
		}

		if ( ! empty( $meta_data ) && is_array( $meta_data ) ) {
			foreach ( $meta_data as $key => $value ) {
				if ( is_scalar( $value ) ) {
					update_comment_meta( $comment_id, sanitize_key( $key ), sanitize_text_field( $value ) );
				}
			}
		}

		do_action( 'eu_owb_woocommerce_order_note_added', $comment_id, $this );

		return $comment_id;
	}

	/**
	 * Add an order note for status transition
	 *
	 * @param string $note          Note to be added giving status transition from and to details.
	 * @param array  $transition    Details of the status transition.
	 * @return int                  Comment ID.
	 */
	private function add_status_transition_note( $note, $transition ) {
		return $this->add_order_note( trim( $transition['note'] . ' ' . $note ), 0, $transition['manual'], array( 'note_group' => 'order_update' ) );
	}

	/**
	 * List order notes (public) for the customer.
	 *
	 * @return array
	 */
	public function get_customer_order_notes() {
		$notes = array();
		$args  = array(
			'post_id' => $this->get_id(),
			'approve' => 'approve',
			'type'    => '',
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );

		$comments = get_comments( $args );

		foreach ( $comments as $comment ) {
			if ( ! get_comment_meta( $comment->comment_ID, 'is_customer_note', true ) ) {
				continue;
			}
			$comment->comment_content = make_clickable( $comment->comment_content );
			$notes[]                  = $comment;
		}

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );

		return $notes;
	}

	/**
	 * Updates status of order immediately.
	 *
	 * @uses self::set_status()
	 * @param string $new_status    Status to change the order to. No internal wc- prefix is required.
	 * @param bool   $manual        Is this a manual order status change?.
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		if ( ! $this->get_id() ) { // Order must exist.
			return false;
		}

		/**
		 * Backwards compatibility when $manual was the 2. argument.
		 */
		if ( is_bool( $note ) ) {
			$manual = $note;
			$note   = '';
		}

		try {
			$this->set_status( $new_status, $note, $manual );
			$this->save();
		} catch ( \Exception $e ) {
			Package::log( sprintf( 'Error updating status for withdrawal order #%d', $this->get_id() ), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * @param $types
	 *
	 * @return WithdrawalItem[]
	 */
	public function get_items( $types = 'withdrawal' ) {
		$types = 'withdrawal';

		return parent::get_items( $types );
	}

	protected function get_items_key( $item ) {
		$key = '';

		if ( is_a( $item, '\Vendidero\OrderWithdrawalButton\WithdrawalItem' ) ) {
			$key = 'withdrawal_lines';
		}

		return $key;
	}

	/**
	 * Save data to the database.
	 *
	 * @since 3.0.0
	 * @return int order ID
	 */
	public function save() {
		parent::save();
		$this->status_transition();

		return $this->get_id();
	}

	protected function get_valid_statuses() {
		return array_keys( Package::get_withdrawal_statuses() );
	}

	/**
	 * Handle the status transition.
	 *
	 * @return void
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {
				do_action( 'eu_owb_woocommerce_withdrawal_order_status_' . $status_transition['to'], $this->get_id(), $this, $status_transition );

				// Add a status transition note unless `note` was explicitly set to false.
				if ( false !== $status_transition['note'] ) {
					if ( ! empty( $status_transition['from'] ) ) {
						$this->add_status_transition_note( sprintf( _x( 'Withdrawal status changed from %1$s to %2$s.', 'owb', 'woocommerce-germanized' ), Package::get_withdrawal_status_name( $status_transition['from'] ), Package::get_withdrawal_status_name( $status_transition['to'] ) ), $status_transition );
					} else {
						/* translators: %s: new order status */
						$this->add_status_transition_note( sprintf( _x( 'Withdrawal status set to %s.', 'owb', 'woocommerce-germanized' ), Package::get_withdrawal_status_name( $status_transition['to'] ) ), $status_transition );
					}
				}

				if ( ! empty( $status_transition['from'] ) ) {
					do_action( 'eu_owb_woocommerce_withdrawal_order_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );
					do_action( 'eu_owb_woocommerce_withdrawal_order_status_changed', $this->get_id(), $status_transition['from'], $status_transition['to'], $this );
				}
			} catch ( \Exception $e ) {
				Package::log( sprintf( 'Status transition of withdrawal order #%d errored!', $this->get_id() ), 'error' );
			}
		}
	}

	/**
	 * Get all class data in array format.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_data() {
		return array_merge(
			array(
				'id'            => $this->get_id(),
				'date_received' => $this->get_date_received(),
			),
			$this->data,
			array(
				'meta_data'        => $this->get_meta_data(),
				'line_items'       => array(),
				'tax_lines'        => array(),
				'shipping_lines'   => array(),
				'fee_lines'        => array(),
				'coupon_lines'     => array(),
				'withdrawal_lines' => $this->get_items(),
			)
		);
	}

	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		$legacy = array(
			'id',
			'date_received',
			'date_confirmed',
			'date_rejected',
			'request_email',
			'original_status',
			'status',
			'items',
			'meta',
			'rejection_reason',
			'is_partial',
			'has_verified_email',
			'is_update',
			'is_guest',
			'has_refund',
			'refund_id',
		);

		return isset( $legacy[ $offset ] );
	}

	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$getter = "get_$offset";

		if ( 'id' === $offset ) {
			return $this->get_withdrawal_number();
		} elseif ( 'date_rejected' === $offset || 'date_confirmed' === $offset || 'date_received' === $offset ) {
			return $this->get_date_rejected() ? $this->get_date_rejected()->getTimestamp() : null;
		} elseif ( 'request_email' === $offset ) {
			return $this->get_email();
		} elseif ( 'status' === $offset ) {
			return Package::maybe_remove_withdrawal_order_status_prefix( $this->get_status() );
		} elseif ( in_array( $offset, array( 'is_partial', 'has_verified_email', 'is_update', 'is_guest', 'has_refund' ), true ) ) {
			$result = 'no';

			if ( is_callable( array( $this, $getter ) ) ) {
				$result = wc_bool_to_string( $this->$getter() );
			}

			return $result;
		} elseif ( 'items' === $offset ) {
			$items = array();

			foreach ( $this->get_items() as $item ) {
				$items[ $item->get_parent_id() ] = array(
					'quantity' => $item->get_quantity(),
				);
			}

			return $items;
		} elseif ( 'meta' === $offset ) {
			$meta = array(
				'first_name' => $this->get_first_name(),
				'last_name'  => $this->get_last_name(),
			);

			foreach ( $this->get_meta_data() as $meta_obj ) {
				$meta[ ( '_' === substr( $meta_obj->key, 0, 1 ) ) ? substr( $meta_obj->key, 1 ) : $meta_obj->key ] = $meta_obj->value;
			}

			return $meta;
		} elseif ( is_callable( array( $this, $getter ) ) ) {
			return $this->$getter();
		}

		return false;
	}

	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$setter = "set_{$offset}";

		if ( is_callable( array( $this, $setter ) ) ) {
			$this->$setter( $value );
		}
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$setter = "set_{$offset}";

		if ( is_callable( array( $this, $setter ) ) ) {
			$this->$setter( null );
		}
	}

	public function __toString() {
		$data = $this->get_data();
		$data = array_diff_key(
			$data,
			array(
				'date_created'       => '',
				'currency'           => '',
				'discount_total'     => '',
				'discount_tax'       => '',
				'shipping_total'     => '',
				'shipping_tax'       => '',
				'cart_tax'           => '',
				'total'              => '',
				'total_tax'          => '',
				'line_items'         => '',
				'tax_lines'          => '',
				'shipping_lines'     => '',
				'fee_lines'          => '',
				'coupon_lines'       => '',
				'prices_include_tax' => '',
			)
		);

		$data['withdrawal_lines'] = array_map(
			function ( $item ) {
				return $item->get_data();
			},
			$data['withdrawal_lines']
		);

		return wp_json_encode( $data );
	}
}
