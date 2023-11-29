<?php
namespace Vendidero\Germanized\DHL;

use Vendidero\Germanized\DHL\Admin\Admin;
use Vendidero\Germanized\DHL\Blocks\Assets;
use Vendidero\Germanized\DHL\Blocks\PreferredServices;
use Vendidero\Germanized\Shipments\Registry\Container;

/**
 * Takes care of bootstrapping the plugin.
 *
 * @since 2.5.0
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

		do_action( 'woocommerce_gzd_dhl_loaded' );
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

		if ( $this->container->get( ParcelLocator::class )::is_enabled() ) {
			$this->container->get( ParcelLocator::class )::init();
		}

		if ( Package::is_dhl_enabled() && $this->container->get( ParcelServices::class )::is_enabled() ) {
			if ( \Vendidero\Germanized\Shipments\Package::load_blocks() ) {
				$this->container->get( PreferredServices::class );
			}

			$this->container->get( ParcelServices::class )::init();
		}
	}

	/**
	 * Register core dependencies with the container.
	 */
	protected function register_dependencies() {
		$this->container->register(
			Assets::class,
			function ( $container ) {
				return new Assets();
			}
		);
		$this->container->register(
			Admin::class,
			function ( $container ) {
				return Admin::class;
			}
		);
		$this->container->register(
			\Vendidero\Germanized\DHL\Blocks\Integrations\PreferredServices::class,
			function ( $container ) {
				return new \Vendidero\Germanized\DHL\Blocks\Integrations\PreferredServices();
			}
		);
		$this->container->register(
			ParcelServices::class,
			function ( $container ) {
				return ParcelServices::class;
			}
		);
		$this->container->register(
			ParcelLocator::class,
			function ( $container ) {
				return ParcelLocator::class;
			}
		);
		$this->container->register(
			Ajax::class,
			function ( $container ) {
				return Ajax::class;
			}
		);
		$this->container->register(
			PreferredServices::class,
			function ( $container ) {
				return new PreferredServices();
			}
		);
	}
}
