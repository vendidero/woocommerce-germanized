<?php

namespace Vendidero\Germanized\DHL\Api;

use baltpeter\Internetmarke\Address;
use baltpeter\Internetmarke\CompanyName;
use baltpeter\Internetmarke\Name;
use baltpeter\Internetmarke\PageFormat;
use baltpeter\Internetmarke\PersonName;
use baltpeter\Internetmarke\Service;
use baltpeter\Internetmarke\User;
use Vendidero\Germanized\DHL\Label\DeutschePost;
use Vendidero\Germanized\DHL\Label\DeutschePostReturn;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelLocator;
use Vendidero\Germanized\Shipments\Shipment;

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

	protected $wp_int_api = null;

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
				if ( ! Package::has_load_dependencies() ) {
					throw new \Exception( sprintf( _x( 'To enable communication between your shop and DHL, the PHP <a href="%1$s">SOAPClient</a> is required. Please contact your host and make sure that SOAPClient is <a href="%2$s">installed</a>.', 'dhl', 'woocommerce-germanized' ), 'https://www.php.net/manual/class.soapclient.php', esc_url( admin_url( 'admin.php?page=wc-status' ) ) ) );
				}

				$this->api = new Service( $this->partner, array(), Package::get_wsdl_file( Package::get_internetmarke_main_url() ) );
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
		return wc_gzd_dhl_wp_error_has_errors( $this->errors ) ? true : false;
	}

	public function get_errors() {
		return wc_gzd_dhl_wp_error_has_errors( $this->errors ) ? $this->errors : false;
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
		$balance = get_transient( 'wc_gzd_dhl_portokasse_balance' );

		if ( ! $balance || $force_refresh ) {
			if ( $user = $this->get_user() ) {
				$balance = $user->getWalletBalance();

				set_transient( 'wc_gzd_dhl_portokasse_balance', $user->getWalletBalance(), HOUR_IN_SECONDS );
			} else {
				$balance = 0;
			}
		}

		return $balance;
	}

	protected function invalidate_balance() {
		delete_transient( 'wc_gzd_dhl_portokasse_balance' );
	}

	public function reload_products() {
		$this->products = null;
	}

	protected function load_products() {
		if ( is_null( $this->products ) ) {
			$this->products = new ImProductList( $this );
		}

		$transient = get_transient( 'wc_gzd_dhl_im_products_expire' );

		if ( ! $transient ) {
			$result = $this->products->update();

			if ( is_wp_error( $result ) ) {
				Package::log( 'Error while refreshing Internetmarke product data: ' . $result->get_error_message() );
			}

			/**
			 * Refresh product data once per day.
			 */
			set_transient( 'wc_gzd_dhl_im_products_expire', 'yes', DAY_IN_SECONDS );
		}
	}

	public function get_products( $filters = array() ) {
		$this->load_products();

		return $this->products->get_products( $filters );
	}

	public function get_base_products() {
		$this->load_products();

		return $this->products->get_base_products();
	}

	public function get_default_available_products() {
		return array(
			'11',
			'21',
			'31',
			'282',
			'290',
			'10246',
			'10247',
			'10248',
			'10249',
			'10254',
			'10255',
			'10256',
			'10257',
		);
	}

	public function get_available_products( $filters = array() ) {
		$this->load_products();

		return $this->products->get_available_products( $filters );
	}

	public function get_product_list() {
		$this->load_products();

		return $this->products;
	}

	public function is_warenpost_international( $im_product_code ) {
		$is_wp_int = false;

		if ( $product_data = $this->get_product_data_by_code( $im_product_code ) ) {
			if ( 1 === (int) $product_data->product_is_wp_int ) {
				return true;
			}
		}

		return $is_wp_int;
	}

	public function is_warenpost_international_eu( $im_product_code ) {
		$is_wp_int = false;

		if ( $product_data = $this->get_product_data_by_code( $im_product_code ) ) {
			if ( 1 === (int) $product_data->product_is_wp_int && 'eu' === $product_data->product_destination ) {
				return true;
			}
		}

		return $is_wp_int;
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
		$this->load_products();

		return $this->products->get_product_data_by_code( $im_product_id );
	}

	public function get_product_data( $product_id ) {
		$this->load_products();

		return $this->products->get_product_data( $product_id );
	}

	public function get_product_id( $im_product_id ) {
		$this->load_products();
		$data = $this->products->get_product_data_by_code( $im_product_id );

		return ( ! empty( $data ) ? $data->product_id : 0 );
	}

	public function get_product_parent_code( $im_product_id ) {
		$this->load_products();
		$data = $this->products->get_product_data_by_code( $im_product_id );

		if ( ! empty( $data ) ) {
			if ( $data->product_parent_id > 0 ) {
				$im_product_data = $this->get_product_data( $data->product_parent_id );

				$im_product_id = $im_product_data->product_code;
			}
		}

		return $im_product_id;
	}

	public function product_code_is_parent( $im_product_id ) {
		$this->load_products();
		$data      = $this->products->get_product_data_by_code( $im_product_id );
		$is_parent = false;

		if ( ! empty( $data ) ) {
			if ( $data->product_parent_id > 0 ) {
				$is_parent = false;
			} else {
				$is_parent = true;
			}
		}

		return $is_parent;
	}

	public function get_product_total( $product_code ) {
		$total = 0;

		if ( $data = $this->get_product_data_by_code( $product_code ) ) {
			$total = $data->product_price;
		}

		return $total;
	}

	public function get_available_products_printable() {
		$printable = array();

		foreach ( $this->get_available_products() as $product ) {
			$printable[ $product->product_code ] = $this->get_product_preview_data( $product );
		}

		return $printable;
	}

	public function get_product_preview_data( $im_product_id ) {
		$product   = is_numeric( $im_product_id ) ? $this->get_product_data_by_code( $im_product_id ) : $im_product_id;
		$formatted = array(
			'title_formatted'            => '',
			'price_formatted'            => '',
			'description_formatted'      => '',
			'information_text_formatted' => '',
			'dimensions_formatted'       => '',
		);

		if ( ! $product || ! isset( $product->product_id ) ) {
			return $formatted;
		}

		$dimensions       = array();
		$formatted_length = $this->format_dimensions( $product, 'length' );
		$formatted_width  = $this->format_dimensions( $product, 'width' );
		$formatted_height = $this->format_dimensions( $product, 'height' );
		$formatted_weight = $this->format_dimensions( $product, 'weight' );

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
				'title_formatted'            => wc_gzd_dhl_get_im_product_title( $product->product_name ),
				'price_formatted'            => wc_price( Package::cents_to_eur( $product->product_price ), array( 'currency' => 'EUR' ) ) . ' <span class="price-suffix">' . _x( 'Total', 'dhl', 'woocommerce-germanized' ) . '</span>',
				'description_formatted'      => ! empty( $product->product_annotation ) ? $product->product_annotation : $product->product_description,
				'information_text_formatted' => $product->product_information_text,
				'dimensions_formatted'       => implode( '<br/>', $dimensions ),
			)
		);

		return $formatted;
	}

	public function get_page_formats( $force_refresh = false ) {
		if ( is_null( $this->page_formats ) ) {
			$this->page_formats = get_transient( 'wc_gzd_dhl_im_page_formats' );

			if ( ! $this->page_formats || $force_refresh ) {
				$this->page_formats = array();

				try {
					if ( ! $api = $this->get_api() ) {
						throw new \Exception( $this->get_error_message() );
					}

					$this->page_formats = $api->retrievePageFormats();

					set_transient( 'wc_gzd_dhl_im_page_formats', $this->page_formats, DAY_IN_SECONDS );
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
			if ( apply_filters( 'woocommerce_gzd_deutsche_post_exclude_page_format', ! $format->isIsAddressPossible(), $format ) ) {
				continue;
			}

			$options[ $format->getId() ] = $format->getName();
		}

		return $options;
	}

	public function get_product_services( $im_product_id ) {
		$this->load_products();

		$product_id = $this->get_product_id( $im_product_id );

		if ( $product_id ) {
			return $this->products->get_product_services( $product_id );
		}

		return array();
	}

	public function get_product_code( $maybe_parent_product_code, $services = array() ) {
		$this->load_products();

		$product_id = $this->get_product_id( $maybe_parent_product_code );

		if ( $product_id ) {
			$new_product_data = $this->products->get_product_data_by_services( $product_id, $services );

			if ( $new_product_data ) {
				return $new_product_data->product_code;
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
				if ( $this->is_warenpost_international( $product_id ) ) {
					if ( $this->is_warenpost_international_eu( $product_id ) ) {
						$preview_url = trailingslashit( Package::get_assets_url() ) . 'img/wp-int-eu-preview.png';
					} else {
						$preview_url = trailingslashit( Package::get_assets_url() ) . 'img/wp-int-preview.png';
					}
				} else {
					$preview_url = $api->retrievePreviewVoucherPng( $product_id, $address_type, $image_id );
				}
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
		if ( $label->is_warenpost_international() ) {
			$result = $this->create_or_update_wp_int_label( $label );
		} else {
			$result = $this->create_or_update_default_label( $label );
		}

		$this->invalidate_balance();

		return $result;
	}

	protected function get_wp_int_api() {
		if ( is_null( $this->wp_int_api ) ) {
			$this->wp_int_api = new ImWarenpostIntRest();
		}

		return $this->wp_int_api;
	}

	/**
	 * @param DeutschePost $label
	 *
	 * @return mixed
	 */
	protected function create_or_update_wp_int_label( &$label ) {
		$today                      = new \DateTime();
		$discontinued_starting_from = new \DateTime( '2022-07-01' );

		if ( $today >= $discontinued_starting_from ) {
			throw new \Exception( sprintf( _x( 'The Deutsche Post WP International API was discontinued. Please use the <a href="%s">DHL API</a> for Warenpost labels instead.', 'dhl', 'woocommerce-germanized' ), esc_url( 'https://vendidero.de/dokument/dhl-integration-einrichten' ) ) );
		} else {
			if ( empty( $label->get_wp_int_awb() ) ) {
				return $this->get_wp_int_api()->create_label( $label );
			} else {
				try {
					$pdf = $this->get_wp_int_api()->get_pdf( $label->get_wp_int_awb() );

					if ( ! $pdf ) {
						throw new \Exception( _x( 'Error while fetching label PDF', 'dhl', 'woocommerce-germanized' ) );
					}
				} catch ( \Exception $e ) {
					return $this->get_wp_int_api()->create_label( $label );
				}

				return true;
			}
		}
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
				throw new \Exception( $this->get_authentication_error() );
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
			$this->refund_api = new ImRefundSoap( $this->partner, array(), Package::get_wsdl_file( Package::get_internetmarke_refund_url() ) );
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
			if ( $label->is_warenpost_international() ) {
				return $this->refund_wp_int_label( $label );
			} else {
				return $this->refund_default_label( $label );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( sprintf( _x( 'Could not refund post label: %s', 'dhl', 'woocommerce-germanized' ), $e->getMessage() ) );
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
			throw new \Exception( _x( 'Refund API could not be instantiated', 'dhl', 'woocommerce-germanized' ) );
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
	 * @return false|int
	 * @throws \Exception
	 */
	protected function refund_wp_int_label( $label ) {
		return false;
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
			 * @package Vendidero/Germanized/DHL
			 */
			do_action( 'woocommerce_gzd_dhl_deutsche_post_label_api_before_delete', $label );

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
			 * @package Vendidero/Germanized/DHL
			 */
			do_action( 'woocommerce_gzd_dhl_deutsche_post_label_api_deleted', $label );

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

	protected function get_shipment_address_data( $shipment, $address_type = '' ) {
		$person_name = new PersonName( '', '', $this->get_shipment_address_prop( $shipment, 'first_name', $address_type ), $this->get_shipment_address_prop( $shipment, 'last_name', $address_type ) );

		if ( $this->get_shipment_address_prop( $shipment, 'company', $address_type ) ) {
			$name = new Name( null, new CompanyName( $this->get_shipment_address_prop( $shipment, 'company', $address_type ), $person_name ) );
		} else {
			$name = new Name( $person_name, null );
		}

		$additional = $this->get_shipment_address_prop( $shipment, 'address_2', $address_type );

		if ( 'simple' === $shipment->get_type() && $shipment->send_to_external_pickup( 'packstation' ) ) {
			$additional = ( empty( $additional ) ? '' : $additional . ' ' ) . ParcelLocator::get_postnumber_by_shipment( $shipment );
		}

		$address = new Address(
			$additional,
			$this->get_shipment_address_prop( $shipment, 'address_street', $address_type ),
			$this->get_shipment_address_prop( $shipment, 'address_street_number', $address_type ),
			$this->get_shipment_address_prop( $shipment, 'postcode', $address_type ),
			$this->get_shipment_address_prop( $shipment, 'city', $address_type ),
			Package::get_country_iso_alpha3( $this->get_shipment_address_prop( $shipment, 'country', $address_type ) )
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
			throw new \Exception( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) );
		}

		$sender          = $this->get_shipment_address_data( $shipment, 'sender' );
		$receiver        = $this->get_shipment_address_data( $shipment );
		$address_binding = new \baltpeter\Internetmarke\AddressBinding( $sender, $receiver );

		if ( ! $api = $this->get_api( true ) ) {
			throw new \Exception( $this->get_error_message() );
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
				 * @package Vendidero/Germanized/DHL
				 */
				apply_filters( 'woocommerce_gzd_deutsche_post_label_api_position_x', $label->get_position_x(), $label, $shipment ),
				/**
				 * Adjust the Deutsche Post (Internetmarke) label print Y position.
				 *
				 * @param mixed $y The y axis position.
				 * @param DeutschePost $label The label instance.
				 * @param Shipment $shipment The shipment instance.
				 *
				 * @since 3.4.5
				 * @package Vendidero/Germanized/DHL
				 */
				apply_filters( 'woocommerce_gzd_deutsche_post_label_api_position_y', $label->get_position_y(), $label, $shipment ),
				apply_filters( 'woocommerce_gzd_deutsche_post_label_api_page_number', 1, $label, $shipment )
			);

			$order_item = new \baltpeter\Internetmarke\OrderItem( $label->get_product_id(), null, $address_binding, $position, 'AddressZone' );
			$stamp      = $api->checkoutShoppingCartPdf( $this->get_user()->getUserToken(), $label->get_page_format(), array( $order_item ), $label->get_stamp_total(), $shop_order_id, null, true, 2 );

			return $this->update_default_label( $label, $stamp );
		} catch ( \Exception $e ) {
			throw new \Exception( sprintf( _x( 'Error while trying to purchase the stamp. Please manually <a href="%s">refresh</a> your product database and try again.', 'dhl', 'woocommerce-germanized' ), esc_url( Package::get_deutsche_post_shipping_provider()->get_edit_link( 'label' ) ) ) );
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
				throw new \Exception( _x( 'Error while downloading the PDF stamp.', 'dhl', 'woocommerce-germanized' ) );
			}

			$label->save();

			return $label;
		} else {
			throw new \Exception( _x( 'Invalid stamp response.', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	public function update_products() {
		$this->load_products();

		return $this->products->update();
	}
}
