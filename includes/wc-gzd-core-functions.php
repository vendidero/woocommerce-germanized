<?php
/**
 * Core Functions
 *
 * WC_GZD core functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-product-functions.php';

function wc_gzd_get_dependencies( $instance = null ) {
	return apply_filters( 'woocommerce_gzd_dependencies_instance', WC_GZD_Dependencies::instance( $instance ) );
}

function wc_gzd_send_instant_order_confirmation() {
    return ( apply_filters( 'woocommerce_gzd_instant_order_confirmation', true ) && ( 'yes' !== get_option( 'woocommerce_gzd_disable_instant_order_confirmation' ) ) );
}

/**
 * Format tax rate percentage for output in frontend
 *  
 * @param  float  $rate   
 * @param  boolean $percent show percentage after number
 * @return string
 */
function wc_gzd_format_tax_rate_percentage( $rate, $percent = false ) {
	return str_replace( '.', ',', wc_format_decimal( str_replace( '%', '', $rate ), true, true ) ) . ( $percent ? '%' : '' );
}

function wc_gzd_is_customer_activated( $user_id = '' ) {
	
	if ( is_user_logged_in() && empty( $user_id ) )
		$user_id = get_current_user_id();

	if ( empty( $user_id ) || ! $user_id )
		return false;

	return ( get_user_meta( $user_id, '_woocommerce_activation', true ) ? false : true );
}

function wc_gzd_get_hook_priority( $hook ) {
	return WC_GZD_Hook_Priorities::instance()->get_hook_priority( $hook );
}

function wc_gzd_get_legal_pages( $email_attachable_only = false ) {
    $legal_pages = array(
        'terms'           => __( 'Terms & Conditions', 'woocommerce-germanized' ),
        'revocation'      => __( 'Right of Recission', 'woocommerce-germanized' ),
        'imprint'         => __( 'Imprint', 'woocommerce-germanized' ),
        'data_security'   => __( 'Data Security', 'woocommerce-germanized' ),
    );

    $secondary_pages = array(
        'payment_methods' => __( 'Payment Methods', 'woocommerce-germanized' ),
        'shipping_costs'  => __( 'Shipping Costs', 'woocommerce-germanized' ),
    );

    if ( ! $email_attachable_only ) {
        $legal_pages = $legal_pages + $secondary_pages;
    }

    return apply_filters( 'woocommerce_gzd_legal_pages', $legal_pages, $email_attachable_only );
}

function wc_gzd_get_email_attachment_order() {
	$order       = explode( ',', get_option( 'woocommerce_gzd_mail_attach_order', 'terms,revocation,data_security,imprint' ) );
	$items       = array();
	$legal_pages = wc_gzd_get_legal_pages( true );

	foreach ( $order as $key => $item ) {
		$items[ $item ] = ( isset( $legal_pages[ $item ] ) ? $legal_pages[ $item ] : '' );
	}
	
	return $items;	
}

function wc_gzd_get_page_permalink( $type ) {
	$page_id = wc_get_page_id( $type );
	$link    = $page_id ? get_permalink( $page_id ) : '';

	return apply_filters( 'woocommerce_gzd_legal_page_permalink', $link, $type );
}

if ( ! function_exists( 'is_payment_methods' ) ) {

	/**
	 * is_checkout - Returns true when viewing the checkout page.
	 * @return bool
	 */
	function is_payment_methods() {
		return is_page( wc_get_page_id( 'payment_methods' ) ) || apply_filters( 'woocommerce_gzd_is_payment_methods', false ) ? true : false;
	}
}

function wc_gzd_get_small_business_notice() {
	return apply_filters( 'woocommerce_gzd_small_business_notice', get_option( 'woocommerce_gzd_small_enterprise_text', __( 'Value added tax is not collected, as small businesses according to §19 (1) UStG.', 'woocommerce-germanized' ) ) );
}

function wc_gzd_help_tip( $tip, $allow_html = false ) {
	
	if ( function_exists( 'wc_help_tip' ) )
		return wc_help_tip( $tip, $allow_html );

	return '<a class="tips" data-tip="' . ( $allow_html ? esc_html( $tip ) : $tip ) . '" href="#">[?]</a>';
}

function wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( $rate_ids = array() ) {
	$return = false;

	if ( $checkbox = wc_gzd_get_legal_checkbox( 'parcel_delivery' ) ) {

		if ( $checkbox->is_enabled() ) {
			$show = $checkbox->show_special;

			if ( 'always' === $show ) {
				$return = true;
			} else {
				$supported = $checkbox->show_shipping_methods ? $checkbox->show_shipping_methods : array();

				if ( ! is_array( $supported ) )
					$supported = array();

				$return = false;
				$rate_is_supported = true;

				if ( ! empty( $rate_ids ) ) {

					foreach ( $rate_ids as $rate_id ) {
						if ( ! in_array( $rate_id, $supported ) )
							$rate_is_supported = false;
					}

					if ( $rate_is_supported ) {
						$return = true;
					}
				}
			}
		}
	}

	return apply_filters( 'woocommerce_gzd_enable_parcel_delivery_data_transfer_checkbox', $return, $rate_ids );
}

