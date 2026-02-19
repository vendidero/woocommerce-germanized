<?php
namespace Vendidero\Shiptastic\Blocks\StoreApi;

use Vendidero\Shiptastic\Package;

/**
 * StoreApi Main Class.
 */
final class StoreApi {
	/**
	 * Init and hook in Store API functionality.
	 */
	public function init() {
		add_action(
			'rest_api_init',
			function () {
				Package::container()->get( RoutesController::class )->register_all_routes();
			}
		);
	}
}
