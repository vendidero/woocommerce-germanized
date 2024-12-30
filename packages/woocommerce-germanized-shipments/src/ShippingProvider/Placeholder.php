<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Extensions;
use Vendidero\Germanized\Shipments\Package;

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
				'extension_name'      => '',
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

	public function get_description( $context = 'view' ) {
		return empty( $this->placeholder_args['description'] ) ? sprintf( _x( 'Conveniently create %1$s labels to your shipments.', 'shipments', 'woocommerce-germanized' ), $this->get_title() ) : $this->placeholder_args['description'];
	}

	public function get_title( $context = 'view' ) {
		$title = $this->placeholder_args['title'];

		if ( $this->is_pro() ) {
			$title .= '<span class="wc-gzd-shipments-pro wc-gzd-shipments-pro-outlined">' . _x( 'pro', 'shipments', 'woocommerce-germanized' ) . '</span>';
		}

		return $title;
	}

	public function is_manual_integration() {
		return false;
	}

	public function is_pro() {
		return $this->placeholder_args['is_pro'] && ! Package::is_pro() && ! Extensions::is_provider_integration_active( $this->get_original_name(), $this->get_extension_name() );
	}

	public function is_activated() {
		return false;
	}

	public function activate() {
		return false;
	}

	public function get_help_link() {
		return 'https://vendidero.de/woocommerce-germanized/features';
	}

	public function save() {
		return false;
	}
}
