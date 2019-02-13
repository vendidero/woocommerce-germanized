<?php

class WC_GZD_Product_Attribute_Helper {

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
    }

    public function __construct() {
        // Make sure Woo uses our implementation when updating the attributes via AJAX
        add_filter( 'woocommerce_admin_meta_boxes_prepare_attribute', array( $this, 'prepare_attributes_filter' ), 10, 3 );
        // This is the only nice way to update attributes after Woo has updated product attributes
        add_action( 'woocommerce_product_object_updated_props', array( $this, 'update_attributes' ), 10, 2 );
        // Adjust cart item data to include attributes visible during cart/checkout
        add_filter( 'woocommerce_get_item_data', array( $this, 'cart_item_data_filter' ), 150, 2 );

        if ( is_admin() ) {
            add_action( 'woocommerce_after_product_attribute_settings', array( $this, 'attribute_visibility' ), 10, 2 );
        }

        add_filter( 'woocommerce_germanized_settings_display', array( $this, 'global_attribute_setting' ), 10 );
    }

    public function global_attribute_setting( $settings ) {
        foreach( $settings as $key => $setting ) {
            if ( isset( $setting['id'] ) && 'woocommerce_gzd_display_checkout_thumbnails' === $setting['id'] ) {
                array_splice( $settings, $key + 1, 0, array( array(
                    'title' 	=> __( 'Show product attributes', 'woocommerce-germanized' ),
                    'desc' 		=> __( 'List all product attributes during cart and checkout.', 'woocommerce-germanized' ),
                    'id' 		=> 'woocommerce_gzd_display_checkout_product_attributes',
                    'default'	=> 'no',
                    'type' 		=> 'checkbox',
                    'desc_tip'	=> __( 'This option forces WooCommerce to output a list of all product attributes during cart and checkout.', 'woocommerce-germanized' ),
                ) ) );
            }
        }

        return $settings;
    }

    public function attribute_visibility( $attribute, $i ) {
        global $product_object, $product;

        if ( isset( $product_object ) ) {
            $gzd_product = $product_object;
        } elseif( isset( $product ) ) {
            $gzd_product = $product;
        } else {
            $gzd_product = null;
        }

        $gzd_product_attribute = ( is_a( $attribute, 'WC_GZD_Product_Attribute' ) ? $attribute : $this->get_attribute( $attribute, $gzd_product ) );
        ?>
        <tr>
            <td>
                <label><input type="checkbox" class="checkbox" <?php checked( $gzd_product_attribute->is_checkout_visible(), true ); ?> name="attribute_checkout_visibility[<?php echo esc_attr( $i ); ?>]" value="1" /> <?php esc_html_e( 'Visible during checkout', 'woocommerce-germanized' ); ?></label>
            </td>
        </tr>
        <?php
    }

    public function cart_item_data_filter( $item_data, $cart_item ) {
        $cart_product = $cart_item['data'];

        if ( $cart_product->is_type( 'variation' ) ) {
            $item_data = array_merge( $item_data, $this->get_cart_product_variation_attributes( $cart_item, $item_data ) );
        }

        $item_data = array_merge( $this->get_cart_product_attributes( $cart_item, $item_data ), $item_data );

        return $item_data;
    }

    protected function get_attribute_by_variation( $product, $name ) {
        $name = str_replace( 'attribute_', '', $name );

        if ( $parent = wc_get_product( $product->get_parent_id() ) ) {
            foreach( $parent->get_attributes() as $key => $attribute ) {
                if ( $attribute->get_name() === $name ) {
                    return $this->get_attribute( $attribute, $parent );
                }
            }
        }

        return false;
    }

    protected function cart_item_data_exists( $key, $item_data ) {
        foreach( $item_data as $item_data_key => $data ) {
            if ( isset( $data['key'] ) && $key === $data['key'] ) {
                return true;
            }
        }

        return false;
    }

    protected function get_cart_product_variation_attributes( $cart_item, $original_item_data = array() ) {
        $item_data = array();

        if ( $cart_item['data']->is_type( 'variation' ) && is_array( $cart_item['variation'] ) ) {
            foreach ( $cart_item['variation'] as $name => $value ) {
                $taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

                // Lets try to find the original (parent) attribute based on attribute name
                if ( $attribute = $this->get_attribute_by_variation( $cart_item['data'], $name ) ) {
                    // If the attribute is not visible and displaying is not forced - skip
                    if ( ! $attribute->is_checkout_visible() && 'yes' !== get_option( 'woocommerce_gzd_display_checkout_product_attributes' ) ) {
                        continue;
                    }
                }

                if ( taxonomy_exists( $taxonomy ) ) {
                    // If this is a term slug, get the term's nice name.
                    $term = get_term_by( 'slug', $value, $taxonomy );
                    if ( ! is_wp_error( $term ) && $term && $term->name ) {
                        $value = $term->name;
                    }
                    $label = wc_attribute_label( $taxonomy );
                } else {
                    // If this is a custom option slug, get the options name.
                    $value = apply_filters( 'woocommerce_variation_option_name', $value );
                    $label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $cart_item['data'] );
                }

                if ( '' === $value ) {
                    continue;
                }

                if ( $this->cart_item_data_exists( $label, $original_item_data ) ) {
                    continue;
                }

                $item_data[] = array(
                    'key'   => $label,
                    'value' => $value,
                );
            }
        }

        return $item_data;
    }

    protected function get_cart_product_attributes( $cart_item, $original_item_data = array() ) {
        $item_data    = array();
        $org_product  = $cart_item['data'];
        $product      = $org_product;

        if ( $product->is_type( 'variation' ) ) {
            $product = wc_get_product( $product->get_parent_id() );
        }

        if ( ! $product ) {
            return $item_data;
        }

        foreach( $product->get_attributes() as $attribute ) {
            $attribute = $this->get_attribute( $attribute, $product );

            if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_product_attributes' ) || $attribute->is_checkout_visible() ) {
                $values = array();

                // Make sure to exclude variation specific attributes (which were already added by variation data).
                if ( $org_product->is_type( 'variation' ) && $attribute->get_variation() ) {
                    continue;
                }

                if ( $attribute->is_taxonomy() ) {
                    $attribute_taxonomy = $attribute->get_taxonomy_object();
                    $attribute_values   = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

                    foreach ( $attribute_values as $attribute_value ) {
                        $value_name = esc_html( $attribute_value->name );

                        if ( apply_filters( 'woocommerce_gzd_product_attribute_checkout_clickable', false ) && $attribute_taxonomy->attribute_public ) {
                            $values[] = '<a href="' . esc_url( get_term_link( $attribute_value->term_id, $attribute->get_name() ) ) . '" rel="tag">' . $value_name . '</a>';
                        } else {
                            $values[] = $value_name;
                        }
                    }
                } else {
                    $values = $attribute->get_options();

                    foreach ( $values as &$value ) {
                        $value = make_clickable( esc_html( $value ) );
                    }
                }

                $label = wc_attribute_label( $attribute->get_name() );

                if ( $this->cart_item_data_exists( $label, $original_item_data ) ) {
                    continue;
                }

                $item_data[] = array(
                    'key'   => $label,
                    'value' => apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute->get_attribute(), $values )
                );
            }
        }

        return $item_data;
    }

    public function update_attributes( $product, $updated_props ) {
        $attributes = $product->get_attributes();
        $meta       = get_post_meta( $product->get_id(), '_product_attributes', true );

        if ( $meta && is_array( $meta ) ) {
            foreach ( $meta as $meta_key => $meta_attribute ) {
                if ( isset( $attributes[ $meta_key ] ) ) {
                    $attribute = $attributes[ $meta_key ];

                    if ( is_a( $attribute, 'WC_GZD_Product_Attribute' ) ) {
                        $meta[ $meta_key ]['checkout_visible'] = $attribute->get_checkout_visible() ? 1 : 0;
                    }
                }
            }

            update_post_meta( $product->get_id(), '_product_attributes', $meta );
        }
    }

    public function prepare_attributes_filter( $attribute, $data, $i ) {
        $attribute_checkout_visibility = isset( $data['attribute_checkout_visibility'] ) ? $data['attribute_checkout_visibility'] : array();

        $attribute = new WC_GZD_Product_Attribute( $attribute );
        $attribute->set_checkout_visible( isset( $attribute_checkout_visibility[ $i ] ) );

        return $attribute;
    }

    public function get_attribute( $attribute, $product_id = false ) {
        $new_attribute   = new WC_GZD_Product_Attribute( $attribute );
        $product_id      = ( $product_id && ! is_numeric( $product_id ) ? wc_gzd_get_crud_data( $product_id, 'id' ) : $product_id );
        $meta_attributes = $product_id ? get_post_meta( $product_id, '_product_attributes', true ) : array();
        $meta_key        = $attribute->get_name();

        if ( ! empty( $meta_attributes ) && is_array( $meta_attributes ) ) {
            if ( isset( $meta_attributes[ $meta_key ] ) ) {
                $meta_value = array_merge(
                    array(
                        'checkout_visible' => apply_filters( 'woocommerce_gzd_product_attribute_checkout_visible_default_value', false ),
                    ),
                    (array) $meta_attributes[ $meta_key ]
                );

                if ( ! is_null( $meta_value['checkout_visible'] ) ) {
                    $new_attribute->set_checkout_visible( $meta_value['checkout_visible'] );
                }
            }
        }

        return $new_attribute;
    }
}

WC_GZD_Product_Attribute_Helper::instance();