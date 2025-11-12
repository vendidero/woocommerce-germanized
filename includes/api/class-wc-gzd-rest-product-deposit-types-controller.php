<?php

defined( 'ABSPATH' ) || exit;

/**
 * @since 3.9.0
 * @author vendidero
 */
class WC_GZD_REST_Product_Deposit_Types_Controller extends WC_REST_Terms_Controller {

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
	protected $rest_base = 'products/deposit_types';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = 'product_deposit_type';

	/**
	 * Prepare a single delivery Time output for response.
	 *
	 * @param WP_Term $item Term object.
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$packaging = WC_germanized()->deposit_types->get_packaging( $item );

		$data = array(
			'id'                           => (int) $item->term_id,
			'name'                         => $item->name,
			'slug'                         => $item->slug,
			'description'                  => $item->description,
			'count'                        => (int) $item->count,
			'deposit'                      => WC_germanized()->deposit_types->get_deposit( $item ),
			'tax_status'                   => WC_germanized()->deposit_types->get_tax_status( $item ),
			'packaging_type'               => WC_germanized()->deposit_types->get_packaging_type( $item ),
			'packaging_type_title'         => WC_germanized()->deposit_types->get_packaging_type_title( $item ),
			'packaging'                    => $packaging ? $packaging->slug : '',
			'is_packaging'                 => WC_germanized()->deposit_types->is_packaging( $item ),
			'packaging_number_of_contents' => WC_germanized()->deposit_types->get_packaging_number_of_contents( $item ),
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
				'id'                           => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'                         => array(
					'description' => __( 'Resource name.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'                         => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'description'                  => array(
					'description' => __( 'HTML description of the resource.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'count'                        => array(
					'description' => __( 'Number of published products for the resource.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'packaging_type'               => array(
					'description' => __( 'The current deposit packaging type.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'enum'        => array_keys( WC_germanized()->deposit_types->get_packaging_types() ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'tax_status'                   => array(
					'description' => __( 'The current deposit tax_status.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'enum'        => array_keys( WC_germanized()->deposit_types->get_tax_statuses() ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'is_packaging'                 => array(
					'description' => __( 'Whether this deposit type is a packaging.', 'woocommerce-germanized' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'default'     => false,
				),
				'packaging'                    => array(
					'description' => __( 'A packaging linked to the current deposit type.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'packaging_number_of_contents' => array(
					'description' => __( 'The number of contents that fits into the packaging.', 'woocommerce-germanized' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'packaging_type_title'         => array(
					'description' => __( 'The current deposit packaging type title.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'deposit'                      => array(
					'description' => __( 'The current deposit amount.', 'woocommerce-germanized' ),
					'type'        => 'number',
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
		if ( isset( $request['packaging_type'] ) ) {
			update_term_meta( $term->term_id, 'deposit_packaging_type', sanitize_title( $request['packaging_type'] ) );
		}

		if ( isset( $request['deposit'] ) ) {
			update_term_meta( $term->term_id, 'deposit', wc_format_decimal( $request['deposit'], '' ) );
		}

		if ( isset( $request['tax_status'] ) ) {
			update_term_meta( $term->term_id, 'tax_status', sanitize_title( $request['tax_status'], '' ) );
		}

		$is_packaging = isset( $request['is_packaging'] ) ? wc_string_to_bool( $request['is_packaging'] ) : wc_string_to_bool( get_term_meta( $term->term_id, 'deposit_is_packaging', true ) );

		update_term_meta( $term->term_id, 'deposit_is_packaging', wc_bool_to_string( $is_packaging ) );

		if ( $is_packaging ) {
			$number_contents = absint( wp_unslash( isset( $request['packaging_number_of_contents'] ) ? $request['packaging_number_of_contents'] : 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			update_term_meta( $term->term_id, 'deposit_packaging_number_contents', $number_contents );
			update_term_meta( $term->term_id, 'deposit_packaging', '' );
		} else {
			update_term_meta( $term->term_id, 'deposit_packaging_number_contents', 0 );

			$packaging_list = WC_germanized()->deposit_types->get_packaging_list(
				array(
					'exclude' => $term->term_id,
				)
			);

			$packaging = wc_clean( wp_unslash( isset( $request['packaging'] ) ? $request['packaging'] : '' ) );

			if ( array_key_exists( $packaging, $packaging_list ) ) {
				update_term_meta( $term->term_id, 'deposit_packaging', $packaging );
			} else {
				update_term_meta( $term->term_id, 'deposit_packaging', '' );
			}
		}

		return true;
	}
}
