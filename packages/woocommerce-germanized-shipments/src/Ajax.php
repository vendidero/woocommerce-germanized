<?php

namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Admin\Admin;
use Vendidero\Germanized\Shipments\Admin\MetaBox;

/**
 * WC_Ajax class.
 */
class Ajax {

    /**
     * Hook in ajax handlers.
     */
    public static function init() {
        self::add_ajax_events();
    }

    /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
    public static function add_ajax_events() {
        $ajax_events_nopriv = array();

        foreach ( $ajax_events_nopriv as $ajax_event ) {
            add_action( 'wp_ajax_woocommerce_gzd_' . $ajax_event, array( __CLASS__, $ajax_event ) );
        }

        $ajax_events = array(
            'get_shipment_available_items',
            'get_shipment_available_return_items',
            'add_shipment_item',
	        'add_shipment_return',
            'add_shipment',
            'remove_shipment',
            'remove_shipment_item',
            'limit_shipment_item_quantity',
            'save_shipments',
            'sync_shipment_items',
            'validate_shipment_item_quantities',
            'json_search_orders',
	        'update_shipment_status',
	        'shipments_bulk_action_handle'
        );

        foreach ( $ajax_events as $ajax_event ) {
            add_action( 'wp_ajax_woocommerce_gzd_' . $ajax_event, array( __CLASS__, $ajax_event ) );
        }
    }

