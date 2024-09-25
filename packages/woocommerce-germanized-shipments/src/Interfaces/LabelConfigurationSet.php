<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use Vendidero\Germanized\Shipments\Labels\ConfigurationSet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LabelConfigurationSet Interface
 *
 * @package  Germanized/Shipments/Interfaces
 * @version  3.1.0
 */
interface LabelConfigurationSet {
	public function get_configuration_sets( $context = 'view' );

	/**
	 * @param $args
	 * @param $context
	 *
	 * @return false|ConfigurationSet
	 */
	public function get_configuration_set( $args, $context = 'view' );

	public function has_configuration_set( $args, $context = 'view' );

	public function set_configuration_sets( $sets );

	/**
	 * @param ConfigurationSet $set
	 *
	 * @return void
	 */
	public function update_configuration_set( $set );

	public function reset_configuration_sets( $args );
}
