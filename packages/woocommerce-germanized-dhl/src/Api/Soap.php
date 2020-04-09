<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\DHL\Api;

use Exception;

defined( 'ABSPATH' ) || exit;

abstract class Soap {

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
     * @var
     */
    protected $soap_auth = null;

    /**
     * @var array
     */
    protected $body_request = array();

    /**
     * DHL_Api constructor.
     *
     * @param string $api_key, $api_secret
     */
    public function __construct( $wsdl_link ) {
        try {
            $this->soap_auth = new AuthSoap( $wsdl_link );
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    protected function get_auth_api() {
        return $this->soap_auth;
    }

    abstract public function get_access_token();

    protected function walk_recursive_remove( array $array ) {
        foreach ( $array as $k => $v ) {

            if ( is_array( $v ) ) {
                $array[ $k ] = $this->walk_recursive_remove( $v );
            }

            // Explicitly allow street_number fields to equal 0
            if ( empty( $v ) && ( ! in_array( $k, array( 'minorRelease', 'streetNumber', 'houseNumber' ) ) ) ) {
                unset( $array[ $k ] );
            }
        }

        return $array;
    }
}
