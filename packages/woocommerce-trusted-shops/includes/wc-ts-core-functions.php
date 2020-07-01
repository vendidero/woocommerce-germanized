<?php
/**
 * Core Functions
 *
 * WC_GZD_TS core functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'wc_ts_get_crud_data' ) ) {

    function wc_ts_get_crud_data( $object, $key, $suppress_suffix = false ) {
        if ( is_a( $object, 'WC_GZD_Product' ) ) {
            $object = $object->get_wc_product();
        }

        $value = null;

        $getter = substr( $key, 0, 3 ) === "get" ? $key : "get_$key";
        $key = substr( $key, 0, 3 ) === "get" ? substr( $key, 3 ) : $key;

        if ( 'id' === $key && is_callable( array( $object, 'is_type' ) ) && $object->is_type( 'variation' ) && ! wc_ts_woocommerce_supports_crud() ) {
            $key = 'variation_id';
        } elseif ( 'parent' === $key && is_callable( array( $object, 'is_type' ) ) && $object->is_type( 'variation' ) && ! wc_ts_woocommerce_supports_crud() ) {
            // Set getter to parent so that it is not being used for pre 2.7
            $key = 'id';
            $getter = 'parent';
        }

        $getter_mapping = array(
            'parent' => 'get_parent_id',
            'completed_date' => 'get_date_completed',
            'order_date' => 'get_date_created',
            'product_type' => 'get_type',
            'order_type' => 'get_type',
        );

        if ( array_key_exists( $key, $getter_mapping ) ) {
            $getter = $getter_mapping[ $key ];
        }

        if ( is_callable( array( $object, $getter ) ) ) {
            $reflection = new ReflectionMethod( $object, $getter );
            if ( $reflection->isPublic() ) {
                $value = $object->{$getter}();
            }
        } elseif ( wc_ts_woocommerce_supports_crud() ) {
            // Prefix meta if suppress_suffix is not set
            if ( substr( $key, 0, 1 ) !== '_' && ! $suppress_suffix )
                $key = '_' . $key;

            $value = $object->get_meta( $key );
        } else {
            $key = substr( $key, 0, 1 ) === "_" ? substr( $key, 1 ) : $key;
            $value = $object->{$key};
        }

        return $value;
    }
}

if ( ! function_exists( 'wc_ts_woocommerce_supports_crud' ) ) {

    function wc_ts_woocommerce_supports_crud() {
        return WC_TS_Dependencies::instance()->woocommerce_version_supports_crud();
    }
}

if ( ! function_exists( 'wc_ts_help_tip' ) ) {

    function wc_ts_help_tip( $tip, $allow_html = false ) {
        if ( function_exists( 'wc_help_tip' ) )
            return wc_help_tip( $tip, $allow_html );

        return '<a class="tips" data-tip="' . ( $allow_html ? esc_html( $tip ) : $tip ) . '" href="#">[?]</a>';
    }
}

if ( ! function_exists( 'wc_ts_set_crud_data' ) ) {

    function wc_ts_set_crud_data( $object, $key, $value ) {
        if ( wc_ts_woocommerce_supports_crud() ) {

            $key_unprefixed = substr( $key, 0, 1 ) === '_' ? substr( $key, 1 ) : $key;
            $setter = substr( $key_unprefixed, 0, 3 ) === "set" ? $key : "set_{$key_unprefixed}";

            if ( is_callable( array( $object, $setter ) ) ) {
                $reflection = new ReflectionMethod( $object, $setter );
                if ( $reflection->isPublic() ) {
                    $object->{$setter}( $value );
                }
            } else {
                $object = wc_ts_set_crud_meta_data( $object, $key, $value );
            }
        } else {
            $object = wc_ts_set_crud_meta_data( $object, $key, $value );
        }
        return $object;
    }
}

if ( ! function_exists( 'wc_ts_set_crud_meta_data' ) ) {
    function wc_ts_set_crud_meta_data( $object, $key, $value ) {

        if ( wc_ts_woocommerce_supports_crud() ) {
            $object->update_meta_data( $key, $value );
        } else {
            update_post_meta( wc_ts_get_crud_data( $object, 'id' ), $key, $value );
        }
        return $object;
    }
}

if ( ! function_exists( 'wc_ts_get_order_date' ) ) {

    function wc_ts_get_order_date( $order, $format = '' ) {
        $date_formatted = '';

        if ( function_exists( 'wc_format_datetime' ) ) {
            return wc_format_datetime( $order->get_date_created(), $format );
        } else {
            $date = $order->order_date;
        }

        if ( empty( $format ) ) {
            $format = get_option( 'date_format' );
        }

        if ( ! empty( $date ) ) {
            $date_formatted = date_i18n( $format, strtotime( $date ) );
        }

        return $date_formatted;
    }
}

if ( ! function_exists( 'wc_ts_get_order_currency' ) ) {

    function wc_ts_get_order_currency( $order ) {
        if ( wc_ts_woocommerce_supports_crud() ) {
            return $order->get_currency();
        }

        return $order->get_order_currency();
    }
}

if ( ! function_exists( 'wc_ts_get_order_language' ) ) {

    function wc_ts_get_order_language( $order ) {
        $order_id = is_numeric( $order ) ? $order : wc_ts_get_crud_data( $order, 'id' );

        return get_post_meta( $order_id, 'wpml_language', true );
    }
}

if ( ! function_exists( 'wc_ts_switch_language' ) ) {

    function wc_ts_switch_language( $lang, $set_default = false ) {
        global $sitepress;
        global $wc_ts_original_lang;

        if ( $set_default ) {
            $wc_ts_original_lang = $lang;
        }

        if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_current_language' ) ) && is_callable( array( $sitepress, 'switch_lang' ) ) ) {
            if ( $sitepress->get_current_language() != $lang ) {

                $sitepress->switch_lang( $lang, true );

                // Somehow WPML doesn't automatically change the locale
                if ( is_callable( array( $sitepress, 'reset_locale_utils_cache' ) ) ) {
                    $sitepress->reset_locale_utils_cache();
                }

                if ( function_exists( 'switch_to_locale' ) ) {
                    switch_to_locale( get_locale() );

                    // Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
                    add_filter( 'plugin_locale', 'get_locale' );

                    // Init WC locale.
                    WC()->load_plugin_textdomain();
                    WC_trusted_shops()->load_plugin_textdomain();
                    WC_trusted_shops()->trusted_shops->refresh();
                }

                do_action( 'woocommerce_gzd_trusted_shops_switched_language', $lang, $wc_ts_original_lang );
            }
        }

        do_action( 'woocommerce_gzd_trusted_shops_switch_language', $lang, $wc_ts_original_lang );
    }
}

if ( ! function_exists( 'wc_ts_restore_language' ) ) {

    function wc_ts_restore_language() {
        global $wc_ts_original_lang;

        if ( isset( $wc_ts_original_lang ) && ! empty( $wc_ts_original_lang ) ) {
            wc_ts_switch_language( $wc_ts_original_lang );
        }
    }
}

if ( ! function_exists( 'wc_ts_remove_class_filter' ) ) {
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
    function wc_ts_remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
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
}

if ( ! function_exists( 'wc_ts_remove_class_action' ) ) {
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
    function wc_ts_remove_class_action( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
        wc_ts_remove_class_filter( $tag, $class_name, $method_name, $priority );
    }
}