function wc_gzd_get_dispute_resolution_text() {
	$type = get_option( 'woocommerce_gzd_dispute_resolution_type', 'none' );
	return get_option( 'woocommerce_gzd_alternative_complaints_text_' . $type );
}

function wc_gzd_get_tax_rate_label( $rate_percentage ) {
	return ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $rate_percentage ) ) : __( 'incl. VAT', 'woocommerce-germanized' ) );
}

function wc_gzd_get_shipping_costs_text( $product = false ) {
	$replacements = array(
	    '{link}'  => '<a href="' . esc_url( get_permalink( wc_get_page_id( 'shipping_costs' ) ) ) . '" target="_blank">',
        '{/link}' => '</a>',
    );

	if ( $product ) {
	    $html = $product->has_free_shipping() ? get_option( 'woocommerce_gzd_free_shipping_text' ) : get_option( 'woocommerce_gzd_shipping_costs_text' );
		return apply_filters( 'woocommerce_gzd_shipping_costs_text', wc_gzd_replace_label_shortcodes( $html, $replacements ), $product );
	} else {
		return apply_filters( 'woocommerce_gzd_shipping_costs_cart_text', wc_gzd_replace_label_shortcodes( get_option( 'woocommerce_gzd_shipping_costs_text' ), $replacements ) );
	}
}

function wc_gzd_sanitize_html_text_field( $value ) {
	return wp_kses_post( esc_html( wp_unslash( $value ) ) );
}

function wc_gzd_convert_coupon_to_voucher( $coupon ) {
	$coupon = new WC_Coupon( $coupon );
	WC_GZD_Coupon_Helper::instance()->convert_coupon_to_voucher( $coupon );
}

function wc_gzd_get_differential_taxation_notice_text() {
	return apply_filters( 'woocommerce_gzd_differential_taxation_notice_text', get_option( 'woocommerce_gzd_differential_taxation_notice_text' ) );
}

function wc_gzd_get_privacy_policy_page_id() {
	return apply_filters( 'woocommerce_gzd_privacy_policy_page_id', wc_get_page_id( 'data_security' ) );
}

function wc_gzd_get_privacy_policy_url() {
	return get_permalink( wc_gzd_get_privacy_policy_page_id() );
}

function wc_gzd_get_customer_title( $option ) {
	$options = apply_filters( 'woocommerce_gzd_title_options', array( 1 => __( 'Mr.', 'woocommerce-germanized' ), 2 => __( 'Ms.', 'woocommerce-germanized' ) ) );
	return ( array_key_exists( $option, $options ) ? $options[ $option ] : $option );
}

function wc_gzd_register_legal_checkbox( $id, $args ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	return $manager->register( $id, $args );
}

function wc_gzd_update_legal_checkbox( $id, $args ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	return $manager->update( $id, $args );
}

function wc_gzd_get_legal_checkbox( $id ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	return $manager->get_checkbox( $id );
}

function wc_gzd_remove_legal_checkbox( $id ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	$manager->remove( $id );
}

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * Is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}

/**
 * Remove Class Filter Without Access to Class Object
 *
 * In order to use the core WordPress remove_filter() on a filter added with the callback
 * to a class, you either have to have access to that class object, or it has to be a call
 * to a static method.  This method allows you to remove filters with a callback to a class
 * you don't have access to.
 *
 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
 * Updated 2-27-2017 to use internal WordPress removal for 4.7+ (to prevent PHP warnings output)
 *
 * @param string $tag         Filter to remove
 * @param string $class_name  Class name for the filter's callback
 * @param string $method_name Method name for the filter's callback
 * @param int    $priority    Priority of the filter (default 10)
 *
 * @return bool Whether the function is removed.
 */
