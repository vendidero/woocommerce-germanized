<?php

namespace Vendidero\Germanized\Shipments\Rest;

use Vendidero\Germanized\Shipments\Labels\Label;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentFactory;
use Vendidero\Germanized\Shipments\ShipmentItem;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class ShipmentsController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipments';

	/**
	 * Registers rest routes for this controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => _x( 'Whether to bypass trash and force deletion.', 'shipments', 'woocommerce-germanized' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/label',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_label' ),
					'permission_callback' => array( $this, 'get_label_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_label' ),
					'permission_callback' => array( $this, 'create_label_permissions_check' ),
					'args'                => array(
						array(
							'description' => _x( 'Shipment label.', 'shipment', 'woocommerce-germanized' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => false,
							'type'        => 'object',
							'properties'  => array(
								'type'       => 'object',
								'properties' => array(
									'key'   => array(
										'description' => _x( 'Label field key.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value' => array(
										'description' => _x( 'Label field value.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'mixed',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_label' ),
					'permission_callback' => array( $this, 'delete_label_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => _x( 'Whether to bypass trash and force deletion.', 'shipments', 'woocommerce-germanized' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_label_schema' ),
			)
		);
	}

	/**
	 * Get object.
	 *
	 * @param  int|Shipment $id Object ID.
	 * @return Shipment Shipment object or WP_Error object.
	 */
	protected function get_object( $id ) {
		return $this->get_shipment( $id );
	}

	/**
	 * Get object permalink.
	 *
	 * @param  Shipment $shipment Object.
	 * @return string
	 */
	protected function get_permalink( $shipment ) {
		return $shipment->get_edit_shipment_url();
	}

	private static function get_shipment_statuses() {
		return array_map( array( 'Vendidero\Germanized\Shipments\Api', 'remove_status_prefix' ), array_keys( wc_gzd_get_shipment_statuses() ) );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment', 'read', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_view', _x( 'Sorry, you are not allowed to view this resource.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves a shipment by id.
	 *
	 * @param int $shipment_id
	 *
	 * @return Shipment|false
	 */
	private function get_shipment( $shipment_id ) {
		$shipment = wc_gzd_get_shipment( $shipment_id );

		return $shipment;
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! $this->check_permissions() ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_view', _x( 'Sorry, you cannot list resources.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	protected function check_permissions( $object_type = 'shipment', $context = 'read', $object_id = 0 ) {
		if ( 'delete' === $context || 'edit' === $context ) {
			$post_type_object = get_post_type_object( 'shop_order' );
			$capped           = 'delete' === $context ? $post_type_object->cap->delete_posts : $post_type_object->cap->edit_posts;
			$permission       = current_user_can( $capped, $object_id );
		} else {
			$permission = wc_rest_check_post_permissions( 'shop_order', $context );
		}

		return apply_filters( 'woocommerce_gzd_shipments_rest_check_permissions', $permission, $object_type, $context, $object_id );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function get_items( $request ) {
		$prepared_args = array(
			'limit'       => $request['per_page'],
			'paginate'    => true,
			'type'        => $request['type'],
			'order_id'    => $request['order_id'],
			'search'      => $request['search'],
			'status'      => $request['status'],
			'order'       => $request['order'],
			'orderby'     => $request['orderby'],
			'count_total' => true,
		);

		if ( ! empty( $prepared_args['search'] ) ) {
			$prepared_args['search'] = '*' . $prepared_args['search'] . '*';
		}

		if ( ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['limit'];
		}

		$objects   = array();
		$query     = new \Vendidero\Germanized\Shipments\ShipmentQuery( $prepared_args );
		$shipments = $query->get_shipments();

		if ( ! empty( $shipments ) ) {
			foreach ( $shipments as $shipment ) {
				if ( ! $this->check_permissions( 'shipment', 'read', $shipment->get_id() ) ) {
					continue;
				}

				$objects[] = $this->prepare_object_for_response( $shipment, $request );
			}
		}

		$page      = (int) $request['page'];
		$max_pages = $query->get_max_num_pages();

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query->get_total() );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base          = $this->rest_base;
		$attrib_prefix = '(?P<';
		if ( strpos( $base, $attrib_prefix ) !== false ) {
			$attrib_names = array();
			preg_match( '/\(\?P<[^>]+>.*\)/', $base, $attrib_names, PREG_OFFSET_CAPTURE );
			foreach ( $attrib_names as $attrib_name_match ) {
				$beginning_offset = strlen( $attrib_prefix );
				$attrib_name_end  = strpos( $attrib_name_match[0], '>', $attrib_name_match[1] );
				$attrib_name      = substr( $attrib_name_match[0], $beginning_offset, $attrib_name_end - $beginning_offset );
				if ( isset( $request[ $attrib_name ] ) ) {
					$base = str_replace( "(?P<$attrib_name>[\d]+)", $request[ $attrib_name ], $base );
				}
			}
		}
		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment', 'edit', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_edit', _x( 'Sorry, you are not allowed to edit this resource.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to create a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment', 'create' ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_view', _x( 'Sorry, you are not allowed to create resources.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * @param $request
	 * @param boolean $creating
	 *
	 * @return Shipment
	 * @throws \WC_REST_Exception
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : false;

		// Type is the most important part here because we need to be using the correct class and methods.
		if ( isset( $request['type'] ) ) {
			$shipment = ShipmentFactory::get_shipment( $id, $request['type'] );
		} elseif ( $id ) {
			$shipment = wc_gzd_get_shipment( $id );
		} else {
			$shipment = ShipmentFactory::get_shipment( false );
		}

		if ( ! $shipment ) {
			throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_id', _x( 'There was an error while creating the shipment.', 'shipments', 'woocommerce-germanized' ) );
		}

		if ( isset( $request['order_id'] ) ) {
			$shipment->set_order_id( absint( wp_unslash( $request['order_id'] ) ) );
		}

		if ( $creating ) {
			$order_shipment = $shipment->get_order_shipment();

			if ( ! $order_shipment ) {
				throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_id', _x( 'This order does not exist.', 'shipments', 'woocommerce-germanized' ) );
			}

			if ( 'return' === $shipment->get_type() ) {
				if ( ! $order_shipment->needs_return() ) {
					throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_id', _x( 'This order does need a return.', 'shipments', 'woocommerce-germanized' ) );
				}
			} else {
				if ( ! $order_shipment->needs_shipping() ) {
					throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_id', _x( 'This order does need shipping.', 'shipments', 'woocommerce-germanized' ) );
				}
			}

			$shipment->sync();
		}

		if ( isset( $request['shipping_provider'] ) ) {
			$provider = wc_clean( wp_unslash( $request['shipping_provider'] ) );

			if ( $provider = wc_gzd_get_shipping_provider( $provider ) ) {
				$shipment->set_shipping_provider( $provider );
			}
		}

		if ( isset( $request['shipping_method'] ) ) {
			$shipment->set_shipping_method( wc_clean( wp_unslash( $request['shipping_method'] ) ) );
		}

		if ( isset( $request['packaging_id'] ) ) {
			$packaging_id = absint( wp_unslash( $request['packaging_id'] ) );

			if ( $packaging = wc_gzd_get_packaging( $packaging_id ) ) {
				$shipment->set_packaging_id( $packaging_id );
			}
		}

		if ( isset( $request['packaging_weight'] ) ) {
			$shipment->set_packaging_weight( wc_clean( wp_unslash( $request['packaging_weight'] ) ) );
		}

		if ( isset( $request['tracking_id'] ) ) {
			$shipment->set_tracking_id( wc_clean( wp_unslash( $request['tracking_id'] ) ) );
		}

		if ( isset( $request['dimensions'] ) ) {
			if ( isset( $request['dimensions']['length'] ) ) {
				$shipment->set_length( wc_clean( wp_unslash( $request['dimensions']['length'] ) ) );
			}
			if ( isset( $request['dimensions']['width'] ) ) {
				$shipment->set_width( wc_clean( wp_unslash( $request['dimensions']['width'] ) ) );
			}
			if ( isset( $request['dimensions']['height'] ) ) {
				$shipment->set_height( wc_clean( wp_unslash( $request['dimensions']['height'] ) ) );
			}
		}

		if ( isset( $request['dimension_unit'] ) ) {
			$shipment->set_dimension_unit( wc_clean( wp_unslash( $request['dimension_unit'] ) ) );
		}

		if ( isset( $request['weight'] ) ) {
			$shipment->set_weight( wc_clean( wp_unslash( $request['weight'] ) ) );
		}

		if ( isset( $request['weight_unit'] ) ) {
			$shipment->set_weight_unit( wc_clean( wp_unslash( $request['weight_unit'] ) ) );
		}

		if ( isset( $request['address'] ) && is_array( $request['address'] ) ) {
			$shipment->set_address( wc_clean( wp_unslash( $request['address'] ) ) );

			if ( isset( $request['address']['country'] ) ) {
				$shipment->set_country( wc_clean( wp_unslash( $request['address']['country'] ) ) );
			}
		}

		if ( isset( $request['total'] ) ) {
			$shipment->set_total( wc_clean( wp_unslash( $request['total'] ) ) );
		}

		if ( isset( $request['subtotal'] ) ) {
			$shipment->set_subtotal( wc_clean( wp_unslash( $request['subtotal'] ) ) );
		}

		if ( isset( $request['additional_total'] ) ) {
			$shipment->set_additional_total( wc_clean( wp_unslash( $request['additional_total'] ) ) );
		}

		if ( is_a( $shipment, 'Vendidero\Germanized\Shipments\ReturnShipment' ) ) {
			if ( isset( $request['sender_address'] ) && is_array( $request['sender_address'] ) ) {
				$shipment->set_sender_address( wc_clean( wp_unslash( $request['sender_address'] ) ) );
			}

			if ( isset( $request['is_customer_requested'] ) ) {
				$shipment->set_is_customer_requested( wc_clean( wp_unslash( $request['is_customer_requested'] ) ) );
			}
		}

		if ( ! empty( $request['date_created'] ) ) {
			$date = rest_parse_date( wc_clean( wp_unslash( $request['date_created'] ) ) );

			if ( $date ) {
				$shipment->set_date_created( $date );
			}
		}

		if ( ! empty( $request['date_created_gmt'] ) ) {
			$date = rest_parse_date( wc_clean( wp_unslash( $request['date_created_gmt'] ) ), true );

			if ( $date ) {
				$shipment->set_date_created( $date );
			}
		}

		if ( ! empty( $request['est_delivery_date'] ) ) {
			$date = rest_parse_date( wc_clean( wp_unslash( $request['est_delivery_date'] ) ) );

			if ( $date ) {
				$shipment->set_est_delivery_date( $date );
			}
		}

		if ( ! empty( $request['est_delivery_date_gmt'] ) ) {
			$date = rest_parse_date( wc_clean( wp_unslash( $request['est_delivery_date_gmt'] ) ), true );

			if ( $date ) {
				$shipment->set_est_delivery_date( $date );
			}
		}

		if ( ! empty( $request['date_sent'] ) ) {
			$date = rest_parse_date( wc_clean( wp_unslash( $request['date_sent'] ) ) );

			if ( $date ) {
				$shipment->set_date_sent( $date );
			}
		}

		if ( ! empty( $request['date_sent_gmt'] ) ) {
			$date = rest_parse_date( wc_clean( wp_unslash( $request['date_sent_gmt'] ) ), true );

			if ( $date ) {
				$shipment->set_date_sent( $date );
			}
		}

		if ( ! empty( $request['items'] ) && is_array( $request['items'] ) ) {
			foreach ( $request['items'] as $item ) {
				if ( is_array( $item ) ) {
					if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
						$shipment->remove_item( $item['id'] );
					} else {
						$this->set_item( $shipment, $item );
					}
				}
			}
		} elseif ( $creating ) {
			$shipment->sync_items();
		}

		// Update the status at last
		if ( isset( $request['status'] ) ) {
			$status = str_replace( 'gzd-', '', wc_clean( wp_unslash( $request['status'] ) ) );

			if ( in_array( $status, self::get_shipment_statuses(), true ) ) {
				$shipment->set_status( $status );
			}
		}

		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$meta = wc_clean( wp_unslash( $meta ) );

				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$shipment->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}

		if ( $shipment->get_item_count() <= 0 ) {
			$shipment->delete( true );

			throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_id', _x( 'This shipment does not contain any items and was deleted.', 'shipments', 'woocommerce-germanized' ) );
		}

		/**
		 * Filters a shipment before it is inserted via the REST API.
		 *
		 * @param Shipment        $shipment Shipment object.
		 * @param WP_REST_Request $request  Request object.
		 */
		return apply_filters( 'woocommerce_gzd_rest_pre_insert_shipment_object', $shipment, $request );
	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @param Shipment $shipment order object.
	 * @param array    $posted item provided in the request body.
	 *
	 * @throws \WC_REST_Exception If item ID is not associated with order.
	 */
	protected function set_item( $shipment, $posted ) {
		if ( ! empty( $posted['id'] ) ) {
			$action = 'update';
		} else {
			$action = 'create';
		}

		$item = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $shipment->get_item( absint( $posted['id'] ) );

			if ( ! $item ) {
				throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_item_id', _x( 'Shipment item ID provided is not associated with shipment.', 'shipments', 'woocommerce-germanized' ), 400 );
			}
		}

		if ( is_null( $item ) ) {
			if ( 'return' === $shipment->get_type() ) {
				$item = new \Vendidero\Germanized\Shipments\ShipmentReturnItem();
			} else {
				$item = new \Vendidero\Germanized\Shipments\ShipmentItem();
			}
		}

		if ( isset( $posted['order_item_id'] ) ) {
			$item->set_order_item_id( absint( wp_unslash( $posted['order_item_id'] ) ) );
		}

		$item->set_shipment( $shipment );

		/**
		 * Sync quantity first.
		 */
		if ( 'create' === $action ) {
			$quantity      = isset( $posted['quantity'] ) ? absint( wp_unslash( $posted['quantity'] ) ) : -1;
			$quantity_left = 0;

			if ( $order_shipment = $shipment->get_order_shipment() ) {
				if ( 'return' === $shipment->get_type() ) {
					$quantity_left = $order_shipment->get_item_quantity_left_for_returning( $item->get_order_item_id() );
				} elseif ( $order_item = $item->get_order_item() ) {
					$quantity_left = $order_shipment->get_item_quantity_left_for_shipping( $order_item );
				}

				if ( -1 !== $quantity ) {
					if ( $quantity > $quantity_left ) {
						$quantity = $quantity_left;
					}
				} else {
					$quantity = $quantity_left;
				}
			}

			if ( $quantity <= 0 ) {
				throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_item_id', _x( 'This order item does not need shipping/returning.', 'shipments', 'woocommerce-germanized' ), 400 );
			}

			if ( $shipment->get_item_by_order_item_id( $item->get_order_item_id() ) ) {
				throw new \WC_REST_Exception( 'woocommerce_gzd_rest_invalid_item_id', _x( 'The order item is already associated with another item.', 'shipments', 'woocommerce-germanized' ), 400 );
			}

			$item->sync( array( 'quantity' => $quantity ) );
		} else {
			if ( $order_shipment = $shipment->get_order_shipment() ) {
				$quantity      = isset( $posted['quantity'] ) ? absint( wp_unslash( $posted['quantity'] ) ) : $item->get_quantity();
				$quantity_left = 0;

				if ( 'return' === $shipment->get_type() ) {
					$quantity_left = $order_shipment->get_item_quantity_left_for_returning(
						$item->get_order_item_id(),
						array(
							'exclude_current_shipment' => true,
							'shipment_id'              => $shipment->get_id(),
						)
					);
				} elseif ( $order_item = $item->get_order_item() ) {
					$quantity_left = $order_shipment->get_item_quantity_left_for_shipping(
						$order_item,
						array(
							'exclude_current_shipment' => true,
							'shipment_id'              => $shipment->get_id(),
						)
					);
				}

				if ( $quantity > $quantity_left ) {
					$quantity = $quantity_left;
				}

				if ( $quantity <= 0 ) {
					$shipment->remove_item( $item->get_id() );
					return;
				}

				$shipment->update_item_quantity( $item->get_id(), $quantity );
			}
		}

		$props_to_set = array(
			'name',
			'product_id',
			'sku',
			'total',
			'subtotal',
			'weight',
			'hs_code',
			'manufacture_country',
			'return_reason_code',
		);

		foreach ( $props_to_set as $prop ) {
			$setter = "set_{$prop}";

			if ( isset( $posted[ $prop ] ) && is_callable( array( $item, $setter ) ) ) {
				$item->{$setter}( wc_clean( wp_unslash( $posted[ $prop ] ) ) );
			}
		}

		if ( isset( $posted['dimensions'] ) && is_array( $posted['dimensions'] ) ) {
			if ( isset( $posted['dimensions']['length'] ) ) {
				$item->set_length( wc_clean( wp_unslash( $posted['dimensions']['length'] ) ) );
			}
			if ( isset( $posted['dimensions']['width'] ) ) {
				$item->set_width( wc_clean( wp_unslash( $posted['dimensions']['width'] ) ) );
			}
			if ( isset( $posted['dimensions']['height'] ) ) {
				$item->set_height( wc_clean( wp_unslash( $posted['dimensions']['height'] ) ) );
			}
		}

		if ( isset( $posted['attributes'] ) && is_array( $posted['attributes'] ) ) {
			$attributes_to_save = array();

			foreach ( $posted['attributes'] as $attribute ) {
				$attribute = wc_clean( wp_unslash( $attribute ) );
				$attribute = wp_parse_args(
					$attribute,
					array(
						'key'                => '',
						'value'              => '',
						'label'              => '',
						'order_item_meta_id' => 0,
					)
				);

				$attributes_to_save[] = array_intersect_key( $attribute, array_flip( array( 'key', 'value', 'label', 'order_item_meta_id' ) ) );
			}

			$item->set_attributes( $attributes_to_save );
		}

		if ( ! empty( $posted['meta_data'] ) && is_array( $posted['meta_data'] ) ) {
			foreach ( $posted['meta_data'] as $meta ) {
				$meta = wc_clean( wp_unslash( $meta ) );

				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$item->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}

		do_action( 'woocommerce_gzd_rest_set_shipment_item', $item, $posted );

		// If creating the shipment, add the item to it.
		if ( 'create' === $action ) {
			$shipment->add_item( $item );
		} else {
			$item->save();
		}
	}

	/**
	 * Helper method to check if the resource ID associated with the provided item is null.
	 * Items can be deleted by setting the resource ID to null.
	 *
	 * @param array $item Item provided in the request body.
	 * @return bool True if the item resource ID is null, false otherwise.
	 */
	protected function item_is_null( $item ) {
		$keys = array( 'order_item_id', 'name', 'product_id' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $item ) && is_null( $item[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return Shipment|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			$object->save();

			return $this->get_object( $object->get_id() );
		} catch ( \WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_gzd_rest_shipment_exists', _x( 'Cannot create existing shipment.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 400 ) );
		}

		$object = $this->save_object( $request, true );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		try {
			$this->update_additional_fields_for_object( $object, $request );

			/**
			 * Fires after a single object is created or updated via the REST API.
			 *
			 * @param Shipment        $shipment  Inserted object.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating object, false when updating.
			 */
			do_action( 'woocommerce_gzd_rest_insert_shipment_object', $object, $request, true );
		} catch ( \WC_Data_Exception $e ) {
			$object->delete();
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			$object->delete();
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ) );

		return $response;
	}

	/**
	 * Update a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$shipment = $this->get_object( (int) $request['id'] );

		if ( ! $shipment || 0 === $shipment->get_id() ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_invalid_id', _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 400 ) );
		}

		$shipment = $this->save_object( $request, false );

		if ( is_wp_error( $shipment ) ) {
			return $shipment;
		}

		try {
			$this->update_additional_fields_for_object( $shipment, $request );

			/**
			 * Fires after a single shipment is created or updated via the REST API.
			 *
			 * @param Shipment        $shipment    Inserted object.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating object, false when updating.
			 */
			do_action( 'woocommerce_gzd_rest_insert_shipment_object', $shipment, $request, false );
		} catch ( \WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $shipment, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  3.0.0
	 * @param  Shipment        $shipment  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $shipment, $request ) {
		$context       = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$this->request = $request;
		$data          = self::prepare_shipment( $shipment, $context );
		$data          = $this->add_additional_fields_to_object( $data, $request );
		$data          = $this->filter_response_by_context( $data, $context );
		$response      = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $shipment, $request ) );

		/**
		 * Filter the shipment data for a response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Shipment         $shipment   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'woocommerce_gzd_rest_prepare_shipment_object', $response, $shipment, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param Shipment        $shipment  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $shipment, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $shipment->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_invalid_id', _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment', 'delete', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_delete', _x( 'Sorry, you are not allowed to delete this resource.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function delete_item( $request ) {
		$force    = (bool) $request['force'];
		$shipment = $this->get_shipment( (int) $request['id'] );

		if ( ! $shipment ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_invalid_id', _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 404 ) );
		}

		if ( ! $shipment->delete( $force ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_delete', _x( 'The shipment cannot be deleted.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( self::prepare_shipment( $shipment ) );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function get_label_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment_label', 'read', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_view', _x( 'Sorry, you are not allowed to view this resource.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function get_label( $request ) {
		$shipment = $this->get_shipment( (int) $request['id'] );

		if ( ! $shipment ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_invalid_id', _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 404 ) );
		}

		$label = $shipment->get_label();

		if ( ! $label ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_invalid_id', _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 404 ) );
		}

		$label_data = self::prepare_label( $label );

		return rest_ensure_response( $label_data );
	}

	public function delete_label( $request ) {
		$force    = (bool) $request['force'];
		$shipment = $this->get_shipment( (int) $request['id'] );

		if ( ! $shipment ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_invalid_id', _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 404 ) );
		}

		$label = $shipment->get_label();

		if ( ! $label || ! $label->delete( $force ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_delete', _x( 'The label cannot be deleted.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( self::prepare_label( $label ) );
	}

	public function create_label( $request ) {
		$shipment = $this->get_shipment( (int) $request['id'] );

		if ( ! $shipment ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_invalid_id', _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 404 ) );
		}

		if ( ! $shipment->supports_label() || ! $shipment->needs_label() ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_label_exists', _x( 'Label already exists, please delete first.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 404 ) );
		}

		$request->set_param( 'context', 'edit' );

		$args   = wc_clean( wp_unslash( $request['args'] ) );
		$args   = empty( $args ) ? false : $args;
		$result = $shipment->create_label( $args );

		if ( is_wp_error( $result ) ) {
			$result = wc_gzd_get_shipment_error( $result );
		}

		if ( is_wp_error( $result ) && ! $result->is_soft_error() ) {
			$message = implode( ' | ', $result->get_error_messages() );

			return new WP_Error( 'woocommerce_gzd_rest_shipment_label_create', $message, array( 'status' => 500 ) );
		}

		$label = $shipment->get_label();

		if ( ! $label ) {
			return new WP_Error( 'woocommerce_gzd_rest_shipment_label_create', _x( 'There was an error creating the label.', 'shipments', 'woocommerce-germanized' ), array( 'status' => 500 ) );
		}

		$response = self::prepare_label( $label, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d/label', $this->namespace, $this->rest_base, $label->get_shipment_id() ) ) );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function create_label_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment_label', 'create' ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_delete', _x( 'Sorry, you are not allowed to create resources.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function delete_label_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment_label', 'delete', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_gzd_rest_cannot_delete', _x( 'Sorry, you are not allowed to delete this resource.', 'shipments', 'woocommerce-germanized' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 * @since 4.7.0
	 */
	public function get_item_schema() {
		return $this->add_additional_fields_schema( self::get_single_item_schema() );
	}

	/**
	 * @param Shipment $shipment
	 * @param string $context
	 * @param bool|int $dp
	 *
	 * @return array
	 */
	public static function prepare_shipment( $shipment, $context = 'view', $dp = false ) {
		$item_data = array();

		foreach ( $shipment->get_items() as $item ) {
			$item_data[] = array(
				'id'                  => $item->get_id(),
				'name'                => $item->get_name( $context ),
				'order_item_id'       => $item->get_order_item_id( $context ),
				'product_id'          => $item->get_product_id( $context ),
				'sku'                 => $item->get_sku( $context ),
				'quantity'            => $item->get_quantity( $context ),
				'total'               => wc_format_decimal( $item->get_total( $context ), $dp ),
				'subtotal'            => wc_format_decimal( $item->get_subtotal( $context ), $dp ),
				'weight'              => wc_format_decimal( $item->get_weight( $context ), $dp ),
				'dimensions'          => array(
					'length' => wc_format_decimal( $item->get_length( $context ), $dp ),
					'width'  => wc_format_decimal( $item->get_width( $context ), $dp ),
					'height' => wc_format_decimal( $item->get_height( $context ), $dp ),
				),
				'hs_code'             => $item->get_hs_code( $context ),
				'manufacture_country' => $item->get_manufacture_country( $context ),
				'attributes'          => $item->get_attributes( $context ),
				'meta_data'           => $item->get_meta_data(),
			);
		}

		return array(
			'id'                    => $shipment->get_id(),
			'shipment_number'       => $shipment->get_shipment_number(),
			'date_created'          => wc_rest_prepare_date_response( $shipment->get_date_created( $context ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $shipment->get_date_created( $context ) ),
			'date_sent'             => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ), false ),
			'date_sent_gmt'         => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ) ),
			'est_delivery_date'     => wc_rest_prepare_date_response( $shipment->get_est_delivery_date( $context ), false ),
			'est_delivery_date_gmt' => wc_rest_prepare_date_response( $shipment->get_est_delivery_date( $context ) ),
			'total'                 => wc_format_decimal( $shipment->get_total( $context ), $dp ),
			'subtotal'              => wc_format_decimal( $shipment->get_subtotal( $context ), $dp ),
			'additional_total'      => wc_format_decimal( $shipment->get_additional_total( $context ), $dp ),
			'order_id'              => $shipment->get_order_id( $context ),
			'order_number'          => $shipment->get_order_number(),
			'weight'                => wc_format_decimal( $shipment->get_weight( $context ), $dp ),
			'content_weight'        => wc_format_decimal( $shipment->get_content_weight(), $dp ),
			'weight_unit'           => $shipment->get_weight_unit( $context ),
			'packaging_id'          => $shipment->get_packaging_id( $context ),
			'packaging_weight'      => $shipment->get_packaging_weight( $context ),
			'status'                => $shipment->get_status( $context ),
			'tracking_id'           => $shipment->get_tracking_id( $context ),
			'tracking_url'          => $shipment->get_tracking_url(),
			'shipping_provider'     => $shipment->get_shipping_provider( $context ),
			'content_dimensions'    => array(
				'length' => wc_format_decimal( $shipment->get_content_length(), $dp ),
				'width'  => wc_format_decimal( $shipment->get_content_width(), $dp ),
				'height' => wc_format_decimal( $shipment->get_content_height(), $dp ),
			),
			'dimensions'            => array(
				'length' => wc_format_decimal( $shipment->get_length( $context ), $dp ),
				'width'  => wc_format_decimal( $shipment->get_width( $context ), $dp ),
				'height' => wc_format_decimal( $shipment->get_height( $context ), $dp ),
			),
			'package_dimensions'    => array(
				'length' => wc_format_decimal( $shipment->get_package_length(), $dp ),
				'width'  => wc_format_decimal( $shipment->get_package_width(), $dp ),
				'height' => wc_format_decimal( $shipment->get_package_height(), $dp ),
			),
			'dimension_unit'        => $shipment->get_dimension_unit( $context ),
			'address'               => $shipment->get_address( $context ),
			'sender_address'        => 'return' === $shipment->get_type() ? $shipment->get_sender_address( $context ) : array(),
			'is_customer_requested' => 'return' === $shipment->get_type() ? $shipment->get_is_customer_requested( $context ) : false,
			'items'                 => $item_data,
			'meta_data'             => $shipment->get_meta_data(),
		);
	}

	/**
	 * @param Label $label
	 *
	 * @return
	 */
	private static function get_label_file( $label, $file_type = '' ) {
		$result = array(
			'file'     => '',
			'filename' => $label->get_filename( $file_type ),
			'path'     => $label->get_path( 'view', $file_type ),
			'type'     => $file_type,
		);

		if ( $file = $label->get_file( $file_type ) ) {
			try {
				$content        = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$result['file'] = chunk_split( base64_encode( $content ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			} catch ( \Exception $ex ) {
				$result['file'] = '';
			}
		}

		return $result;
	}

	/**
	 * @param Label $label
	 * @param string $context
	 * @param bool|int $dp
	 *
	 * @return array
	 */
	public static function prepare_label( $label, $context = 'view', $dp = false ) {
		$label_data = array(
			'id'                    => $label->get_id(),
			'date_created'          => wc_rest_prepare_date_response( $label->get_date_created( $context ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $label->get_date_created( $context ) ),
			'weight'                => wc_format_decimal( $label->get_weight( $context ), $dp ),
			'net_weight'            => wc_format_decimal( $label->get_net_weight( $context ), $dp ),
			'dimensions'            => array(
				'length' => wc_format_decimal( $label->get_length( $context ), $dp ),
				'width'  => wc_format_decimal( $label->get_width( $context ), $dp ),
				'height' => wc_format_decimal( $label->get_height( $context ), $dp ),
			),
			'shipment_id'           => $label->get_shipment_id( $context ),
			'parent_id'             => $label->get_parent_id( $context ),
			'product_id'            => $label->get_product_id( $context ),
			'number'                => $label->get_number( $context ),
			'type'                  => $label->get_type(),
			'shipping_provider'     => $label->get_shipping_provider( $context ),
			'created_via'           => $label->get_created_via( $context ),
			'services'              => $label->get_services( $context ),
			'additional_file_types' => array(),
			'files'                 => array( self::get_label_file( $label ) ),
		);

		foreach ( $label->get_additional_file_types() as $file_type ) {
			if ( 'default' === $file_type ) {
				continue;
			}

			$label_file = self::get_label_file( $label, $file_type );

			if ( ! empty( $label_file['file'] ) ) {
				$label_data['files'][]                 = $label_file;
				$label_data['additional_file_types'][] = $file_type;
			}
		}

		return $label_data;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['offset']   = array(
			'description'       => _x( 'Offset the result set by a specific number of items.', 'shipments', 'woocommerce-germanized' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']    = array(
			'default'           => 'desc',
			'description'       => _x( 'Order sort attribute ascending or descending.', 'shipments', 'woocommerce-germanized' ),
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']  = array(
			'default'           => 'date_created',
			'description'       => _x( 'Sort collection by object attribute.', 'shipments', 'woocommerce-germanized' ),
			'enum'              => array(
				'country',
				'status',
				'tracking_id',
				'date_created',
				'order_id',
				'weight',
			),
			'sanitize_callback' => 'sanitize_key',
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order_id'] = array(
			'description'       => _x( 'Limit result set to shipments belonging to a certain order id.', 'shipments', 'woocommerce-germanized' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status']   = array(
			'description'       => _x( 'Limit result set to shipments having a certain status.', 'shipments', 'woocommerce-germanized' ),
			'enum'              => self::get_shipment_statuses(),
			'sanitize_callback' => 'sanitize_key',
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['type']     = array(
			'description'       => _x( 'Limit result set to shipments of a certain type.', 'shipments', 'woocommerce-germanized' ),
			'default'           => 'simple',
			'enum'              => wc_gzd_get_shipment_types(),
			'sanitize_callback' => 'sanitize_key',
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}

	/**
	 * Get the schema of a single shipment
	 *
	 * @return array
	 */
	public static function get_single_item_schema() {
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		return array(
			'description' => _x( 'Single shipment.', 'shipment', 'woocommerce-germanized' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => false,
			'type'        => 'object',
			'properties'  => array(
				'id'                    => array(
					'description' => _x( 'Shipment ID.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipment_number'       => array(
					'description' => _x( 'Shipment number.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'order_id'              => array(
					'description' => _x( 'Shipment order id.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'order_number'          => array(
					'description' => _x( 'Shipment order number.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'                => array(
					'description' => _x( 'Shipment status.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => self::get_shipment_statuses(),
				),
				'tracking_id'           => array(
					'description' => _x( 'Shipment tracking id.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'tracking_url'          => array(
					'description' => _x( 'Shipment tracking url.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_provider'     => array(
					'description' => _x( 'Shipment shipping provider.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'          => array(
					'description' => _x( "The date the shipment was created, in the site's timezone.", 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'      => array(
					'description' => _x( 'The date the shipment was created, as GMT.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_sent'             => array(
					'description' => _x( "The date the shipment was sent, in the site's timezone.", 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_sent_gmt'         => array(
					'description' => _x( 'The date the shipment was sent, as GMT.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'est_delivery_date'     => array(
					'description' => _x( "The estimated delivery date of the shipment, in the site's timezone.", 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'est_delivery_date_gmt' => array(
					'description' => _x( 'The estimated delivery date of the shipment, as GMT.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'type'                  => array(
					'description' => _x( 'Shipment type, e.g. simple or return.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => wc_gzd_get_shipment_types(),
				),
				'is_customer_requested' => array(
					'description' => _x( 'Return shipment is requested by customer.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'sender_address'        => array(
					'description' => _x( 'Return sender address.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name'               => array(
							'description' => _x( 'First name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'                => array(
							'description' => _x( 'Last name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'                  => array(
							'description' => _x( 'Company name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'                => array(
							'description' => _x( 'Address line 1', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'                => array(
							'description' => _x( 'Address line 2', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'                     => array(
							'description' => _x( 'City name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'                    => array(
							'description' => _x( 'ISO code or name of the state, province or district.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'                 => array(
							'description' => _x( 'Postal code.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'                  => array(
							'description' => _x( 'Country code in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'customs_reference_number' => array(
							'description' => _x( 'Customs reference number.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'weight'                => array(
					'description' => _x( 'Shipment weight.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'content_weight'        => array(
					'description' => _x( 'Shipment content weight.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'content_dimensions'    => array(
					'description' => _x( 'Shipment content dimensions.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'length' => array(
							'description' => _x( 'Shipment content length.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'width'  => array(
							'description' => _x( 'Shipment content width.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'height' => array(
							'description' => _x( 'Shipment content height.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'weight_unit'           => array(
					'description' => _x( 'Shipment weight unit.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => $weight_unit,
				),
				'packaging_id'          => array(
					'description' => _x( 'Shipment packaging id.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'packaging_weight'      => array(
					'description' => _x( 'Shipment packaging weight.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'total'                 => array(
					'description' => _x( 'Shipment total.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'subtotal'              => array(
					'description' => _x( 'Shipment subtotal.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'additional_total'      => array(
					'description' => _x( 'Shipment additional total.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'version'               => array(
					'description' => _x( 'Shipment version.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'shipping_method'       => array(
					'description' => _x( 'Shipment shipping method.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'dimensions'            => array(
					'description' => _x( 'Shipment dimensions.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'length' => array(
							'description' => _x( 'Shipment length.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width'  => array(
							'description' => _x( 'Shipment width.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							'description' => _x( 'Shipment height.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'package_dimensions'    => array(
					'description' => _x( 'Shipment package dimensions.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'length' => array(
							'description' => _x( 'Shipment package length.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'readonly'    => true,
							'context'     => array( 'view', 'edit' ),
						),
						'width'  => array(
							'description' => _x( 'Shipment package width.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'readonly'    => true,
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							'description' => _x( 'Shipment package height.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'readonly'    => true,
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'dimension_unit'        => array(
					'description' => _x( 'Shipment dimension unit.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => $dimension_unit,
				),
				'address'               => array(
					'description' => _x( 'Shipping address.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name'               => array(
							'description' => _x( 'First name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'                => array(
							'description' => _x( 'Last name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'                  => array(
							'description' => _x( 'Company name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'                => array(
							'description' => _x( 'Address line 1', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'                => array(
							'description' => _x( 'Address line 2', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'                     => array(
							'description' => _x( 'City name.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'                    => array(
							'description' => _x( 'ISO code or name of the state, province or district.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'                 => array(
							'description' => _x( 'Postal code.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'                  => array(
							'description' => _x( 'Country code in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'customs_reference_number' => array(
							'description' => _x( 'Customs reference number.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'meta_data'             => array(
					'description' => _x( 'Meta data.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => _x( 'Meta ID.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => _x( 'Meta key.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => _x( 'Meta value.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'items'                 => array(
					'description' => _x( 'Shipment items.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                  => array(
								'description' => _x( 'Item ID.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'name'                => array(
								'description' => _x( 'Item name.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'order_item_id'       => array(
								'description' => _x( 'Order Item ID.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'product_id'          => array(
								'description' => _x( 'Product ID.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'quantity'            => array(
								'description' => _x( 'Quantity.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'weight'              => array(
								'description' => _x( 'Item weight.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'sku'                 => array(
								'description' => _x( 'Item SKU.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total'               => array(
								'description' => _x( 'Item total.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'subtotal'            => array(
								'description' => _x( 'Item subtotal.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'hs_code'             => array(
								'description' => _x( 'Item HS Code (customs).', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'manufacture_country' => array(
								'description' => _x( 'Item country of manufacture in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'dimensions'          => array(
								'description' => _x( 'Item dimensions.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'object',
								'context'     => array( 'view', 'edit' ),
								'properties'  => array(
									'length' => array(
										'description' => _x( 'Item length.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'width'  => array(
										'description' => _x( 'Item width.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'height' => array(
										'description' => _x( 'Item height.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
							'attributes'          => array(
								'description' => _x( 'Item attributes.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'object',
								'context'     => array( 'view', 'edit' ),
								'properties'  => array(
									'key'                => array(
										'description' => _x( 'Attribute key.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value'              => array(
										'description' => _x( 'Attribute value.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'label'              => array(
										'description' => _x( 'Attribute label.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'order_item_meta_id' => array(
										'description' => _x( 'Order item meta id.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
							'meta_data'           => array(
								'description' => _x( 'Shipment item meta data.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => _x( 'Meta ID.', 'shipments', 'woocommerce-germanized' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key'   => array(
											'description' => _x( 'Meta key.', 'shipments', 'woocommerce-germanized' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => _x( 'Meta value.', 'shipments', 'woocommerce-germanized' ),
											'type'        => 'mixed',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
			),
		);
	}

	public function get_public_item_label_schema() {
		return array(
			'description' => _x( 'Shipment label.', 'shipment', 'woocommerce-germanized' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => false,
			'type'        => 'object',
			'properties'  => array(
				'id'                    => array(
					'description' => _x( 'Label ID.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'          => array(
					'description' => _x( "The date the label was created, in the site's timezone.", 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'      => array(
					'description' => _x( 'The date the label was created, as GMT.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipment_id'           => array(
					'description' => _x( 'Shipment id.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id'             => array(
					'description' => _x( 'Parent id.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'product_id'            => array(
					'description' => _x( 'Label product id.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'number'                => array(
					'description' => _x( 'Label number.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'shipping_provider'     => array(
					'description' => _x( 'Shipping provider.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'weight'                => array(
					'description' => _x( 'Weight.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'net_weight'            => array(
					'description' => _x( 'Net weight.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'created_via'           => array(
					'description' => _x( 'Created via.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'is_trackable'          => array(
					'description' => _x( 'Is trackable?', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'additional_file_types' => array(
					'description' => _x( 'Additional file types', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
					),
				),
				'files'                 => array(
					'description' => _x( 'Label file data.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'context'    => array( 'view', 'edit' ),
						'readonly'   => true,
						'type'       => 'object',
						'properties' => array(
							'path'     => array(
								'description' => _x( 'File path.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'filename' => array(
								'description' => _x( 'File name.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'file'     => array(
								'description' => _x( 'The file data (base64 encoded).', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'binary',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'type'     => array(
								'description' => _x( 'File type.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'type'                  => array(
					'description' => _x( 'Label type, e.g. simple or return.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'dimensions'            => array(
					'description' => _x( 'Label dimensions.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'length' => array(
							'description' => _x( 'Label length.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width'  => array(
							'description' => _x( 'Label width.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							'description' => _x( 'Label height.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'services'              => array(
					'description' => _x( 'Label services.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'meta_data'             => array(
					'description' => _x( 'Label meta data.', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => _x( 'Meta ID.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => _x( 'Meta key.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => _x( 'Meta value.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);
	}
}
