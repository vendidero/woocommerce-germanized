<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\ShippingProvider\Product;
use Vendidero\Shiptastic\ShippingProvider\ProductList;

defined( 'ABSPATH' ) || exit;

/**
 * DHL Shipment class.
 */
class ImProductList {

	/**
	 * @var Internetmarke
	 */
	protected $im = null;

	public function __construct( $im ) {
		$this->im = $im;
	}

	/**
	 * @return ProductList|false
	 */
	public function get_products() {
		if ( $provider = Package::get_deutsche_post_shipping_provider() ) {
			return $provider->get_products();
		}

		return new ProductList();
	}

	public function get_product_services( $product_id ) {
		global $wpdb;

		$services = array();
		$results  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->stc_dhl_im_product_services} WHERE product_service_product_id = %d", $product_id ) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$services[] = $result->product_service_slug;
			}
		}

		return $services;
	}

	/**
	 * Returns available service slugs for a certain (parent) product.
	 *
	 * In case additional services chosen are supplied, only those services (e.g. Zusatzentgelt MBf which is only available if EINSCHREIBEN has been selected)
	 * are added which are compatible with the current selection.
	 *
	 * @param $parent_id
	 * @param array $services
	 *
	 * @return string[]
	 */
	public function get_services_for_product( $parent_id, $services = array() ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->stc_dhl_im_products}";
		$count = 1;

		if ( empty( $services ) ) {
			$query .= " INNER JOIN {$wpdb->stc_dhl_im_product_services} S{$count} ON {$wpdb->stc_dhl_im_products}.product_id = S{$count}.product_service_product_id";
		} else {
			foreach ( $services as $service ) {
				++$count;

				$query .= $wpdb->prepare( " INNER JOIN {$wpdb->stc_dhl_im_product_services} S{$count} ON {$wpdb->stc_dhl_im_products}.product_id = S{$count}.product_service_product_id AND S{$count}.product_service_slug = %s", $service ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		$query .= $wpdb->prepare( " WHERE {$wpdb->stc_dhl_im_products}.product_parent_id = %d", $parent_id );

		if ( empty( $services ) ) {
			$query .= $wpdb->prepare( " AND {$wpdb->stc_dhl_im_products}.product_service_count = %d", 1 );
		}

		$results            = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$available_services = array();

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$product_id       = $result->product_id;
				$product_services = $wpdb->get_results( $wpdb->prepare( "SELECT product_service_slug FROM {$wpdb->stc_dhl_im_product_services} WHERE {$wpdb->stc_dhl_im_product_services}.product_service_product_id = %d", $product_id ) );

				if ( ! empty( $product_services ) ) {
					foreach ( $product_services as $product_service ) {
						$service_slug = $product_service->product_service_slug;

						if ( ! in_array( $service_slug, $available_services, true ) ) {
							$available_services[] = $service_slug;
						}
					}
				}
			}
		}

		return $available_services;
	}

	/**
	 * Returns the product id based on a list of services.
	 * In case no services are supplied, the main product is returned.
	 *
	 * @param $parent_id
	 * @param $services
	 *
	 * @return Product|false
	 */
	public function get_product_data_by_services( $parent_id, $services = array() ) {
		global $wpdb;

		$product = $this->get_product_data( $parent_id );

		/**
		 * In case we are passing a product without a parent that may mean that
		 * this product already includes services - return the actual product instead of searching.
		 */
		if ( $product && $product->get_parent_id() > 0 ) {
			if ( empty( $services ) ) {
				return $product;
			} else {
				$parent_id = $product->get_parent_id();
			}
		}

		$query = "SELECT * FROM {$wpdb->stc_dhl_im_products}";
		$count = 0;

		if ( ! empty( $services ) ) {
			foreach ( $services as $service ) {
				++$count;
				$query .= $wpdb->prepare( " INNER JOIN {$wpdb->stc_dhl_im_product_services} S{$count} ON {$wpdb->stc_dhl_im_products}.product_id = S{$count}.product_service_product_id AND S{$count}.product_service_slug = %s", $service ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

			$all_services = array_map(
				function ( $p ) {
					return "'" . esc_sql( $p ) . "'";
				},
				$services
			);

			$all_services = implode( ',', $all_services );

			// Add a left join which must be NULL making sure that no other services are linked to that product.
			$query .= " LEFT JOIN {$wpdb->stc_dhl_im_product_services} S ON {$wpdb->stc_dhl_im_products}.product_id = S.product_service_product_id AND S.product_service_slug NOT IN ($all_services)";
			$query .= $wpdb->prepare( " WHERE {$wpdb->stc_dhl_im_products}.product_parent_id = %d AND S.product_service_id IS NULL LIMIT 1", $parent_id );
		} else {
			$query .= $wpdb->prepare( " WHERE {$wpdb->stc_dhl_im_products}.product_id = %d AND {$wpdb->stc_dhl_im_products}.product_parent_id = %d LIMIT 1", $parent_id, 0 );
		}

		$result = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $result ) ) {
			return $this->get_products()->get( $result->product_code );
		} else {
			return false;
		}
	}

	/**
	 * @param $product_code
	 *
	 * @return false|\Vendidero\Shiptastic\ShippingProvider\Product
	 */
	public function get_product_data_by_code( $product_code ) {
		if ( is_null( $product_code ) ) {
			return false;
		}

		if ( $product = $this->get_products()->get( $product_code ) ) {
			return $product;
		}

		return false;
	}

	/**
	 * @param $product_id
	 *
	 * @return false|Product
	 */
	public function get_product_data( $product_id ) {
		if ( is_null( $product_id ) ) {
			return false;
		}

		if ( $product = $this->get_products()->get_by_internal_id( $product_id ) ) {
			return $product;
		}

		return false;
	}

	private function get_information_text( $stamp_type ) {
		$information_text = '';

		foreach ( $stamp_type as $stamp ) {
			if ( 'Internetmarke' === $stamp->name ) {
				foreach ( $stamp->propertyList as $properties ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					foreach ( $properties as $property ) {
						if ( 'InformationText' === $property->name ) {
							$information_text = $property->propertyValue->alphanumericValue->fixValue; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						}
					}
				}
			}
		}

		return $information_text;
	}

	protected function get_dimensions( $dimensions, $type = 'width' ) {
		$data = array(
			"product_{$type}_min"  => null,
			"product_{$type}_max"  => null,
			"product_{$type}_unit" => null,
		);

		if ( property_exists( $dimensions, $type ) ) {
			$d = $dimensions->{ $type };

			$data[ "product_{$type}_min" ]  = property_exists( $d, 'minValue' ) ? $d->minValue : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data[ "product_{$type}_max" ]  = property_exists( $d, 'maxValue' ) ? $d->maxValue : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data[ "product_{$type}_unit" ] = property_exists( $d, 'unit' ) ? $d->unit : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		return $data;
	}

	public function get_additional_services() {
		return array(
			'PRIO' => _x( 'PRIO', 'dhl', 'woocommerce-germanized' ),
			'ESEW' => _x( 'Einschreiben (Einwurf)', 'dhl', 'woocommerce-germanized' ),
			'ESCH' => _x( 'Einschreiben', 'dhl', 'woocommerce-germanized' ),
			'ESEH' => _x( 'Einschreiben (Eigenhändig)', 'dhl', 'woocommerce-germanized' ),
			'AS16' => _x( 'Alterssichtprüfung 16', 'dhl', 'woocommerce-germanized' ),
			'AS18' => _x( 'Alterssichtprüfung 18', 'dhl', 'woocommerce-germanized' ),
			'ZMBF' => _x( 'Zusatzentgelt MBf', 'dhl', 'woocommerce-germanized' ),
			'USFT' => _x( 'Unterschrift', 'dhl', 'woocommerce-germanized' ),
			'TRCK' => _x( 'Tracked', 'dhl', 'woocommerce-germanized' ),
			'RCKS' => _x( 'Rückschein', 'dhl', 'woocommerce-germanized' ),
		);
	}

	public function get_additional_service_title( $service ) {
		$services = $this->get_additional_services();

		return array_key_exists( $service, $services ) ? $services[ $service ] : '';
	}

	protected function get_additional_service_identifiers() {
		return array(
			'-einschreiben-einwurf'      => 'ESEW',
			'-einschreiben-eigenhaendig' => 'ESEH',
			'-einschreiben'              => 'ESCH',
			'-zusatzentgelt-mbf'         => 'ZMBF',
			'-prio'                      => 'PRIO',
			'-unterschrift'              => 'USFT',
			'-tracked'                   => 'TRCK',
			'-rueckschein'               => 'RCKS',
		);
	}

	protected function is_additional_service( $slug ) {
		$service_slug = $this->get_product_service_slugs( $slug );

		return ! empty( $service_slug ) ? true : false;
	}

	protected function get_product_base_slug( $slug ) {
		$additional_services = $this->get_additional_service_identifiers();
		$slug                = str_replace( 'integral', '', $slug );

		foreach ( array_keys( $additional_services ) as $identifier ) {
			$slug = str_replace( $identifier, ' ', $slug );
		}

		return $this->sanitize_product_slug( $slug );
	}

	protected function get_product_service_slugs( $slug ) {
		$service_slugs    = array();
		$has_einschreiben = false;

		foreach ( $this->get_additional_service_identifiers() as $identifier => $service ) {
			if ( strpos( $slug, $identifier ) !== false ) {
				if ( strpos( $identifier, 'einschreiben' ) !== false ) {
					if ( ! $has_einschreiben ) {
						$has_einschreiben = true;
						$service_slugs[]  = $service;
					}
				} elseif ( strpos( $slug, $identifier ) !== false ) {
					$service_slugs[] = $service;
				}
			}
		}

		return array_unique( $service_slugs );
	}

	protected function sanitize_product_slug( $product_name ) {
		$product_name = trim( mb_strtolower( $product_name ) );

		// Remove duplicate whitespaces
		$product_name = preg_replace( '/\s+/', ' ', $product_name );

		return sanitize_title( $product_name );
	}

	public function update() {
		global $wpdb;

		$result = new \WP_Error();

		wp_cache_delete( 'im_products', 'shiptastic-dhl' );

		try {
			if ( ! class_exists( 'SoapClient' ) ) {
				throw new \Exception( 'SoapClient is missing.' );
			}

			$product_soap = new ImProductsSoap( array(), Package::get_core_wsdl_file( Package::get_internetmarke_products_url() ) );
			$product_list = $product_soap->get_products();

			if ( ! isset( $product_list->Response ) && isset( $product_list->Exception ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				throw new \Exception( esc_html( $product_list->Exception->exceptionDetail[0]->errorDetail ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			$response = $product_list->Response; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( ! isset( $response->salesProductList ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				Package::log( 'Error while retrieving Internetmarke products:' );
				Package::log( wc_print_r( $product_list, true ) );

				if ( isset( $response->message ) ) {
					throw new \Exception( wc_clean( $response->message ) );
				} else {
					throw new \Exception( _x( 'No Internetmarke product data found.', 'dhl', 'woocommerce-germanized' ) );
				}
			}

			$wpdb->query( "TRUNCATE TABLE {$wpdb->stc_dhl_im_products}" );
			$wpdb->query( "TRUNCATE TABLE {$wpdb->stc_dhl_im_product_services}" );

			$products = array(
				'sales'      => $response->salesProductList->SalesProduct, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'additional' => $response->additionalProductList->AdditionalProduct, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'basic'      => $response->basicProductList->BasicProduct, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			);

			$products_with_additional_service = array();

			foreach ( $products as $product_type => $inner_products ) {
				foreach ( $inner_products as $product ) {
					$extended_identifier = $product->extendedIdentifier; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$extern_identifier   = property_exists( $extended_identifier, 'externIdentifier' ) ? $extended_identifier->externIdentifier[0] : new \stdClass(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					if ( ! property_exists( $extern_identifier, 'id' ) || empty( $extern_identifier->id ) ) {
						continue;
					}

					$to_insert = array(
						'product_im_id'            => $extended_identifier->{'ProdWS-ID'},
						'product_code'             => property_exists( $extern_identifier, 'id' ) ? $extern_identifier->id : $extended_identifier->{'ProdWS-ID'},
						'product_name'             => property_exists( $extern_identifier, 'name' ) ? $extern_identifier->name : $extended_identifier->name,
						'product_type'             => $product_type,
						'product_annotation'       => property_exists( $extended_identifier, 'annotation' ) ? $extended_identifier->annotation : '',
						'product_description'      => property_exists( $extended_identifier, 'description' ) ? $extended_identifier->description : '',
						'product_destination'      => $extended_identifier->destination,
						'product_price'            => property_exists( $product->priceDefinition, 'price' ) ? Package::eur_to_cents( $product->priceDefinition->price->calculatedGrossPrice->value ) : Package::eur_to_cents( $product->priceDefinition->grossPrice->value ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						'product_information_text' => property_exists( $product, 'stampTypeList' ) ? $this->get_information_text( (array) $product->stampTypeList->stampType ) : '', // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					);

					$product_slug = $this->sanitize_product_slug( $to_insert['product_name'] );

					/**
					 * Exclude national Warenpost as this service won't be available
					 * in the future (only via DHL).
					 */
					if ( strpos( $product_slug, 'warenpost' ) !== false && 'international' !== $to_insert['product_destination'] ) {
						continue;
					}

					/**
					 * Warenpost International
					 */
					if ( strpos( $product_slug, 'warenpost' ) !== false ) {
						$to_insert['product_is_wp_int'] = 1;
					}

					/**
					 * EU Warenpost
					 */
					if ( strpos( $product_slug, 'warenpost' ) !== false && strpos( $product_slug, '(eu' ) !== false ) {
						$to_insert['product_destination'] = 'eu';
					}

					if ( property_exists( $product, 'dimensionList' ) ) {
						$dimensions = $product->dimensionList; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

						$to_insert = array_merge( $to_insert, $this->get_dimensions( $dimensions, 'width' ) );
						$to_insert = array_merge( $to_insert, $this->get_dimensions( $dimensions, 'height' ) );
						$to_insert = array_merge( $to_insert, $this->get_dimensions( $dimensions, 'length' ) );
					}

					if ( property_exists( $product, 'weight' ) ) {
						$to_insert = array_merge( $to_insert, $this->get_dimensions( $product, 'weight' ) );
					}

					$to_insert['product_slug'] = $this->sanitize_product_slug( $to_insert['product_name'] );

					/**
					 * Sanitize data.
					 */
					$to_insert = array_map( 'wc_clean', $to_insert );

					/**
					 * Skip product creation if this is an additional service
					 */
					if ( $this->is_additional_service( $to_insert['product_slug'] ) ) {
						$products_with_additional_service[] = $to_insert;

						continue;
					}

					$wpdb->insert( $wpdb->stc_dhl_im_products, $to_insert );
				}
			}

			foreach ( $products_with_additional_service as $product_to_insert ) {
				$product_base_slug = $this->get_product_base_slug( $product_to_insert['product_slug'] );
				$parent_product    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->stc_dhl_im_products} WHERE product_slug = %s", $product_base_slug ) );
				$service_slugs     = $this->get_product_service_slugs( $product_to_insert['product_slug'] );

				if ( ! empty( $parent_product ) && ! empty( $service_slugs ) ) {
					$product_to_insert['product_parent_id']     = $parent_product->product_id;
					$product_to_insert['product_service_count'] = count( $service_slugs );
				}

				$wpdb->insert( $wpdb->stc_dhl_im_products, $product_to_insert );

				$product_id = $wpdb->insert_id;

				if ( $product_id && ! empty( $parent_product ) && ! empty( $service_slugs ) ) {
					foreach ( $service_slugs as $service_slug ) {
						$service_insert = array(
							'product_service_product_id' => $product_id,
							'product_service_product_parent_id' => $parent_product->product_id,
							'product_service_slug'       => $service_slug,
						);

						$wpdb->insert( $wpdb->stc_dhl_im_product_services, $service_insert );
					}
				}
			}
		} catch ( \Exception $e ) {
			$result->add( 'soap', $e->getMessage() );
		}

		return wc_stc_shipment_wp_error_has_errors( $result ) ? $result : true;
	}
}
