<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Label\DeutschePost;
use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\ShippingProvider\ProductList;

defined( 'ABSPATH' ) || exit;

class Internetmarke {

	/**
	 * @var ImProductList|null
	 */
	protected $products = null;

	/**
	 * @var null|array
	 */
	protected $page_formats = null;

	public function __construct() {}

	/**
	 * @return false|InternetmarkeRest
	 */
	public function get_api() {
		if ( $api = \Vendidero\Shiptastic\API\Helper::get_api( 'dhl_im_rest' ) ) {
			return $api;
		}

		return false;
	}

	public function auth() {
		if ( $this->has_auth() ) {
			return true;
		} elseif ( $this->is_configured() ) {
			$result = $this->get_api()->get_auth_api()->auth();

			if ( true === $result ) {
				return true;
			} else {
				return false;
			}
		}

		return false;
	}

	public function has_auth() {
		if ( $api = $this->get_api() ) {
			return $api->get_auth_api()->has_auth();
		}

		return false;
	}

	public function is_configured() {
		if ( $api = $this->get_api() ) {
			return $api->get_auth_api()->is_connected();
		}

		return false;
	}

	public function get_balance( $force_refresh = false ) {
		if ( $force_refresh && ( $api = $this->get_api() ) ) {
			$api->refresh_balance();
		}

		$value = get_transient( 'wc_stc_dhl_portokasse_balance' );

		if ( false !== $value ) {
			return (int) $value;
		}

		return 0;
	}

	protected function invalidate_balance() {
		delete_transient( 'wc_stc_dhl_portokasse_balance' );
	}

	public function reload_products() {
		$this->products = null;
	}

	protected function load_products() {
		if ( is_null( $this->products ) ) {
			$this->products = new ImProductList( $this );
		}

		return $this->products;
	}

	/**
	 * @return ProductList
	 */
	public function get_products() {
		$this->load_products();

		return $this->products->get_products();
	}

	/**
	 * @return ImProductList
	 */
	public function get_product_list() {
		$this->load_products();

		return $this->products;
	}

	protected function format_dimensions( $product, $type = 'length' ) {
		$dimension = '';

		if ( ! empty( $product->{"product_{$type}_min"} ) ) {
			$dimension .= $product->{"product_{$type}_min"};

			if ( ! empty( $product->{"product_{$type}_max"} ) ) {
				$dimension .= '-' . $product->{"product_{$type}_max"};
			}
		} elseif ( 0 === (int) $product->{"product_{$type}_min"} ) {
			$dimension = sprintf( _x( 'until %s', 'dhl', 'woocommerce-germanized' ), $product->{"product_{$type}_max"} );
		}

		if ( ! empty( $dimension ) ) {
			$dimension .= ' ' . $product->{"product_{$type}_unit"};
		}

		return $dimension;
	}

	public function get_product_data_by_code( $im_product_id ) {
		return $this->get_product_list()->get_product_data_by_code( $im_product_id );
	}

	public function get_product_data( $product_id ) {
		return $this->get_product_list()->get_product_data( $product_id );
	}

	public function get_product_id( $im_product_id ) {
		if ( $product = $this->get_product_list()->get_product_data_by_code( $im_product_id ) ) {
			return $product->get_internal_id();
		}

		return 0;
	}

	public function get_product_parent_code( $im_product_id ) {
		if ( $product = $this->get_product_list()->get_product_data_by_code( $im_product_id ) ) {
			if ( $product->get_parent_id() > 0 ) {
				if ( $parent_product = $this->get_product_data( $product->get_parent_id() ) ) {
					$im_product_id = $parent_product->get_id();
				}
			}
		}

		return $im_product_id;
	}

	public function product_code_is_parent( $im_product_id ) {
		$is_parent = false;

		if ( $product = $this->get_product_list()->get_product_data_by_code( $im_product_id ) ) {
			if ( $product->get_parent_id() > 0 ) {
				$is_parent = false;
			} else {
				$is_parent = true;
			}
		}

		return $is_parent;
	}

	public function get_product_total( $product_code ) {
		$total = 0;

		if ( $product = $this->get_product_data_by_code( $product_code ) ) {
			$total = $product->get_price();
		}

		return $total;
	}

	public function get_product_preview_data( $im_product_id ) {
		$product   = $this->get_product_list()->get_products()->get( $im_product_id );
		$formatted = array(
			'title_formatted'            => '',
			'price_formatted'            => '',
			'description_formatted'      => '',
			'information_text_formatted' => '',
			'dimensions_formatted'       => '',
		);

		if ( ! $product ) {
			return $formatted;
		}

		$dimensions       = array();
		$formatted_length = $product->get_formatted_dimensions( 'length' );
		$formatted_width  = $product->get_formatted_dimensions( 'width' );
		$formatted_height = $product->get_formatted_dimensions( 'height' );
		$formatted_weight = $product->get_formatted_dimensions( 'weight' );

		if ( ! empty( $formatted_length ) ) {
			$dimensions[] = sprintf( _x( 'Length: %s', 'dhl', 'woocommerce-germanized' ), $formatted_length );
		}

		if ( ! empty( $formatted_width ) ) {
			$dimensions[] = sprintf( _x( 'Width: %s', 'dhl', 'woocommerce-germanized' ), $formatted_width );
		}

		if ( ! empty( $formatted_height ) ) {
			$dimensions[] = sprintf( _x( 'Height: %s', 'dhl', 'woocommerce-germanized' ), $formatted_height );
		}

		if ( ! empty( $formatted_weight ) ) {
			$dimensions[] = sprintf( _x( 'Weight: %s', 'dhl', 'woocommerce-germanized' ), $formatted_weight );
		}

		$formatted = array_merge(
			(array) $product,
			array(
				'title_formatted'            => $product->get_label(),
				'price_formatted'            => wc_price( Package::cents_to_eur( $product->get_price() ), array( 'currency' => 'EUR' ) ) . ' <span class="price-suffix">' . _x( 'Total', 'dhl', 'woocommerce-germanized' ) . '</span>',
				'description_formatted'      => $product->get_meta( 'annotation' ) ? $product->get_meta( 'annotation' ) : $product->get_description(),
				'information_text_formatted' => $product->get_meta( 'information_text' ),
				'dimensions_formatted'       => implode( '<br/>', $dimensions ),
			)
		);

		return $formatted;
	}

