<?php
/**
 * Label Factory
 *
 * The label factory creates the right label objects.
 *
 * @version 1.0.0
 * @package Vendidero/Germanized/DHL
 */
namespace Vendidero\Germanized\DHL\Legacy;

defined( 'ABSPATH' ) || exit;

/**
 * Label factory class
 */
class LabelFactory {

	/**
	 * Get label.
	 */
	public static function get_label( $label_id = false, $label_type = 'simple' ) {
		return \Vendidero\Germanized\Shipments\Labels\Factory::get_label( $label_id, '', $label_type );
	}

	public static function get_label_id( $label ) {
		return \Vendidero\Germanized\Shipments\Labels\Factory::get_label_id( $label );
	}
}
