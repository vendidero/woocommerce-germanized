<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_GZD_REST_Product_Delivery_Times_Controller
 *
 * @since 1.7.0
 * @author vendidero, Daniel Huesken
 */
class WC_GZD_REST_Product_Delivery_Times_V1_Controller extends WC_REST_Terms_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/delivery_times';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = 'product_delivery_time';

	/**
	 * Prepare a single delivery Time output for response.
	 *
	 * @param WP_Term $item Term object.
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {

		$data = array(
			'id'          => (int) $item->term_id,
			'name'        => $item->name,
			'slug'        => $item->slug,
			'description' => $item->description,
			'count'       => (int) $item->count,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item, $request ) );

		return apply_filters( "woocommerce_rest_prepare_{$this->taxonomy}", $response, $item, $request );
	}

	/**
	 * Get the Category schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->taxonomy,
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'        => array(
					'description' => __( 'Resource name.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'description' => array(
					'description' => __( 'HTML description of the resource.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'count'       => array(
					'description' => __( 'Number of published products for the resource.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
