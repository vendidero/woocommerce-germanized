<?php

namespace Vendidero\Shiptastic\ShippingProvider;

use Vendidero\Shiptastic\Extensions;
use Vendidero\Shiptastic\Package;

defined( 'ABSPATH' ) || exit;

class Placeholder extends Simple {

	protected $placeholder_args;

	public function __construct( $data = 0, $args = array() ) {
		$this->placeholder_args = wp_parse_args(
			$args,
			array(
				'title'               => '',
				'name'                => '',
				'description'         => '',
				'countries_supported' => array(),
				'is_pro'              => false,
				'is_builtin'          => false,
				'extension_name'      => '',
				'help_url'            => '',
			)
		);

		if ( empty( $this->placeholder_args['name'] ) ) {
			$this->placeholder_args['name'] = str_replace( '-', '_', sanitize_title( $this->placeholder_args['title'] ) );
		}

		parent::__construct( $data );
	}

	public function get_edit_link( $section = '' ) {
		return '';
	}

	public function get_original_name() {
		return $this->placeholder_args['name'];
	}

	public function get_name( $context = 'view' ) {
		return '_' . sanitize_key( $this->placeholder_args['name'] );
	}

	public function is_base_country_supported() {
		return empty( $this->placeholder_args['supported_countries'] ) || in_array( Package::get_base_country(), $this->placeholder_args['supported_countries'], true );
	}

	public function get_extension_name() {
		return $this->placeholder_args['extension_name'];
	}

	public function is_installed() {
		return $this->is_builtin() || ( ! empty( $this->get_extension_name() ) && Extensions::is_provider_integration_active( $this->get_original_name(), $this->get_extension_name() ) );
	}

	public function get_shipping_provider() {
		return wc_stc_get_shipping_provider( $this->get_original_name() );
	}

	public function get_description( $context = 'view' ) {
		return empty( $this->placeholder_args['description'] ) ? sprintf( _x( 'Conveniently create %1$s labels to your shipments.', 'shipments', 'woocommerce-germanized' ), $this->get_title() ) : $this->placeholder_args['description'];
	}

	public function get_title( $context = 'view' ) {
		$title = $this->placeholder_args['title'];

		if ( $this->is_pro() ) {
			$title .= '<span class="wc-shiptastic-pro wc-shiptastic-pro-outlined">' . _x( 'pro', 'shipments', 'woocommerce-germanized' ) . '</span>';
		}

		return $title;
	}

	public function is_manual_integration() {
		return false;
	}

	public function is_builtin() {
		return $this->placeholder_args['is_builtin'];
	}

	public function is_pro() {
		return $this->placeholder_args['is_pro'] && ! Package::is_pro() && ! Extensions::is_provider_integration_active( $this->get_original_name(), $this->get_extension_name() );
	}

	public function is_activated() {
		return $this->get_shipping_provider() ? $this->get_shipping_provider()->is_activated() : false;
	}

	public function activate() {
		return $this->get_shipping_provider() ? $this->get_shipping_provider()->activate() : false;
	}

	public function deactivate() {
		return $this->get_shipping_provider() ? $this->get_shipping_provider()->deactivate() : false;
	}

	public function get_help_link() {
		return apply_filters( "{$this->get_general_hook_prefix()}help_link", $this->placeholder_args['help_url'] ? esc_url_raw( $this->placeholder_args['help_url'] ) : '', $this );
	}

	public function save() {
		return false;
	}
}
