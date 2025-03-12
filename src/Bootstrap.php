<?php
namespace Vendidero\Germanized;

use Vendidero\Germanized\Blocks\Assets;
use Vendidero\Germanized\Blocks\BlockTypesController;
use Vendidero\Germanized\Blocks\Cart;
use Vendidero\Germanized\Blocks\Checkout;
use Vendidero\Germanized\Blocks\Integrations\ProductElements;
use Vendidero\Germanized\Blocks\MiniCart;
use Vendidero\Germanized\Blocks\PaymentGateways\DirectDebit;
use Vendidero\Germanized\Blocks\PaymentGateways\Invoice;
use Vendidero\Germanized\Blocks\Products;
use Vendidero\Germanized\Registry\Container;

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

		do_action( 'woocommerce_gzd_container_loaded' );
	}

	/**
	 * Init the package - load the blocks library and define constants.
	 */
	protected function init() {
		if ( ! did_action( 'woocommerce_shiptastic_init' ) ) {
			add_action( 'woocommerce_shiptastic_init', array( Shiptastic::class, 'init' ), 0 );
		} else {
			Shiptastic::init();
		}

		if ( Package::load_blocks() ) {
			$this->register_dependencies();
			$this->register_payment_methods();

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
		add_filter(
			'__experimental_woocommerce_blocks_add_data_attributes_to_namespace',
			function ( $namespaces ) {
				return array_merge( $namespaces, array( 'woocommerce-germanized', 'woocommerce-germanized-blocks' ) );
			}
		);

		$this->container->get( BlockTypesController::class );
		$this->container->get( Assets::class );
		$this->container->get( Products::class );
		$this->container->get( Checkout::class );
		$this->container->get( MiniCart::class );
		$this->container->get( Cart::class );
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
			BlockTypesController::class,
			function ( $container ) {
				$assets = $container->get( Assets::class );

				return new BlockTypesController( $assets );
			}
		);

		$this->container->register(
			Checkout::class,
			function ( $container ) {
				return new Checkout();
			}
		);

		$this->container->register(
			Products::class,
			function ( $container ) {
				return new Products();
			}
		);

		$this->container->register(
			MiniCart::class,
			function ( $container ) {
				return new MiniCart();
			}
		);

		$this->container->register(
			ProductElements::class,
			function ( $container ) {
				return new ProductElements();
			}
		);

		$this->container->register(
			Cart::class,
			function ( $container ) {
				return new Cart();
			}
		);
	}

	/**
	 * Register payment method integrations with the container.
	 */
	protected function register_payment_methods() {
		$this->container->register(
			Invoice::class,
			function ( $container ) {
				$asset_api = $container->get( Assets::class );
				return new Invoice( $asset_api );
			}
		);

		$this->container->register(
			DirectDebit::class,
			function ( $container ) {
				$asset_api = $container->get( Assets::class );
				return new DirectDebit( $asset_api );
			}
		);
	}
}
