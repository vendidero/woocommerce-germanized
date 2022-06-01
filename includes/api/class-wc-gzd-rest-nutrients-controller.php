<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_GZD_REST_Nutrients_Controller
 *
 * @since 3.9.0
 * @author vendidero
 */
class WC_GZD_REST_Nutrients_Controller extends WC_REST_Terms_Controller {

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
	protected $rest_base = 'nutrients';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = 'product_nutrient';

	/**
	 * Prepare a single delivery Time output for response.
	 *
	 * @param WP_Term $item Term object.
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		if ( ! $nutrient = $this->get_nutrient( $item ) ) {
			return parent::prepare_item_for_response( $item, $request );
		}

		$data = array(
			'id'            => (int) $item->term_id,
			'name'          => $item->name,
			'slug'          => $item->slug,
			'description'   => $item->description,
			'order'         => (int) $item->order,
			'unit'          => $nutrient->get_unit_term( 'edit' ) ? $nutrient->get_unit_term()->slug : '',
			'unit_label'    => $nutrient->get_unit(),
			'label'         => (string) $nutrient,
			'rounding_rule' => $nutrient->get_rounding_rule_slug( 'edit' ),
			'type'          => $nutrient->get_type( 'edit' ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item, $request ) );

		return apply_filters( "woocommerce_rest_prepare_{$this->taxonomy}", $response, $item, $request );
	}

	protected function get_nutrient( $term ) {
		return WC_germanized()->nutrients->get_nutrient_object( $term );
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
				'id'            => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'          => array(
					'description' => __( 'Nutrient name.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'label'         => array(
					'description' => __( 'Nutrient label.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'          => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'description'   => array(
					'description' => __( 'HTML description of the nutrient.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'order'         => array(
					'description' => __( 'The current nutrient order.', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'absint',
					),
				),
				'unit'          => array(
					'description' => __( 'The current nutrient unit term by slug or id.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'type'          => array(
					'description' => __( 'The current nutrient type.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'enum'        => array_keys( WC_GZD_Food_Helper::get_nutrient_types() ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'rounding_rule' => array(
					'description' => __( 'The current nutrient rounding rule.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( WC_GZD_Food_Helper::get_nutrient_rounding_rules() ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'unit_label'    => array(
					'description' => __( 'The current nutrient unit.', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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
		if ( ! $nutrient = $this->get_nutrient( $term ) ) {
			return true;
		}

		if ( isset( $request['order'] ) ) {
			$nutrient->set_order( absint( $request['order'] ) );
		}

		if ( isset( $request['unit'] ) ) {
			if ( is_numeric( $request['unit'] ) ) {
				$term = WC_germanized()->units->get_unit_term( absint( $request['unit'] ), 'id' );
			} else {
				$term = WC_germanized()->units->get_unit_term( sanitize_title( $request['unit'] ) );
			}

			if ( $term ) {
				$nutrient->set_unit_id( $term->term_id );
			}
		}

		if ( isset( $request['rounding_rule'] ) ) {
			$nutrient->set_rounding_rule_slug( sanitize_title( $request['rounding_rule'] ) );
		}

		if ( isset( $request['type'] ) ) {
			$nutrient->set_type( sanitize_title( $request['type'] ) );
		}

		return true;
	}
}
