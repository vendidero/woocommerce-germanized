<?php

namespace Vendidero\Germanized\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;

defined( 'ABSPATH' ) || exit;

class Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'germanized';
	}

	public function initialize() {
		$this->register_products_api();
	}

	protected function register_products_api() {
		woocommerce_store_api_register_endpoint_data( array(
			'endpoint'        => ProductSchema::IDENTIFIER,
			'namespace'       => 'germanized',
			'schema_callback' => function () {
				return array(
					'unit_price' => array(
						'description' => __( 'Test.', 'woocommece-germanized' ),
						'type'        => 'string',
					),
				);
			},
			'data_callback' => function( $product ) {
				return array(
					'unit_price' => '10.34',
				);
			}
		) );
	}

	public function get_script_handles() {
		return array();
	}

	public function get_editor_script_handles() {
		return array();
	}

	public function get_script_data() {
		return array();
	}
}