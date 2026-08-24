<?php
namespace Vendidero\Germanized\StoreApi;

final class StoreApi {

	public function __construct() {
		add_action(
			'rest_api_init',
			function () {
				$this->register();
			},
			9
		);
	}

	protected function register() {
		$route = new ProductsGaranLabel();

		register_rest_route(
			$route::get_namespace(),
			$route::get_path(),
			$route->get_args()
		);
	}
}