function wc_gzd_remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
	global $wp_filter;

	// Check that filter actually exists first
	if ( ! isset( $wp_filter[ $tag ] ) ) return FALSE;

	/**
	 * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
	 * a simple array, rather it is an object that implements the ArrayAccess interface.
	 *
	 * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
	 *
	 * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
	 */
	if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
		// Create $fob object from filter tag, to use below
		$fob = $wp_filter[ $tag ];
		$callbacks = &$wp_filter[ $tag ]->callbacks;
	} else {
		$callbacks = &$wp_filter[ $tag ];
	}

	// Exit if there aren't any callbacks for specified priority
	if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) return FALSE;

	// Loop through each filter for the specified priority, looking for our class & method
	foreach( (array) $callbacks[ $priority ] as $filter_id => $filter ) {

		// Filter should always be an array - array( $this, 'method' ), if not goto next
		if ( ! isset( $filter[ 'function' ] ) || ! is_array( $filter[ 'function' ] ) ) continue;

		// If first value in array is not an object, it can't be a class
		if ( ! is_object( $filter[ 'function' ][ 0 ] ) ) continue;

		// Method doesn't match the one we're looking for, goto next
		if ( $filter[ 'function' ][ 1 ] !== $method_name ) continue;

		// Method matched, now let's check the Class
		if ( get_class( $filter[ 'function' ][ 0 ] ) === $class_name ) {

			// WordPress 4.7+ use core remove_filter() since we found the class object
			if( isset( $fob ) ){
				// Handles removing filter, reseting callback priority keys mid-iteration, etc.
				$fob->remove_filter( $tag, $filter['function'], $priority );

			} else {
				// Use legacy removal process (pre 4.7)
				unset( $callbacks[ $priority ][ $filter_id ] );
				// and if it was the only filter in that priority, unset that priority
				if ( empty( $callbacks[ $priority ] ) ) {
					unset( $callbacks[ $priority ] );
				}
				// and if the only filter for that tag, set the tag to an empty array
				if ( empty( $callbacks ) ) {
					$callbacks = array();
				}
				// Remove this filter from merged_filters, which specifies if filters have been sorted
				unset( $GLOBALS['merged_filters'][ $tag ] );
			}

			return TRUE;
		}
	}

	return FALSE;
}

/**
 * Remove Class Action Without Access to Class Object
 *
 * In order to use the core WordPress remove_action() on an action added with the callback
 * to a class, you either have to have access to that class object, or it has to be a call
 * to a static method.  This method allows you to remove actions with a callback to a class
 * you don't have access to.
 *
 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
 *
 * @param string $tag         Action to remove
 * @param string $class_name  Class name for the action's callback
 * @param string $method_name Method name for the action's callback
 * @param int    $priority    Priority of the action (default 10)
 *
 * @return bool               Whether the function is removed.
 */
function wc_gzd_remove_class_action( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
	wc_gzd_remove_class_filter( $tag, $class_name, $method_name, $priority );
}

function wc_gzd_replace_label_shortcodes( $html, $replacements ) {
    foreach( $replacements as $search => $replace ) {
        $html = str_replace( $search, $replace, $html );
    }

    global $shortcode_tags;
    $original_shortcode_tags = $shortcode_tags;
    $shortcode_tags          = array();

    add_shortcode( 'page', '_wc_gzd_page_shortcode' );

    foreach( wc_gzd_get_legal_pages() as $legal_page => $title ) {
        add_shortcode( $legal_page, '_wc_gzd_legal_page_shortcode' );
    }

    $html = do_shortcode( $html );

    $shortcode_tags = $original_shortcode_tags;

    return $html;
}

function _wc_gzd_page_shortcode( $atts, $content = '' ) {
    $atts = wp_parse_args( $atts, array(
        'id'     => 0,
        'target' => '_blank',
        'text'   => '',
        'url'    => '',
    ) );

    if ( ( empty( $atts['id'] ) || ! get_post( $atts['id'] ) ) && empty( $atts['url'] ) ) {
        return false;
    }

    if ( empty( $content ) ) {
        if ( empty( $atts['text'] ) ) {
            $content = get_the_title( $atts['id'] );
        } else {
            $content = $atts['text'];
        }
    }

    $url = ( empty( $atts['url'] ) ? get_permalink( $atts['id'] ) : $atts['url'] );

    return '<a href="' . esc_url( $url ) . '" target="' . esc_attr( $atts['target'] ) . '">' . $content . '</a>';
}

function _wc_gzd_legal_page_shortcode( $atts, $content, $tag ) {
    $atts       = wp_parse_args( $atts, array() );
    $atts['id'] = wc_get_page_id( $tag );

    return _wc_gzd_page_shortcode( $atts, $content );
}

/**
 * Variable Pricing
 */
add_filter( 'woocommerce_format_price_range', 'woocommmerce_gzd_price_range', 10, 3 );

function woocommmerce_gzd_price_range( $price_html, $from, $to ) {

    if ( ! apply_filters( 'woocommerce_gzd_adjust_price_range_format', true ) ) {
        return $price_html;
    }

    $format     = get_option( 'woocommerce_gzd_price_range_format_text', __( '{min_price} &ndash; {max_price}', 'woocommerce-germanized' ) );
    $price_html = str_replace( array( '{min_price}', '{max_price}' ), array( is_numeric( $from ) ? wc_price( $from ) : $from, is_numeric( $to ) ? wc_price( $to ) : $to ), $format );

    return $price_html;
}
