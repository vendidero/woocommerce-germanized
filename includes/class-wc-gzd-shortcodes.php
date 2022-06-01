<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Adds Germanized Shortcodes
 *
 * @class        WC_GZD_Shortcodes
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Shortcodes {

	/**
	 * Initializes Shortcodes
	 */
	public static function init() {

		// Rename the original WooCommerce Shortcode tag so that we can add our custom function to it
		add_filter( 'add_to_cart_shortcode_tag', __CLASS__ . '::replace_add_to_cart_shortcode', 10 );

		// Define shortcodes
		$shortcodes = array(
			'revocation_form'                    => __CLASS__ . '::revocation_form',
			'payment_methods_info'               => __CLASS__ . '::payment_methods_info',
			'add_to_cart'                        => __CLASS__ . '::gzd_add_to_cart',
			'gzd_vat_info'                       => __CLASS__ . '::gzd_vat_info',
			'gzd_sale_info'                      => __CLASS__ . '::gzd_sale_info',
			'gzd_complaints'                     => __CLASS__ . '::gzd_complaints',
			'gzd_product_unit_price'             => __CLASS__ . '::gzd_product_unit_price',
			'gzd_product_units'                  => __CLASS__ . '::gzd_product_units',
			'gzd_product_delivery_time'          => __CLASS__ . '::gzd_product_delivery_time',
			'gzd_product_tax_notice'             => __CLASS__ . '::gzd_product_tax_notice',
			'gzd_product_shipping_notice'        => __CLASS__ . '::gzd_product_shipping_notice',
			'gzd_product_cart_desc'              => __CLASS__ . '::gzd_product_cart_desc',
			'gzd_product_defect_description'     => __CLASS__ . '::gzd_product_defect_description',
			'gzd_product_deposit'                => __CLASS__ . '::gzd_product_deposit',
			'gzd_product_deposit_packaging_type' => __CLASS__ . '::gzd_product_deposit_packaging_type',
			'gzd_email_legal_page_attachments'   => __CLASS__ . '::gzd_email_legal_page_attachments',
		);

		foreach ( array_keys( WC_GZD_Food_Helper::get_food_attribute_types() ) as $food_type ) {
			$suffix_type = strstr( $food_type, 'food_' ) ? $food_type : 'food_' . $food_type;

			$shortcodes[ "gzd_product_{$suffix_type}" ] = __CLASS__ . '::gzd_product_food';
		}

		foreach ( $shortcodes as $shortcode => $function ) {
			/**
			 * Filter the shortcode tag.
			 *
			 * @param string $shortcode The shortcode name.
			 *
			 * @since 1.0.0
			 *
			 */
			add_shortcode( apply_filters( "gzd_{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	protected static function get_gzd_product_shortcode( $atts, $function_name = '' ) {
		if ( empty( $function_name ) || ! is_callable( $function_name ) ) {
			return;
		}

		global $product;

		$content     = '';
		$org_product = false;

		$atts = wp_parse_args(
			$atts,
			array(
				'product' => '',
			)
		);

		if ( ! empty( $atts['product'] ) ) {
			$org_product = $product;
			$product     = wc_get_product( $atts['product'] );
		}

		if ( $product && is_a( $product, 'WC_Product' ) ) {
			ob_start();
			call_user_func( $function_name );
			$content = ob_get_clean();
		}

		/**
		 * Reset global product data
		 */
		if ( $org_product ) {
			$product = $org_product;
		}

		return $content;
	}

	public static function gzd_product_unit_price( $atts ) {
		/**
		 * Filter shortcode product unit price output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_unit_price_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_price_unit' ), $atts );
	}

	public static function gzd_product_units( $atts ) {
		/**
		 * Filter shortcode product unit output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_units_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_product_units' ), $atts );
	}

	public static function gzd_product_delivery_time( $atts ) {
		/**
		 * Filter shortcode product delivery time output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_delivery_time_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_delivery_time_info' ), $atts );
	}

	public static function gzd_product_tax_notice( $atts ) {
		/**
		 * Filter shortcode product tax notice output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_tax_notice_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_tax_info' ), $atts );
	}

	public static function gzd_product_shipping_notice( $atts ) {
		/**
		 * Filter shortcode product shipping notice output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_shipping_notice_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_shipping_costs_info' ), $atts );
	}

	/**
	 * This shortcode attaches legal page content to the footer for a certain email template.
	 * Is useful in case an email customizer plugin is used to style emails.
	 *
	 * @param $atts
	 */
	public static function gzd_email_legal_page_attachments( $atts ) {
		$atts = wp_parse_args(
			$atts,
			array(
				'email_id' => '',
			)
		);

		$instance = false;

		if ( ! empty( $atts['email_id'] ) ) {
			$instance = WC_germanized()->emails->get_email_instance_by_id( $atts['email_id'] );
		}

		WC_germanized()->emails->add_template_footers( $instance );
	}

	public static function gzd_product_cart_desc( $atts ) {
		global $product;

		$content = '';

		$atts = wp_parse_args(
			$atts,
			array(
				'product' => '',
			)
		);

		if ( ! empty( $atts['product'] ) ) {
			$product = wc_get_product( $atts['product'] );
		}

		if ( ! empty( $product ) && is_a( $product, 'WC_Product' ) ) {
			$content = '<div class="wc-gzd-item-desc item-desc">' . do_shortcode( wc_gzd_get_gzd_product( $product )->get_formatted_cart_description() ) . '</div>';
		}

		return $content;
	}

	public static function gzd_product_defect_description( $atts ) {
		/**
		 * Filter shortcode product defect description output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 3.8.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_defect_description_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_defect_description' ), $atts );
	}

	public static function gzd_product_deposit( $atts ) {
		/**
		 * Filter shortcode product deposit output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 3.9.0
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_deposit_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_deposit' ), $atts );
	}

	public static function gzd_product_food( $atts, $content, $tag ) {
		$food_type = sanitize_key( str_replace( 'gzd_product_food_', '', $tag ) );

		if ( in_array( $food_type, array( 'place_of_origin', 'description', 'distributor' ), true ) ) {
			$food_type = 'food_' . $food_type;
		}

		/**
		 * Filter shortcode product food output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 3.9.0
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_food_html', '', $food_type, $atts );
	}

	public static function gzd_product_deposit_packaging_type( $atts ) {
		/**
		 * Filter shortcode product deposit packaging type output.
		 *
		 * @param string $html The output.
		 * @param array $atts The shortcode arguments.
		 *
		 * @since 3.9.0
		 */
		return apply_filters( 'woocommerce_gzd_shortcode_product_deposit_packaging_type_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_deposit_packaging_type' ), $atts );
	}

	public static function gzd_add_price_suffixes( $price, $org_product ) {
		global $product;
		$product = $org_product;

		ob_start();
		woocommerce_gzd_template_single_legal_info();
		$legal = ob_get_clean();

		ob_start();
		echo '<span class="price price-unit">';
		woocommerce_gzd_template_single_price_unit();
		echo '</span>';
		$unit = ob_get_clean();

		return $price . '<span class="wc-gzd-legal-price-info">' . strip_tags( $unit . $legal, '<span><a><ins><del>' ) . '</span>';
	}

	public static function gzd_add_to_cart( $atts ) {
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'gzd_add_price_suffixes' ), 10, 2 );
		$html = WC_Shortcodes::product_add_to_cart( $atts );
		remove_filter( 'woocommerce_get_price_html', array( __CLASS__, 'gzd_add_price_suffixes' ), 10 );

		return $html;
	}

	public static function replace_add_to_cart_shortcode( $shortcode ) {
		return 'add_to_cart_legacy';
	}

	public static function gzd_complaints( $atts ) {
		$atts = wp_parse_args(
			$atts,
			array(
				'text_only' => 'no',
			)
		);

		$atts['text_only'] = wc_string_to_bool( $atts['text_only'] );

		$texts = array(
			'dispute' => wc_gzd_get_dispute_resolution_text(),
		);

		foreach ( $texts as $key => $text ) {
			$texts[ $key ] = wpautop(
				str_replace(
					array(
						'https://ec.europa.eu/consumers/odr',
						'http://ec.europa.eu/consumers/odr/',
					),
					'<a href="https://ec.europa.eu/consumers/odr" target="_blank">https://ec.europa.eu/consumers/odr</a>',
					$text
				)
			);
		}

		if ( $atts['text_only'] ) {
			$texts['dispute'] = preg_replace( '%<p(.*?)>|</p>%s', '', $texts['dispute'] );
		}

		ob_start();
		wc_get_template(
			'global/complaints.php',
			array(
				'dispute_text' => $texts['dispute'],
				'text_only'    => $atts['text_only'],
			)
		);
		$return = ( $atts['text_only'] ? '' : '<div class="woocommerce woocommerce-gzd woocommerce-gzd-complaints-shortcode">' ) . ob_get_clean() . ( $atts['text_only'] ? '' : '</div>' );

		return $return;
	}

	/**
	 * Returns revocation_form template html
	 *
	 * @param array $atts
	 *
	 * @return string revocation form html
	 */
	public static function revocation_form( $atts ) {
		ob_start();
		wc_get_template( 'forms/revocation-form.php' );
		$return = '<div class="woocommerce woocommerce-gzd">' . ob_get_clean() . '</div>';

		return $return;
	}

	/**
	 * Returns payment methods info html
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function payment_methods_info( $atts ) {

		WC_GZD_Payment_Gateways::instance()->manipulate_gateways();

		ob_start();
		wc_get_template( 'global/payment-methods.php' );
		$return = '<div class="woocommerce woocommerce-gzd">' . ob_get_clean() . '</div>';

		return $return;

	}

	/**
	 * Returns VAT info
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public static function gzd_vat_info( $atts, $content = '' ) {
		ob_start();
		wc_get_template( 'footer/vat-info.php' );

		return ob_get_clean();
	}

	/**
	 * Returns Sale info
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public static function gzd_sale_info( $atts, $content = '' ) {
		ob_start();
		wc_get_template( 'footer/sale-info.php' );

		return ob_get_clean();
	}

}
