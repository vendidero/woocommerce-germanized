<?php
/**
 * Legacy Functions
 *
 * WC_GZD legacy functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wc_gzd_get_crud_data( $object, $key ) {

	if ( is_a( $object, 'WC_GZD_Product' ) ) {
		$object = $object->get_wc_product();
	}

	$value = null;

	$getter = substr( $key, 0, 3 ) === "get" ? $key : "get_$key";
	$key = substr( $key, 0, 3 ) === "get" ? substr( $key, 3 ) : $key;

	if ( 'id' === $key && is_callable( array( $object, 'is_type' ) ) && $object->is_type( 'variation' ) && ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
		$key = 'variation_id';
	} elseif ( 'parent' === $key && is_callable( array( $object, 'is_type' ) ) && $object->is_type( 'variation' ) && ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
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
	} elseif ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
		if ( substr( $key, 0, 1 ) !== '_' )
			$key = '_' . $key;

		$value = $object->get_meta( $key );
	} else {
		$key = substr( $key, 0, 1 ) === "_" ? substr( $key, 1 ) : $key;
		$value = $object->{$key};
	}

	return $value;
}

function wc_gzd_set_crud_meta_data( $object, $key, $value ) {
	if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
		$object->update_meta_data( $key, $value );
	} else {
		update_post_meta( wc_gzd_get_crud_data( $object, 'id' ), $key, $value );
	}
	return $object;
}

function wc_gzd_unset_crud_meta_data( $object, $key ) {
	if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
		$object->delete_meta_data( $key );
	} else {
		delete_post_meta( wc_gzd_get_crud_data( $object, 'id' ), $key );
	}
	return $object;
}

function wc_gzd_set_crud_term_data( $object, $term, $taxonomy ) {

	$term_data = ( ! is_numeric( $term ) ? sanitize_text_field( $term ) : absint( $term ) );

	if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
		$object->update_meta_data( '_' . $taxonomy, $term );
	} else {
		wp_set_object_terms( wc_gzd_get_crud_data( $object, 'id' ), $term_data, $taxonomy );
	}

	return $object;
}

function wc_gzd_unset_crud_term_data( $object, $taxonomy ) {
	if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
		$object->update_meta_data( '_delete_' . $taxonomy, true );
	} else {
		wp_delete_object_term_relationships( wc_gzd_get_crud_data( $object, 'id' ), $taxonomy );
	}

	return $object;
}

function wc_gzd_get_variable_visible_children( $product ) {
	if ( is_callable( array( $product, 'get_visible_children' ) ) )
		return $product->get_visible_children();
	return $product->get_children( true );
}

function wc_gzd_get_price_including_tax( $product, $args = array() ) {
	if ( function_exists( 'wc_get_price_including_tax' ) )
		return wc_get_price_including_tax( $product, $args );
	return $product->get_price_including_tax( $args[ 'qty' ], $args[ 'price' ] );
}

function wc_gzd_get_price_excluding_tax( $product, $args = array() ) {
	if ( function_exists( 'wc_get_price_excluding_tax' ) )
		return wc_get_price_excluding_tax( $product, $args );
	return $product->get_price_excluding_tax( $args[ 'qty' ], $args[ 'price' ] );
}

function wc_gzd_get_variation( $parent, $variation ) {
	if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() )
		return wc_get_product( $variation );
	return $parent->get_child( $variation );
}

function wc_gzd_get_order_currency( $order ) {
	if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() )
		return $order->get_currency();
	return $order->get_order_currency();
}

function wc_gzd_reduce_order_stock( $order_id ) {
    if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
        wc_reduce_stock_levels($order_id);
    } else {
        $order = wc_get_order( $order_id );
        $order->reduce_order_stock();
    }
}