	public function get_page_formats( $force_refresh = false ) {
		if ( is_null( $this->page_formats ) ) {
			$this->page_formats = get_transient( 'wc_stc_dhl_im_current_page_formats' );

			if ( ! $this->page_formats || $force_refresh ) {
				$this->page_formats = array();

				try {
					if ( $api = $this->get_api() ) {
						$page_formats = $api->get_page_formats();

						if ( ! empty( $page_formats ) ) {
							$this->page_formats = $page_formats;

							set_transient( 'wc_stc_dhl_im_current_page_formats', $this->page_formats, DAY_IN_SECONDS );
						}
					}
				} catch ( \Exception $e ) {
					Package::log( 'Error while refreshing Internetmarke page formats: ' . $e->getMessage() );
				}

				$page_formats = $this->page_formats;
			} else {
				$page_formats = $this->page_formats;
			}
		} else {
			$page_formats = $this->page_formats;
		}

		return $page_formats;
	}

	public function get_page_format_list() {
		$formats = $this->get_page_formats();
		$options = array();

		foreach ( $formats as $format ) {
			if ( apply_filters( 'woocommerce_shiptastic_deutsche_post_exclude_page_format', ! $format['isAddressPossible'], $format ) ) {
				continue;
			}

			$options[ $format['id'] ] = $format['name'];
		}

		return $options;
	}

	public function get_product_services( $im_product_id ) {
		if ( $product = $this->get_product_list()->get_product_data_by_code( $im_product_id ) ) {
			return $this->get_product_list()->get_product_services( $product->get_internal_id() );
		}

		return array();
	}

	public function get_product_code( $maybe_parent_product_code, $services = array() ) {
		if ( $product = $this->get_product_list()->get_product_data_by_code( $maybe_parent_product_code ) ) {
			$new_product_data = $this->get_product_list()->get_product_data_by_services( $product->get_internal_id(), $services );

			if ( $new_product_data ) {
				return $new_product_data->get_id();
			} else {
				return false;
			}
		}

		return $maybe_parent_product_code;
	}

	public function preview_stamp( $product_id, $address_type = 'FRANKING_ZONE', $image_id = null ) {
		$preview_url = false;

		try {
			if ( $api = $this->get_api() ) {
				$preview_url = $api->get_preview_link( $product_id, $address_type );
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return $preview_url;
	}

	/**
	 * @param float $amount in EUR
	 *
	 * @return false|float
	 */
	public function charge_wallet( $amount ) {
		try {
			if ( $api = $this->get_api() ) {
				$result = $api->charge_wallet( $amount );

				return $result;
			}
		} catch ( \Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @throws \Exception
	 *
	 * @return mixed
	 */
	public function get_label( &$label ) {
		if ( $api = $this->get_api() ) {
			$result = $api->get_label( $label );

			return $result;
		} else {
			throw new \Exception( esc_html_x( 'There was an unknown error when calling the Deutsche Post API.', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @throws \Exception
	 *
	 * @return mixed
	 */
	public function delete_label( &$label ) {
		if ( ! empty( $label->get_shop_order_id() ) ) {
			if ( $api = $this->get_api() ) {
				$transaction_id = $api->refund_label( $label );

				$this->invalidate_balance();
			}

			/**
			 * Action fires before deleting a Deutsche Post PDF label through an API call.
			 *
			 * @param DeutschePost $label The label object.
			 *
			 * @since 3.2.0
			 * @package Vendidero/Shiptastic/DHL
			 */
			do_action( 'woocommerce_shiptastic_dhl_deutsche_post_label_api_before_delete', $label );

			$label->set_number( '' );
			$label->set_wp_int_awb( '' );
			$label->set_wp_int_barcode( '' );
			$label->set_shop_order_id( '' );

			/**
			 * Action fires after deleting a Deutsche Post PDF label through an API call.
			 *
			 * @param DeutschePost $label The label object.
			 *
			 * @since 3.2.0
			 * @package Vendidero/Shiptastic/DHL
			 */
			do_action( 'woocommerce_shiptastic_dhl_deutsche_post_label_api_deleted', $label );

			return $label;
		}

		return false;
	}

	public function update_products() {
		return $this->get_product_list()->update();
	}
}
