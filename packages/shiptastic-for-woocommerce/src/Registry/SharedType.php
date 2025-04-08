<?php
namespace Vendidero\Shiptastic\Registry;

/**
 * A definition for the SharedType dependency type.
 *
 */
class SharedType extends AbstractDependencyType {

	/**
	 * Holds a cached instance of the value stored (or returned) internally.
	 *
	 * @var mixed
	 */
	private $shared_instance;

	/**
	 * Returns the internal stored and shared value after initial generation.
	 *
	 * @param Container $container An instance of the dependency injection
	 *                             container.
	 *
	 * @return mixed
	 */
	public function get( $container ) {
		if ( empty( $this->shared_instance ) ) {
			$this->shared_instance = $this->resolve_value( $container );
		}
		return $this->shared_instance;
	}
}
