<?php

namespace Vendidero\Shiptastic\Labels;

use Vendidero\Shiptastic\Package;

defined( 'ABSPATH' ) || exit;

trait ConfigurationSetTrait {

	protected $configuration_sets = null;

	abstract public function get_prop( $key, $context = 'view' );

	abstract public function set_prop( $key, $value );

	abstract protected function get_configuration_set_setting_type();

	public function get_configuration_sets( $context = 'view' ) {
		return $this->get_prop( 'configuration_sets', $context );
	}

	protected function get_configuration_set_default_args( $args ) {
		if ( is_array( $args ) ) {
			$args = wp_parse_args(
				$args,
				array(
					'shipping_provider_name' => is_a( $this, '\Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ? $this->get_name() : '',
					'shipment_type'          => 'simple',
					'zone'                   => 'dom',
					'setting_type'           => $this->get_configuration_set_setting_type(),
				)
			);
		} elseif ( is_a( $args, 'Vendidero\Shiptastic\Shipment' ) ) {
			$args = wp_parse_args(
				$args,
				array(
					'shipping_provider_name' => $args->get_shipping_provider(),
					'shipment_type'          => $args->get_type(),
					'zone'                   => $args->get_shipping_zone(),
					'setting_type'           => $this->get_configuration_set_setting_type(),
				)
			);
		} else {
			$args = $this->get_configuration_set_args_by_id( $args );
		}

		return $args;
	}

	protected function get_configuration_set_id( $args ) {
		$args   = $this->get_configuration_set_default_args( $args );
		$set_id = '';

		if ( ! empty( $args['shipping_provider_name'] ) ) {
			$set_id = '-p-' . $args['shipping_provider_name'];
		}

		$set_id = "{$set_id}-s-{$args['shipment_type']}-z-{$args['zone']}";

		return $set_id;
	}

	public function get_configuration_set_args_by_id( $id ) {
		return Package::extract_args_from_id( $id );
	}

	/**
	 * @param $args
	 * @param $context
	 *
	 * @return false|ConfigurationSet
	 */
	protected function get_configuration_set_data( $args, $context = 'view' ) {
		if ( is_string( $args ) ) {
			$id = $args;
		} else {
			$id = $this->get_configuration_set_id( $args );
		}

		$configuration_sets = $this->get_configuration_sets( $context );

		if ( array_key_exists( $id, $configuration_sets ) ) {
			return $configuration_sets[ $id ];
		}

		return false;
	}

	public function has_configuration_set( $args, $context = 'view' ) {
		return $this->get_configuration_set( $args, $context ) ? true : false;
	}

	/**
	 * @param $args
	 * @param $context
	 *
	 * @return false|ConfigurationSet
	 */
	public function get_configuration_set( $args, $context = 'view' ) {
		if ( is_string( $args ) ) {
			$configuration_set_id = $this->get_configuration_set_id( $args );
		} else {
			$args                 = $this->get_configuration_set_default_args( $args );
			$configuration_set_id = $this->get_configuration_set_id( $args );
		}

		$configuration_set = false;

		if ( ! is_null( $this->configuration_sets ) && array_key_exists( $configuration_set_id, $this->configuration_sets ) ) {
			return $this->configuration_sets[ $configuration_set_id ];
		} elseif ( $configuration_set_data = $this->get_configuration_set_data( $configuration_set_id, $context ) ) {
			$configuration_set = new ConfigurationSet( $configuration_set_data, $this );

			if ( is_null( $this->configuration_sets ) ) {
				$this->configuration_sets = array();
			}

			$this->configuration_sets[ $configuration_set_id ] = $configuration_set;

			return $this->configuration_sets[ $configuration_set_id ];
		}

		return $configuration_set;
	}

	public function set_configuration_sets( $sets ) {
		$this->set_prop( 'configuration_sets', array_filter( (array) $sets ) );
		$this->configuration_sets = null;
	}

	/**
	 * @param ConfigurationSet $set
	 *
	 * @return void
	 */
	public function update_configuration_set( $set ) {
		$configuration_sets = $this->get_configuration_sets( 'edit' );
		$set_id             = $this->get_configuration_set_id(
			array(
				'shipping_provider_name' => $set->get_shipping_provider_name(),
				'shipment_type'          => $set->get_shipment_type(),
				'zone'                   => $set->get_zone(),
			)
		);

		$configuration_sets[ $set_id ] = $set->get_data();

		$this->set_configuration_sets( $configuration_sets );
	}

	/**
	 * @param $args
	 *
	 * @return ConfigurationSet
	 */
	public function get_or_create_configuration_set( $args = array(), $context = 'view' ) {
		if ( $configuration_set = $this->get_configuration_set( $args, $context ) ) {
			return $configuration_set;
		} else {
			$args              = $this->get_configuration_set_default_args( $args );
			$configuration_set = new ConfigurationSet( $args, $this );

			$this->update_configuration_set( $configuration_set );

			return $configuration_set;
		}
	}

	private function get_configuration_set_setting_parts( $setting_name ) {
		if ( 'label_config_set_' === substr( $setting_name, 0, 17 ) ) {
			return $this->get_configuration_set_args_by_id( substr( $setting_name, 17 ) );
		}

		return false;
	}

	public function get_configuration_set_id_by_setting_name( $setting_name ) {
		if ( $this->is_configuration_set_setting( $setting_name ) ) {
			if ( $parts = $this->get_configuration_set_setting_parts( $setting_name ) ) {
				return $this->get_configuration_set_id( $parts );
			}
		}

		return false;
	}

	public function is_configuration_set_setting( $setting_name ) {
		if ( 'label_config_set_' === substr( $setting_name, 0, 17 ) ) {
			return true;
		}

		return false;
	}

	public function get_configuration_setting_suffix( $setting_name ) {
		if ( $this->is_configuration_set_setting( $setting_name ) ) {
			if ( $parts = $this->get_configuration_set_setting_parts( $setting_name ) ) {
				return $parts['setting_name'];
			}
		}

		return false;
	}

	public function reset_configuration_sets( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'shipping_provider_name' => '',
				'shipment_type'          => '',
				'zone'                   => '',
			)
		);

		$id_prefix = '';

		foreach ( $args as $arg => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( 'shipping_provider_name' === $arg ) {
				$id_prefix = '-p-' . $value;
			} elseif ( 'shipment_type' === $arg ) {
				$id_prefix = $id_prefix . '-s-' . $value;
			} elseif ( 'zone' === $arg ) {
				$id_prefix = $id_prefix . '-z-' . $value;
			}
		}

		if ( empty( $id_prefix ) ) {
			$this->set_configuration_sets( array() );
		} else {
			$configuration_sets = $this->get_configuration_sets( 'edit' );

			foreach ( $configuration_sets as $set_id => $set ) {
				if ( strstr( $set_id, $id_prefix ) ) {
					unset( $configuration_sets[ $set_id ] );
				}
			}

			$this->set_configuration_sets( $configuration_sets );
		}
	}
}
