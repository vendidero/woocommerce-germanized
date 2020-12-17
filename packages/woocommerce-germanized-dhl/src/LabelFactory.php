<?php
/**
 * Label Factory
 *
 * The label factory creates the right label objects.
 *
 * @version 1.0.0
 * @package Vendidero/Germanized/DHL
 */
namespace Vendidero\Germanized\DHL;

use Vendidero\Germanized\DHL\Label;
use \WC_Data_Store;
use \Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Label factory class
 */
class LabelFactory {

	/**
	 * Get label.
	 *
	 * @param  mixed $label_id (default: false) Label id to get or empty if new.
	 * @return SimpleLabel|ReturnLabel|bool
	 */
	public static function get_label( $label_id = false, $label_type = 'simple' ) {
		$label_id = self::get_label_id( $label_id );

		if ( $label_id ) {
			$label_type      = WC_Data_Store::load( 'dhl-label' )->get_label_type( $label_id );
			$label_type_data = wc_gzd_dhl_get_label_type_data( $label_type );
		} else {
			$label_type_data = wc_gzd_dhl_get_label_type_data( $label_type );
		}

		if ( $label_type_data ) {
			$classname = $label_type_data['class_name'];
		} else {
			$classname = false;
		}

		/**
		 * Filter that allows adjusting the default DHL label classname.
		 *
		 * @param string  $classname The classname to be used.
		 * @param integer $label_id The label id.
		 * @param string  $label_type The label type.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		$classname = apply_filters( 'woocommerce_gzd_dhl_label_class', $classname, $label_id, $label_type );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return new $classname( $label_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, func_get_args() );
			return false;
		}
	}

	public static function get_label_id( $label ) {
		if ( is_numeric( $label ) ) {
			return $label;
		} elseif ( $label instanceof Label ) {
			return $label->get_id();
		} elseif ( ! empty( $label->label_id ) ) {
			return $label->label_id;
		} else {
			return false;
		}
	}
}
