<?php

defined( 'ABSPATH' ) || exit;

/**
 * @since 3.9.0
 * @author vendidero
 */
class WC_GZD_REST_Product_Manufacturers_Controller extends WC_REST_Terms_Controller {

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
	protected $rest_base = 'products/manufacturers';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = 'product_manufacturer';

	/**
	 * Prepare a single delivery Time output for response.
	 *
	 * @param WP_Term $item Term object.
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$manufacturer = wc_gzd_get_manufacturer( $item );

		$data = array(
			'id'                   => (int) $item->term_id,
			'name'                 => $item->name,
			'slug'                 => $item->slug,
			'description'          => $item->description,
			'count'                => (int) $item->count,
			'formatted_address'    => $manufacturer->get_formatted_address( 'edit' ),
			'formatted_eu_address' => $manufacturer->get_formatted_eu_address( 'edit' ),
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
				'id'                   => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'                 => array(
					'description' => __( 'Resource name.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'                 => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'description'          => array(
					'description' => __( 'HTML description of the resource.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'count'                => array(
					'description' => __( 'Number of published products for the resource.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'formatted_address'    => array(
					'description' => __( 'The current formatted address.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'formatted_eu_address' => array(
					'description' => __( 'Alternate formatted EU address.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Update term meta fields.
	 *
	 * @param WP_Term         $term    Term object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	protected function update_term_meta_fields( $term, $request ) {
		if ( isset( $request['formatted_address'] ) ) {
			update_term_meta( $term->term_id, 'formatted_address', wc_gzd_sanitize_html_text_field( $request['formatted_address'] ) );
		}

		if ( isset( $request['formatted_eu_address'] ) ) {
			update_term_meta( $term->term_id, 'formatted_eu_address', wc_gzd_sanitize_html_text_field( $request['formatted_eu_address'] ) );
		}

		return true;
	}
}
