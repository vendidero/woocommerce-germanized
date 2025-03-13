<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\DHL\Api;

use Exception;
use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\Interfaces\Api;

defined( 'ABSPATH' ) || exit;

abstract class Soap extends \Vendidero\Shiptastic\API\Api {

	/**
	 * Passed arguments to the API
	 *
	 * @var string
	 */
	protected $args = array();

	/**
	 * The query string
	 *
	 * @var string
	 */
	private $query = array();

	/**
	 * The request response
	 * @var array
	 */
	protected $response = null;

	/**
	 * @var array
	 */
	protected $body_request = array();

	/**
	 * DHL_Api constructor.
	 */
	public function __construct() {}

	abstract public function get_url();

	protected function get_wsdl_file( $wsdl_link ) {
		return $wsdl_link;
	}

	protected function get_auth_instance() {
		return new AuthSoap( $this->get_wsdl_file( $this->get_url() ), $this );
	}

	abstract public function get_client();

	protected function walk_recursive_remove( array $the_array ) {
		foreach ( $the_array as $k => $v ) {
			if ( is_array( $v ) ) {
				$the_array[ $k ] = $this->walk_recursive_remove( $v );
			}

			// Explicitly allow street_number fields to equal 0
			if ( empty( $v ) && ( ! in_array( $k, array( 'minorRelease', 'streetNumber', 'houseNumber', 'zip', 'active', 'postNumber' ), true ) ) ) {
				unset( $the_array[ $k ] );
			}
		}

		return $the_array;
	}
}
