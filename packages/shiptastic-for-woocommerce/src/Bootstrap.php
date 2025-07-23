<?php
namespace Vendidero\Shiptastic;

use Vendidero\Shiptastic\Admin\Admin;
use Vendidero\Shiptastic\Registry\Container;
use Vendidero\Shiptastic\ShippingMethod\MethodHelper;

/**
 * Takes care of bootstrapping the plugin.
 *
 */
class Bootstrap {

	/**
	 * Holds the Dependency Injection Container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Constructor
	 *
	 * @param Container $container  The Dependency Injection Container.
	 */
	public function __construct( $container ) {
		$this->container = $container;
		$this->init();
	}

	/**
	 * Init the package - load the blocks library and define constants.
	 */
	protected function init() {
		$this->register_dependencies();

		if ( is_admin() ) {
			$this->container->get( Admin::class )::init();
		}

		$this->container->get( Ajax::class )::init();
		$this->container->get( MethodHelper::class )::init();
		$this->container->get( Automation::class )::init();
		$this->container->get( Labels\Automation::class )::init();
		$this->container->get( Labels\DownloadHandler::class )::init();
		$this->container->get( Emails::class )::init();
		$this->container->get( Validation::class )::init();
		$this->container->get( Api::class )::init();
		$this->container->get( FormHandler::class )::init();
		$this->container->get( PickupDelivery::class )::init();
		$this->container->get( Packaging\ReportHelper::class )::init();
		$this->container->get( Tracking\Helper::class )::init();
		$this->container->get( Caches\Helper::class )::init();

		if ( Package::load_blocks() ) {
			if ( did_action( 'woocommerce_blocks_loaded' ) ) {
				$this->load_blocks();
			} else {
				add_action(
					'woocommerce_blocks_loaded',
					function () {
						$this->load_blocks();
					}
				);
			}
		}
	}

	protected function load_blocks() {
		$this->container->get( Blocks\Checkout::class );
		$this->container->get( Blocks\StoreApi\StoreApi::class )->init();
	}

	/**
	 * Register core dependencies with the container.
	 */
	protected function register_dependencies() {
		$this->container->register(
			Admin::class,
			function ( $container ) {
				return Admin::class;
			}
		);
		$this->container->register(
			Ajax::class,
			function ( $container ) {
				return Ajax::class;
			}
		);
		$this->container->register(
			ShippingMethod\MethodHelper::class,
			function ( $container ) {
				return ShippingMethod\MethodHelper::class;
			}
		);
		$this->container->register(
			Automation::class,
			function ( $container ) {
				return Automation::class;
			}
		);
		$this->container->register(
			Labels\Automation::class,
			function ( $container ) {
				return Labels\Automation::class;
			}
		);
		$this->container->register(
			Labels\DownloadHandler::class,
			function ( $container ) {
				return Labels\DownloadHandler::class;
			}
		);
		$this->container->register(
			Emails::class,
			function ( $container ) {
				return Emails::class;
			}
		);
		$this->container->register(
			Validation::class,
			function ( $container ) {
				return Validation::class;
			}
		);
		$this->container->register(
			Api::class,
			function ( $container ) {
				return Api::class;
			}
		);
		$this->container->register(
			FormHandler::class,
			function ( $container ) {
				return FormHandler::class;
			}
		);
		$this->container->register(
			PickupDelivery::class,
			function ( $container ) {
				return PickupDelivery::class;
			}
		);
		$this->container->register(
			Packaging\ReportHelper::class,
			function ( $container ) {
				return Packaging\ReportHelper::class;
			}
		);
		$this->container->register(
			Tracking\Helper::class,
			function ( $container ) {
				return Tracking\Helper::class;
			}
		);
		$this->container->register(
			Caches\Helper::class,
			function ( $container ) {
				return Caches\Helper::class;
			}
		);
		$this->container->register(
			Blocks\Checkout::class,
			function ( $container ) {
				return new Blocks\Checkout( $container->get( Blocks\StoreApi\SchemaController::class ) );
			}
		);
		$this->container->register(
			Blocks\Integrations\CheckoutPickupLocationSelect::class,
			function ( $container ) {
				return new Blocks\Integrations\CheckoutPickupLocationSelect();
			}
		);
		$this->container->register(
			Blocks\Assets::class,
			function ( $container ) {
				return new Blocks\Assets();
			}
		);
		$this->container->register(
			Blocks\StoreApi\StoreApi::class,
			function ( $container ) {
				return new Blocks\StoreApi\StoreApi();
			}
		);
		$this->container->register(
			Blocks\StoreApi\SchemaController::class,
			function ( $container ) {
				return new Blocks\StoreApi\SchemaController();
			}
		);
		$this->container->register(
			Blocks\StoreApi\RoutesController::class,
			function ( $container ) {
				return new Blocks\StoreApi\RoutesController( $container->get( Blocks\StoreApi\SchemaController::class ) );
			}
		);
	}
}
