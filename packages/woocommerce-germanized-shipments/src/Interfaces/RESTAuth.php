<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use DVDoug\BoxPacker\ConstrainedPlacementItem;
use DVDoug\BoxPacker\Item;
use Vendidero\Germanized\Shipments\API\Response;
use Vendidero\Germanized\Shipments\ShipmentError;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RESTAuth {

	public function get_type();

	/**
	 * @return Response|true
	 */
	public function auth();

	/**
	 * @return bool
	 */
	public function has_auth();

	public function get_url();

	public function is_unauthenticated_response( $code );

	/**
	 * @return array
	 */
	public function get_headers();

	public function revoke();
}
