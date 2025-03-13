<?php

namespace Vendidero\Shiptastic\DHL\Api;

use baltpeter\Internetmarke\Address;
use baltpeter\Internetmarke\CompanyName;
use baltpeter\Internetmarke\Name;
use baltpeter\Internetmarke\PageFormat;
use baltpeter\Internetmarke\PersonName;
use baltpeter\Internetmarke\Service;
use baltpeter\Internetmarke\User;
use Vendidero\Shiptastic\DHL\Label\DeutschePost;
use Vendidero\Shiptastic\DHL\Label\DeutschePostReturn;
use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\ParcelLocator;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\ProductList;

defined( 'ABSPATH' ) || exit;

class Internetmarke {

	/**
	 * @var ImPartnerInformation|null
	 */
	protected $partner = null;

	/**
	 * @var Service|null
	 */
	protected $api = null;

	/**
	 * @var User|null
	 */
	protected $user = null;

	/**
	 * @var \WP_Error
	 */
	protected $errors = null;

	/**
	 * @var ImProductList|null
	 */
	protected $products = null;

	/**
	 * @var ImRefundSoap|null
	 */
	protected $refund_api = null;

	/**
	 * @var null|PageFormat[]
	 */
	protected $page_formats = null;

	public function __construct() {
		$this->partner = new ImPartnerInformation( Package::get_internetmarke_partner_id(), Package::get_internetmarke_key_phase(), Package::get_internetmarke_token() );
		$this->errors  = new \WP_Error();

		if ( ! Package::is_deutsche_post_enabled() ) {
			$this->errors->add( 'startup', _x( 'Internetmarke is disabled. Please enable Internetmarke.', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	public function get_api( $auth = false ) {
		if ( is_null( $this->api ) ) {
			try {
				if ( ! Package::supports_soap() ) {
					throw new \Exception( sprintf( _x( 'To enable communication between your shop and DHL, the PHP <a href="%1$s">SOAPClient</a> is required. Please contact your host and make sure that SOAPClient is <a href="%2$s">installed</a>.', 'dhl', 'woocommerce-germanized' ), 'https://www.php.net/manual/class.soapclient.php', esc_url( admin_url( 'admin.php?page=wc-status' ) ) ) );
				}

				$this->api = new Service( $this->partner, array(), Package::get_core_wsdl_file( Package::get_internetmarke_main_url() ) );
			} catch ( \Exception $e ) {
				$this->api = null;
				$this->errors->add( 'startup', sprintf( _x( 'Error while instantiating main Internetmarke API: %s', 'dhl', 'woocommerce-germanized' ), $e->getMessage() ) );
			}
		}

		if ( $auth ) {
			if ( is_null( $this->user ) && $this->api && $this->is_configured() ) {
				try {
					$this->errors->remove( 'authentication' );

					$this->user = $this->api->authenticateUser( Package::get_internetmarke_username(), Package::get_internetmarke_password() );
				} catch ( \Exception $e ) {
					$this->user = null;
					$this->errors->add( 'authentication', _x( 'Wrong username or password', 'dhl', 'woocommerce-germanized' ) );
				}
			}

			if ( ! $this->has_authentication_error() && $this->user ) {
				return $this->api;
			} else {
				return false;
			}
		} else {
			return $this->api ? $this->api : false;
		}
	}

	public function is_configured() {
		return Package::get_internetmarke_username() && Package::get_internetmarke_password();
	}

	public function auth() {
		return $this->get_api( true );
	}

	public function has_authentication_error() {
		$errors = $this->errors->get_error_message( 'authentication' );

		return empty( $errors ) ? false : true;
	}

	public function get_authentication_error() {
		$error = $this->errors->get_error_message( 'authentication' );

		return $error;
	}

	public function has_startup_error() {
		$errors = $this->errors->get_error_message( 'startup' );

		return empty( $errors ) ? false : true;
	}

	public function get_startup_error() {
		$error = $this->errors->get_error_message( 'startup' );

		return $error;
	}

	public function get_error_message() {
		if ( $this->has_errors() ) {
			return $this->get_startup_error() ? $this->get_startup_error() : $this->get_authentication_error();
		}

		return false;
	}

	public function has_errors() {
		return wc_stc_dhl_wp_error_has_errors( $this->errors ) ? true : false;
	}

	public function get_errors() {
		return wc_stc_dhl_wp_error_has_errors( $this->errors ) ? $this->errors : false;
	}

	public function is_available() {
		return $this->get_api( true );
	}

	public function get_user() {
		if ( ! $this->user ) {
			$this->auth();
		}

		if ( $this->user && ! is_null( $this->user ) ) {
			return $this->user;
		} else {
			return false;
		}
	}

	public function get_balance( $force_refresh = false ) {
		$balance = get_transient( 'wc_stc_dhl_portokasse_balance' );

		if ( ! $balance || $force_refresh ) {
			if ( $user = $this->get_user() ) {
				$balance = $user->getWalletBalance();

				set_transient( 'wc_stc_dhl_portokasse_balance', $user->getWalletBalance(), HOUR_IN_SECONDS );
			} else {
				$balance = 0;
			}
		}

		return $balance;
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
			$this->page_formats = get_transient( 'wc_stc_dhl_im_page_formats' );

			if ( ! $this->page_formats || $force_refresh ) {
				$this->page_formats = array();

				try {
					if ( ! $api = $this->get_api() ) {
						throw new \Exception( $this->get_error_message() );
					}

					$this->page_formats = $api->retrievePageFormats();

					set_transient( 'wc_stc_dhl_im_page_formats', $this->page_formats, DAY_IN_SECONDS );
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
			if ( apply_filters( 'woocommerce_shiptastic_deutsche_post_exclude_page_format', ! $format->isIsAddressPossible(), $format ) ) {
				continue;
			}

			$options[ $format->getId() ] = $format->getName();
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

	public function preview_stamp( $product_id, $address_type = 'FrankingZone', $image_id = null ) {
		$preview_url = false;

		try {
			if ( $api = $this->get_api( true ) ) {
				$preview_url = $api->retrievePreviewVoucherPng( $product_id, $address_type, $image_id );
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return $preview_url;
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @return mixed
	 */
	public function get_label( &$label ) {
		$result = $this->create_or_update_default_label( $label );

		$this->invalidate_balance();

		return $result;
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @return mixed
	 */
	protected function create_or_update_default_label( &$label ) {
		if ( empty( $label->get_shop_order_id() ) ) {
			return $this->create_default_label( $label );
		} else {
			if ( ! $api = $this->get_api( true ) ) {
				throw new \Exception( wp_kses_post( $this->get_authentication_error() ) );
			}

			try {
				$stamp = $api->retrieveOrder( $this->get_user()->getUserToken(), $label->get_shop_order_id() );
			} catch ( \Exception $e ) {
				return $this->create_default_label( $label );
			}

			return $this->update_default_label( $label, $stamp );
		}
	}

	public function get_refund_api() {
		if ( is_null( $this->refund_api ) ) {
			$this->refund_api = new ImRefundSoap( $this->partner, array(), Package::get_core_wsdl_file( Package::get_internetmarke_refund_url() ) );
		}

		return $this->refund_api;
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @return false|int
	 */
	public function refund_label( $label ) {
		try {
			return $this->refund_default_label( $label );
		} catch ( \Exception $e ) {
			throw new \Exception( esc_html( sprintf( _x( 'Could not refund post label: %s', 'dhl', 'woocommerce-germanized' ), $e->getMessage() ) ) );
		}
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @return false|int
	 * @throws \Exception
	 */
	protected function refund_default_label( $label ) {
		$refund = $this->get_refund_api();

		if ( ! $refund ) {
			throw new \Exception( esc_html_x( 'Refund API could not be instantiated', 'dhl', 'woocommerce-germanized' ) );
		}

		$refund_id = $refund->createRetoureId();

		if ( $refund_id ) {
			$user = $refund->authenticateUser( Package::get_internetmarke_username(), Package::get_internetmarke_password() );

			if ( $user ) {
				$transaction_id = $refund->retoureVouchers( $user->getUserToken(), $refund_id, $label->get_shop_order_id() );
			}

			Package::log( sprintf( 'Refunded DP label %s: %s', $label->get_number(), $transaction_id ) );

			return $transaction_id;
		}
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @return mixed
	 */
	public function delete_label( &$label ) {
		if ( ! empty( $label->get_shop_order_id() ) ) {
			$transaction_id = $this->refund_label( $label );

			if ( false !== $transaction_id ) {
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

	protected function get_shipment_address_prop( $shipment, $prop, $address_type = '' ) {
		$getter = "get_{$prop}";

		if ( ! empty( $address_type ) ) {
			$getter = "get_{$address_type}_{$prop}";
		}

		if ( is_callable( array( $shipment, $getter ) ) ) {
			return $shipment->$getter();
		} else {
			return '';
		}
	}

	/**
	 * @param Shipment $shipment
	 * @param $address_type
	 *
	 * @return \baltpeter\Internetmarke\NamedAddress
	 */
	protected function get_shipment_address_data( $shipment, $address_type = '' ) {
		$person_name = new PersonName( '', '', $this->get_shipment_address_prop( $shipment, 'first_name', $address_type ), $this->get_shipment_address_prop( $shipment, 'last_name', $address_type ) );

		if ( $this->get_shipment_address_prop( $shipment, 'company', $address_type ) ) {
			$name = new Name( null, new CompanyName( $this->get_shipment_address_prop( $shipment, 'company', $address_type ), $person_name ) );
		} else {
			$name = new Name( $person_name, null );
		}

		$additional = $this->get_shipment_address_prop( $shipment, 'address_2', $address_type );

		if ( 'simple' === $shipment->get_type() && $shipment->send_to_external_pickup( 'locker' ) ) {
			$additional = ( empty( $additional ) ? '' : $additional . ' ' ) . $shipment->get_pickup_location_customer_number();
		}

		$address = new Address(
			$additional,
			$this->get_shipment_address_prop( $shipment, 'address_street', $address_type ),
			$this->get_shipment_address_prop( $shipment, 'address_street_number', $address_type ),
			$this->get_shipment_address_prop( $shipment, 'postcode', $address_type ),
			$this->get_shipment_address_prop( $shipment, 'city', $address_type ),
			wc_stc_country_to_alpha3( $this->get_shipment_address_prop( $shipment, 'country', $address_type ) )
		);

		$named_address = new \baltpeter\Internetmarke\NamedAddress( $name, $address );

		return $named_address;
	}

	/**
	 * @param DeutschePost|DeutschePostReturn $label
	 */
	protected function create_default_label( &$label ) {
		$shipment = $label->get_shipment();

		if ( ! $shipment ) {
			throw new \Exception( esc_html( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) ) );
		}

		$sender          = $this->get_shipment_address_data( $shipment, 'sender' );
		$receiver        = $this->get_shipment_address_data( $shipment );
		$address_binding = new \baltpeter\Internetmarke\AddressBinding( $sender, $receiver );

		if ( ! $api = $this->get_api( true ) ) {
			throw new \Exception( wp_kses_post( $this->get_error_message() ) );
		}

		try {
			$shop_order_id = $api->createShopOrderId( $this->get_user()->getUserToken() );

			if ( ! $shop_order_id ) {
				throw new \Exception( _x( 'Error while generating shop order id.', 'dhl', 'woocommerce-germanized' ) );
			}

			$label->set_shop_order_id( $shop_order_id );

			$position = new \baltpeter\Internetmarke\Position(
				/**
				 * Adjust the Deutsche Post (Internetmarke) label print X position.
				 *
				 * @param mixed $x The x axis position.
				 * @param DeutschePost $label The label instance.
				 * @param Shipment $shipment The shipment instance.
				 *
				 * @since 3.4.5
				 * @package Vendidero/Shiptastic/DHL
				 */
				apply_filters( 'woocommerce_shiptastic_deutsche_post_label_api_position_x', $label->get_position_x(), $label, $shipment ),
				/**
				 * Adjust the Deutsche Post (Internetmarke) label print Y position.
				 *
				 * @param mixed $y The y axis position.
				 * @param DeutschePost $label The label instance.
				 * @param Shipment $shipment The shipment instance.
				 *
				 * @since 3.4.5
				 * @package Vendidero/Shiptastic/DHL
				 */
				apply_filters( 'woocommerce_shiptastic_deutsche_post_label_api_position_y', $label->get_position_y(), $label, $shipment ),
				apply_filters( 'woocommerce_shiptastic_deutsche_post_label_api_page_number', 1, $label, $shipment )
			);

			$order_item = new \baltpeter\Internetmarke\OrderItem( $label->get_product_id(), null, $address_binding, $position, 'AddressZone' );
			$stamp      = $api->checkoutShoppingCartPdf( $this->get_user()->getUserToken(), $label->get_print_format(), array( $order_item ), $label->get_stamp_total(), $shop_order_id, null, true, 2 );

			return $this->update_default_label( $label, $stamp );
		} catch ( \Exception $e ) {
			Package::log( 'Error while purchasing the stamp: ' . $e->getMessage() );

			throw new \Exception( wp_kses_post( sprintf( _x( 'Error while trying to purchase the stamp. Please manually <a href="%s">refresh</a> your product database and try again.', 'dhl', 'woocommerce-germanized' ), esc_url( Package::get_deutsche_post_shipping_provider()->get_edit_link( 'label' ) ) ) ) );
		}
	}

	/**
	 * @param DeutschePost $label
	 * @param \stdClass $stamp
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	protected function update_default_label( &$label, $stamp ) {
		if ( isset( $stamp->link ) ) {

			$label->set_original_url( $stamp->link );
			$voucher_list = $stamp->shoppingCart->voucherList; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( ! empty( $voucher_list->voucher ) ) {
				foreach ( $voucher_list->voucher as $i => $voucher ) {

					if ( isset( $voucher->trackId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$label->set_number( $voucher->trackId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					} else {
						$label->set_number( $voucher->voucherId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}

					$label->set_voucher_id( $voucher->voucherId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}
			}

			if ( isset( $stamp->manifestLink ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$label->set_manifest_url( $stamp->manifestLink ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			$label->save();
			$result = $label->download_label_file( $stamp->link );

			if ( ! $result ) {
				throw new \Exception( esc_html_x( 'Error while downloading the PDF stamp.', 'dhl', 'woocommerce-germanized' ) );
			}

			$label->save();

			return $label;
		} else {
			throw new \Exception( esc_html_x( 'Invalid stamp response.', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	public function update_products() {
		return $this->get_product_list()->update();
	}
}
