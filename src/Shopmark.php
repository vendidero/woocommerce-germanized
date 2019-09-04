<?php
/**
 * Loads WooCommece packages from the /packages directory. These are packages developed outside of core.
 *
 * @package Vendidero/Germanized
 */
namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

/**
 * Packages class.
 *
 * @since 3.7.0
 */
class Shopmark {

	protected $default_priority = 10;

	protected $callback = null;

	protected $default_filter = '';

	protected $location = '';

	protected $type = '';

	protected $default_enabled = true;

	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'default_priority' => 10,
			'callback'         => null,
			'default_filter'   => '',
			'location'         => '',
			'type'             => '',
			'default_enabled'  => true,
		) );

		$this->default_priority = $args['default_priority'];
		$this->callback         = $args['callback'];
		$this->default_filter   = $args['default_filter'];
		$this->location         = $args['location'];
		$this->type             = $args['type'];
		$this->default_enabled  = $args['default_enabled'];
	}

	protected function get_hook_prefix() {
		$location = $this->get_location();
		$type     = $this->get_type();

		return "woocommerce_gzd_shopmark_{$location}_{$type}_";
	}

	protected function get_option( $default = '', $suffix = '' ) {
		$option_name = $this->get_option_name( $suffix );

		return get_option( $option_name, $default );
	}

	public function get_option_name( $suffix = '' ) {
		$location = $this->get_location();
		$type     = $this->get_type();

		$option_name = "woocommerce_gzd_display_{$location}_{$type}" . ( ! empty( $suffix ) ? '_' . $suffix : '' );

		return $option_name;
	}

	public function get_type() {
		return sanitize_key( $this->type );
	}

	public function get_location() {
		return sanitize_key( $this->location );
	}

	public function get_default_priority() {
		return apply_filters( $this->get_hook_prefix() . 'default_priority', $this->default_priority, $this );
	}

	public function get_default_filter() {
		return apply_filters( $this->get_hook_prefix() . 'default_filter', $this->default_filter, $this );
	}

	public function get_number_of_params() {
		$filter = Shopmarks::get_filter( $this->get_location(), $this->get_filter() );

		return $filter ? $filter['number_of_params'] : 1;
	}

	public function reset_options() {
		update_option( $this->get_option_name(), $this->is_default_enabled() ? 'yes' : 'no' );
		update_option( $this->get_option_name( 'priority' ), $this->get_default_priority() );
		update_option( $this->get_option_name( 'filter' ), $this->get_default_filter() );
	}

	public function get_callback() {
		$callback = $this->callback;

		if ( is_null( $this->callback ) ) {
			$location = $this->get_location();
			$type     = $this->get_type();
			$callback = "woocommerce_gzd_template_{$location}_{$type}";
		}

		return $callback;
	}

	public function get_is_action() {
		$filter = Shopmarks::get_filter( $this->get_location(), $this->get_filter() );

		return $filter ? $filter['is_action'] : true;
	}

	public function get_priority() {
		$priority = $this->get_option( $this->get_default_priority(), 'priority' );

		return apply_filters( $this->get_hook_prefix() . 'priority', $priority, $this );
	}

	public function get_filter() {
		$filter = $this->get_option( $this->get_default_filter(), 'filter' );
		$filter = apply_filters( $this->get_hook_prefix() . 'filter', $filter, $this );

		// Make sure that the current filter name exists e.g. for custom theme support
		if ( ! Shopmarks::get_filter( $this->get_location(), $filter ) ) {
			$filter = $this->get_default_filter();
		}

		return $filter;
	}

	public function is_default_enabled() {
		return $this->default_enabled;
	}

	public function is_enabled() {
		$is_enabled = $this->get_option( $this->is_default_enabled() ? 'yes' : 'no' );

		return 'yes' === $is_enabled;
	}

	public function execute() {

		if ( is_null( $this->get_callback() ) ) {
			return;
		}

		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( $this->get_is_action() ) {
			add_action( $this->get_filter(), $this->get_callback(), $this->get_priority(), $this->get_number_of_params() );
		} else {
			add_filter( $this->get_filter(), $this->get_callback(), $this->get_priority(), $this->get_number_of_params() );
		}
	}

	public function remove() {
		if ( is_null( $this->get_callback() ) ) {
			return;
		}

		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( $this->get_is_action() ) {
			remove_action( $this->get_filter(), $this->get_callback(), $this->get_priority() );
		} else {
			remove_filter( $this->get_filter(), $this->get_callback(), $this->get_priority() );
		}
	}
}