    public static function shipments_bulk_action_handle() {
    	$action = isset( $_POST['bulk_action'] ) ? wc_clean( $_POST['bulk_action'] ) : '';
	    $type   = isset( $_POST['type'] ) ? wc_clean( $_POST['type'] ) : 'simple';

	    check_ajax_referer( "woocommerce_gzd_shipments_{$action}", 'security' );

	    if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['step'] ) || ! isset( $_POST['ids'] ) ) {
		    wp_die( -1 );
	    }

	    $response_error = array(
		    'success' => false,
		    'message' => _x( 'There was an error while bulk processing shipments.', 'shipments', 'woocommerce-germanized' ),
	    );

	    $response = array(
		    'success' => true,
		    'message' => '',
	    );

	    $handlers = Admin::get_bulk_action_handlers();

	    if ( ! array_key_exists( $action, $handlers ) ) {
		    wp_send_json( $response_error );
	    }

	    $ids     = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();
	    $step    = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;

	    $handler = $handlers[ $action ];

	    if ( 1 === $step ) {
	    	$handler->reset( true );
	    }

		$handler->set_step( $step );
		$handler->set_ids( $ids );
		$handler->set_shipment_type( $type );

	    $handler->handle();

	    if ( $handler->get_percent_complete() >= 100 ) {
	    	$errors = $handler->get_notices( 'error' );

	    	if ( empty( $errors ) ) {
	    		$handler->add_notice( $handler->get_success_message(), 'success' );
	    		$handler->update_notices();
		    }

		    wp_send_json_success(
			    array(
				    'step'       => 'done',
				    'percentage' => 100,
				    'url'        => $handler->get_success_redirect_url(),
				    'type'       => $handler->get_shipment_type(),
			    )
		    );
	    } else {
		    wp_send_json_success(
			    array(
				    'step'       => ++$step,
				    'percentage' => $handler->get_percent_complete(),
				    'ids'        => $handler->get_ids(),
				    'type'       => $handler->get_shipment_type(),
			    )
		    );
	    }
    }

    /**
     * @param Order $order
     */
    private static function refresh_shipments( &$order ) {
        MetaBox::refresh_shipments( $order );
    }

    /**
     * @param Order $order
     * @param bool $shipment
     */
    private static function refresh_shipment_items( &$order, &$shipment = false ) {
        MetaBox::refresh_shipment_items( $order, $shipment );
    }

    /**
     * @param Order $order
     */
    private static function refresh_status( &$order ) {
        MetaBox::refresh_status( $order );
    }

    public static function update_shipment_status() {
	    if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'update-shipment-status' ) && isset( $_GET['status'], $_GET['shipment_id'] ) ) {
	    	$status   = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		    $shipment = wc_gzd_get_shipment( absint( wp_unslash( $_GET['shipment_id'] ) ) );

		    if ( wc_gzd_is_shipment_status( 'gzd-' . $status ) && $shipment ) {
			    $shipment->update_status( $status, true );
			    /**
			     * Action to indicate Shipment status change via WP Admin.
			     *
			     * @param integer $shipment_id The shipment id.
			     * @param string  $status The status to be switched to.
			     *
			     * @since 3.0.0
			     * @package Vendidero/Germanized/Shipments
			     */
			    do_action( 'woocommerce_gzd_updated_shipment_status', $shipment->get_id(), $status );
		    }
	    }

	    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-shipments' ) );
	    exit;
    }

    public static function remove_shipment() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $shipment_id = absint( $_POST['shipment_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $shipment->get_order() ) ) {
            wp_send_json( $response_error );
        }

        if ( $shipment->delete( true ) ) {
            $order_shipment->remove_shipment( $shipment_id );

            $response['shipment_id'] = $shipment_id;
            $response['fragments']   = array(
                '.order-shipping-status' => self::get_order_status_html( $order_shipment ),
            );

            self::send_json_success( $response, $order_shipment );
        } else {
            wp_send_json( $response_error );
        }
    }

    public static function add_shipment() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error while adding the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $order_id = absint( $_POST['order_id'] );

        if ( ! $order = wc_get_order( $order_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        self::refresh_shipment_items( $order_shipment );

        if ( ! $order_shipment->needs_shipping() ) {
            $response_error['message'] = _x( 'This order contains enough shipments already.', 'shipments', 'woocommerce-germanized' );
            wp_send_json( $response_error );
        }

        $shipment = wc_gzd_create_shipment( $order_shipment );

        if ( is_wp_error( $shipment ) ) {
            wp_send_json( $response_error );
        }

        $order_shipment->add_shipment( $shipment );

        // Mark as active
        $is_active = true;

        ob_start();
        include( Package::get_path() . '/includes/admin/views/html-order-shipment.php' );
        $html = ob_get_clean();

        $response['new_shipment'] = $html;
        $response['fragments']    = array(
            '.order-shipping-status' => self::get_order_status_html( $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

	public static function add_shipment_return() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success'      => true,
			'message'      => '',
			'new_shipment' => '',
		);

		$shipment_id = absint( $_POST['shipment_id'] );
		$items       = isset( $_POST['return_item'] ) ? (array) $_POST['return_item'] : array();

		if ( ! $parent_shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order = $parent_shipment->get_order() ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		$parent_shipment = $order_shipment->get_shipment( $shipment_id );
		// Make sure the parent knows the order instance and it's returns
		$parent_shipment->set_order_shipment( $order_shipment );

		self::refresh_shipment_items( $order_shipment );

		$shipment = wc_gzd_create_return_shipment( $parent_shipment, array( 'items' => $items ) );

		if ( is_wp_error( $shipment ) ) {
			wp_send_json( $response_error );
		}

		$order_shipment->add_shipment( $shipment );

		ob_start();
		include( Package::get_path() . '/includes/admin/views/html-order-shipment.php' );
		$html = ob_get_clean();

		$response['new_shipment'] = $html;

		self::send_json_success( $response, $order_shipment );
	}

    public static function validate_shipment_item_quantities() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $order_id = absint( $_POST['order_id'] );
        $active   = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

        if ( ! $order = wc_get_order( $order_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        $order_shipment->validate_shipments();

        $response['fragments'] = self::get_shipments_html( $order_shipment, $active );

        self::send_json_success( $response, $order_shipment );
    }

    public static function sync_shipment_items() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $shipment_id = absint( $_POST['shipment_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        $shipment = $order_shipment->get_shipment( $shipment_id );

        static::refresh_shipment_items( $order_shipment );

        if ( $shipment->is_editable() ) {
            $shipment = $order_shipment->get_shipment( $shipment_id );

            // Make sure we are working based on the current instance.
            $shipment->set_order_shipment( $order_shipment );
			$shipment->sync_items();
            $shipment->save();
        }

        ob_start();

        foreach( $shipment->get_items() as $item ) {
            include( Package::get_path() . '/includes/admin/views/html-order-shipment-item.php' );
        }

        $html = ob_get_clean();

        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .shipment-item-list:first' => '<div class="shipment-item-list">' . $html . '</div>',
            '#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    public static function json_search_orders() {
        ob_start();

        check_ajax_referer( 'search-orders', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 );
        }

        $term  = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';
        $limit = 0;

        if ( empty( $term ) ) {
            wp_die();
        }

        if ( ! is_numeric( $term ) ) {
            $ids = wc_order_search( $term );
        } else {
            global $wpdb;

            $ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT p1.ID FROM {$wpdb->posts} p1 WHERE p1.ID LIKE %s AND post_type = 'shop_order'", // @codingStandardsIgnoreLine
                    $wpdb->esc_like( wc_clean( $term ) ) . '%'
                )
            );
        }

        $found_orders = array();

        if ( ! empty( $_GET['exclude'] ) ) {
            $ids = array_diff( $ids, array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) );
        }

        foreach ( $ids as $id ) {
            if ( $order = wc_get_order( $id ) ) {
                $found_orders[ $id ] = sprintf(
                    esc_html_x( 'Order #%s', 'shipments', 'woocommerce-germanized' ),
                    $order->get_order_number()
                );
            }
        }

	    /**
	     * Filter to adjust found orders to filter Shipments.
	     *
	     * @param array $result The order search result.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        wp_send_json( apply_filters( 'woocommerce_gzd_json_search_found_shipment_orders', $found_orders ) );
    }

    private static function get_order_status_html( $order_shipment ) {
        $status_html = '<span class="order-shipping-status status-' . esc_attr( $order_shipment->get_shipping_status() ) . '">' . wc_gzd_get_shipment_order_shipping_status_name( $order_shipment->get_shipping_status() ) . '</span>';

        return $status_html;
    }

    public static function save_shipments() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $order_id = absint( $_POST['order_id'] );
        $active   = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

        if ( ! $order = wc_get_order( $order_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        // Refresh data
        self::refresh_shipments( $order_shipment );

        // Make sure that we are not applying more
        $order_shipment->validate_shipment_item_quantities();

        // Refresh statuses after adjusting quantities
        self::refresh_status( $order_shipment );

        $order_shipment->save();

        $response['fragments'] = self::get_shipments_html( $order_shipment, $active );

        self::send_json_success( $response, $order_shipment );
    }

    private static function get_shipments_html( $order_shipment, $active = 0 ) {
        ob_start();
        foreach( $order_shipment->get_simple_shipments() as $shipment ) {
            $is_active = false;

            if ( $active === $shipment->get_id() ) {
                $is_active = true;
            }

            include( Package::get_path() . '/includes/admin/views/html-order-shipment.php' );
        }
        $html = ob_get_clean();
        $html = '<div id="order-shipments-list" class="panel-inner">' . $html . '</div>';

        $fragments = array(
            '#order-shipments-list'  => $html,
            '.order-shipping-status' => self::get_order_status_html( $order_shipment ),
        );

        return $fragments;
    }

    public static function get_shipment_available_return_items() {
	    check_ajax_referer( 'edit-shipments', 'security' );

	    if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
		    wp_die( -1 );
	    }

	    $response_error = array(
		    'success' => false,
		    'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
	    );

	    $response = array(
		    'success' => true,
		    'message' => '',
		    'html'    => '',
	    );

	    $shipment_id = absint( $_POST['shipment_id'] );

	    if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
		    wp_send_json( $response_error );
	    }

	    if ( ! $order = $shipment->get_order() ) {
		    wp_send_json( $response_error );
	    }

	    if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
		    wp_send_json( $response_error );
	    }

	    static::refresh_shipments( $order_shipment );

	    $shipment->set_order_shipment( $order_shipment );

	    ob_start();
	    include( Package::get_path() . '/includes/admin/views/html-order-shipment-add-return-items.php' );
	    $response['html'] = ob_get_clean();

	    self::send_json_success( $response, $order_shipment );
    }

    public static function get_shipment_available_items() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
            'items'   => array(),
        );

        $shipment_id = absint( $_POST['shipment_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        if ( 'return' === $shipment->get_type() ) {
	        $shipment->set_order_shipment( $order_shipment );

	        $response['items'] = $shipment->get_parent()->get_available_items_for_return( array(
		        'shipment_id'        => $shipment->get_id(),
		        'disable_duplicates' => true,
	        ) );
        } else {
	        $response['items'] = $order_shipment->get_available_items_for_shipment( array(
		        'shipment_id'        => $shipment_id,
		        'disable_duplicates' => true,
	        ) );
        }

        self::send_json_success( $response, $order_shipment );
    }

    public static function add_shipment_item() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success'   => true,
            'message'   => '',
            'new_item'  => '',
        );

        $shipment_id      = absint( $_POST['shipment_id'] );
        $original_item_id = isset( $_POST['original_item_id'] ) ? absint( $_POST['original_item_id'] ) : 0;
        $item_quantity    = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : false;

        if ( false !== $item_quantity && $item_quantity === 0 ) {
            $item_quantity = 1;
        }

        if ( empty( $original_item_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        // Make sure we are working with the shipment from the order
        $shipment = $order_shipment->get_shipment( $shipment_id );

        if ( 'return' === $shipment->get_type() ) {
	        $item = self::add_shipment_return_item( $order_shipment, $shipment, $original_item_id, $item_quantity );
        } else {
        	$item = self::add_shipment_order_item( $order_shipment, $shipment, $original_item_id, $item_quantity );
        }

        if ( ! $item ) {
	        wp_send_json( $response_error );
        }

        ob_start();
        include( Package::get_path() . '/includes/admin/views/html-order-shipment-item.php' );
        $response['new_item'] = ob_get_clean();

        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

	/**
	 * @param Order $order_shipment
	 * @param ReturnShipment $shipment
	 * @param integer $parent_item_id
	 * @param integer $quantity
	 */
    private static function add_shipment_return_item( $order_shipment, $shipment, $parent_item_id, $quantity ) {
	    $response_error = array(
		    'success' => false,
		    'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
	    );

	    if ( ! $parent = $shipment->get_parent() ) {
		    wp_send_json( $response_error );
	    }
	    
	    if ( ! $parent_item = $parent->get_item( $parent_item_id ) ) {
		    wp_send_json( $response_error );
	    }

	    // No duplicates allowed
	    if ( $shipment->get_item_by_item_parent_id( $parent_item_id ) ) {
		    wp_send_json( $response_error );
	    }

	    // Check max quantity
	    $quantity_left = $parent->get_item_quantity_left_for_return( $parent_item_id );

	    if ( $quantity ) {
		    if ( $quantity > $quantity_left ) {
			    $quantity = $quantity_left;
		    }
	    } else {
		    $quantity = $quantity_left;
	    }

	    if ( $item = wc_gzd_create_return_shipment_item( $shipment, $parent_item, array( 'quantity' => $quantity ) ) ) {
		    $shipment->add_item( $item );
		    $shipment->save();
	    }

	    return $item;
    } 

	/**
	 * @param Order $order_shipment
	 * @param SimpleShipment $shipment
	 * @param integer $order_item_id
	 * @param integer $quantity
	 */
    private static function add_shipment_order_item( $order_shipment, $shipment, $order_item_id, $quantity ) {

	    $response_error = array(
		    'success' => false,
		    'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
	    );

    	$order = $order_shipment->get_order();

	    if ( ! $order_item = $order->get_item( $order_item_id ) ) {
		    wp_send_json( $response_error );
	    }

	    // No duplicates allowed
	    if ( $shipment->get_item_by_order_item_id( $order_item_id ) ) {
		    wp_send_json( $response_error );
	    }

	    // Check max quantity
	    $quantity_left = $order_shipment->get_item_quantity_left_for_shipping( $order_item );

	    if ( $quantity ) {
		    if ( $quantity > $quantity_left ) {
			    $quantity = $quantity_left;
		    }
	    } else {
		    $quantity = $quantity_left;
	    }

	    if ( $item = wc_gzd_create_shipment_item( $shipment, $order_item, array( 'quantity' => $quantity ) ) ) {
		    $shipment->add_item( $item );
		    $shipment->save();
	    }

	    return $item;
    }

    private static function get_item_count_html( $p_shipment, $p_order_shipment ) {
        $shipment       = $p_shipment;

        // Refresh the instance to make sure we are working with the same object
        $shipment->set_order_shipment( $p_order_shipment );

        ob_start();
        include( Package::get_path() . '/includes/admin/views/html-order-shipment-item-count.php' );
        $html = ob_get_clean();

        return $html;
    }

    public static function remove_shipment_item() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) || ! isset( $_POST['item_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success'   => true,
            'message'   => '',
            'item_id'   => '',
        );

        $shipment_id   = absint( $_POST['shipment_id'] );
        $item_id       = absint( $_POST['item_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $item = $shipment->get_item( $item_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $shipment->get_order_id() ) ) {
            wp_send_json( $response_error );
        }

        $shipment->remove_item( $item_id );
        $shipment->save();

        $response['item_id']   = $item_id;
        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    public static function limit_shipment_item_quantity() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) || ! isset( $_POST['item_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
        );

        $response = array(
            'success'      => true,
            'message'      => '',
            'max_quantity' => '',
            'item_id'      => '',
        );

        $shipment_id   = absint( $_POST['shipment_id'] );
        $item_id       = absint( $_POST['item_id'] );
        $quantity      = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 1;
        $quantity      = $quantity <= 0 ? 1 : $quantity;

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        // Make sure the shipment order gets notified about changes
        if ( ! $shipment = $order_shipment->get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $item = $shipment->get_item( $item_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_item = $order->get_item( $item->get_order_item_id() ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        $quantity_max = 0;

	    if ( 'return' === $shipment->get_type() ) {
	    	$shipment->set_order_shipment( $order_shipment );

		    $quantity_max = $shipment->get_parent()->get_item_quantity_left_for_return( $item->get_parent_id(), array(
			    'exclude_current_shipment' => true,
			    'shipment_id'              => $shipment->get_id(),
		    ) );
	    } else {
		    $quantity_max = $order_shipment->get_item_quantity_left_for_shipping( $order_item, array(
			    'exclude_current_shipment' => true,
			    'shipment_id'              => $shipment->get_id(),
		    ) );
	    }

        $response['item_id']      = $item_id;
        $response['max_quantity'] = $quantity_max;

        if ( $quantity > $quantity_max ) {
            $quantity = $quantity_max;
        }

        $shipment->get_item( $item_id )->set_quantity( $quantity );

        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    /**
     * @param $response
     * @param Order $order_shipment
     * @param Shipment|bool $shipment
     */
    private static function send_json_success( $response, $order_shipment ) {

        $available_items       = $order_shipment->get_available_items_for_shipment();
        $response['shipments'] = array();

        foreach( $order_shipment->get_shipments() as $shipment ) {

        	$shipment->set_order_shipment( $order_shipment );

            $response['shipments'][ $shipment->get_id() ] = array(
                'is_editable'   => $shipment->is_editable(),
                'needs_items'   => $shipment->needs_items( array_keys( $available_items ) ),
                'is_returnable' => $shipment->is_returnable(),
                'weight'        => wc_format_localized_decimal( $shipment->get_content_weight() ),
                'length'        => wc_format_localized_decimal( $shipment->get_content_length() ),
                'width'         => wc_format_localized_decimal( $shipment->get_content_width() ),
                'height'        => wc_format_localized_decimal( $shipment->get_content_height() ),
            );
        }

        $response['order_needs_new_shipments'] = $order_shipment->needs_shipping();

        wp_send_json( $response );
    }
}
