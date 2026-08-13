<?php
namespace Vendidero\Germanized\StoreApi;

use WP_Error;
use WP_REST_Response;

class ProductsGaranLabel {

	public static function get_namespace() {
		return 'wc/store/v1';
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public static function get_path() {
		return '/products/(?P<id>[\d]+)/garan-label.svg';
	}

	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'variant' => array(
						'description' => __( 'The EU GARAN label type.', 'woocommerce-germanized' ),
						'type'        => 'enum',
						'options'     => array( 'foldable', 'full' ),
						'default'     => 'full',
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
		);
	}

	/**
	 * Get the route response based on the type of request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		try {
			$product = wc_gzd_get_gzd_product( (int) $request['id'] );
			$variant = $request['variant'];

			if ( ! $product || 0 === $product->get_id() || 'publish' !== $product->get_status() ) {
				throw new \Exception( 'woocommerce_gzd_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce-germanized' ), 404 );
			}

			// A variation's visibility follows its parent product.
			if ( $product->get_parent_id() ) {
				$parent = wc_get_product( $product->get_parent_id() );

				if ( ! $parent || 'publish' !== $parent->get_status() ) {
					throw new \Exception( 'woocommerce_gzd_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce-germanized' ), 404 );
				}
			}

			$svg = $product->get_garan_label_svg( $variant );

			if ( ! $svg ) {
				throw new \Exception( 'woocommerce_gzd_rest_product_invalid_label', __( 'Invalid GARAN label.', 'woocommerce-germanized' ), 404 );
			}

			rest_get_server()->send_headers(
				array(
					'Content-Type' => 'image/svg+xml',
				// 'Cache-Control' => 'max-age=3600',
				)
			);

			echo $svg;
			exit();
		} catch ( \Exception $e ) {
			$error = new WP_Error( $e->getCode(), $e->getMessage() );

			return rest_convert_error_to_response( $error );
		}
	}
}
