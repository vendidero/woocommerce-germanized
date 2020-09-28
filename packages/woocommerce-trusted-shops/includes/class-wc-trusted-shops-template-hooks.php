<?php

class WC_Trusted_Shops_Template_Hooks {

    protected static $_instance = null;

    public $base = null;

    public static function instance( $base ) {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self( $base );

        return self::$_instance;
    }

    private function __construct( $base ) {
        $this->base = $base;

        // Load hooks on init so that language-specific settings are loaded
        add_action( 'init', array( $this, 'init' ), 10 );

        // Always register checkbox to avoid language problems
        add_action( 'woocommerce_gzd_register_legal_core_checkboxes', array( $this, 'review_reminder_checkbox' ), 30 );
    }

    public function init() {
        if ( $this->base->is_enabled() ) {
            add_action( 'woocommerce_thankyou', array( $this, 'template_thankyou' ), 10, 1 );
            add_action( 'wp_footer', array( $this, 'template_trustbadge' ), 250 );
        }

        if ( $this->base->product_reviews_visible() ) {
            add_filter( 'woocommerce_product_tabs', array( $this, 'remove_review_tab' ), 40, 1 );
        }

        if ( $this->base->is_product_sticker_enabled() ) {
            add_filter( 'woocommerce_product_tabs', array( $this, 'review_tab' ), 50, 1 );
        }

        if ( $this->base->is_product_widget_enabled() ) {
            add_filter( 'woocommerce_trusted_shops_template_name', array( $this, 'set_product_widget_template' ), 50, 1 );
        }

        if ( $this->base->is_rich_snippets_enabled() ) {
            add_action( 'wp_footer', array( $this, 'insert_rich_snippets' ), 20 );
        }

        // Save Fields on order
        if ( $this->base->is_review_reminder_enabled() ) {
            add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );

            if ( 'yes' === $this->base->review_reminder_opt_out ) {
                // Email notices right beneath order table
                add_action( 'woocommerce_email_after_order_table', array( $this, 'email_cancel_review_reminder' ), 8, 3 );
                add_filter( 'woocommerce_email_styles', array( $this, 'email_styles' ) );

                // Check for customer activation
                add_action( 'template_redirect', array( $this, 'cancel_review_reminder_check' ) );
            }
        }
    }

    public function insert_rich_snippets() {
        $insert = false;

        if ( in_array( 'category', $this->base->get_rich_snippets_locations() ) ) {
            if ( is_product_category() ) {
                $insert = true;
            }
        }

        if ( in_array( 'home', $this->base->get_rich_snippets_locations() ) ) {
            if ( function_exists( 'is_shop' ) && is_shop() ) {
                $insert = true;
            }
        }

        if ( in_array( 'product', $this->base->get_rich_snippets_locations() ) ) {
            if ( is_product() ) {
                $insert = true;
            }
        }

        if ( $insert ) {
            echo do_shortcode( '[trusted_shops_rich_snippets]' );
        }
    }

    public function cancel_review_reminder_check() {
        if ( isset( $_GET['disable-review-reminder'] ) && isset( $_GET['order-id'] ) ) {

            $order_id = absint( $_GET['order-id'] );
            $code     = wc_clean( $_GET['disable-review-reminder'] );

            if ( ! empty( $code ) && ! empty( $order_id ) ) {

                $order_query = new WP_Query( array(
                    'post_type'      => 'shop_order',
                    'p'              => $order_id,
                    'post_status'    => array_keys( wc_get_order_statuses() ),
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        'code'            => array(
                            'key'         => '_ts_cancel_review_reminder_code',
                            'compare'     => '=',
                            'value'       => $code,
                        ),
                    ),
                ) );

                while ( $order_query->have_posts() ) {
                    $order_query->next_post();
                    $order = wc_get_order( $order_query->post->ID );

                    if ( $order ) {
                        $order_id = wc_ts_get_crud_data( $order, 'id' );

                        delete_post_meta( $order_id, '_ts_cancel_review_reminder_code' );
                        delete_post_meta( $order_id, '_ts_review_reminder_opted_in' );

                        wp_die( sprintf( _x( 'Your review reminder e-mail has been cancelled successfully. Return to %s.', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . get_site_url() . '">' . _x( 'Home', 'trusted-shops', 'woocommerce-germanized' ) . '</a>' ) );
                    }
                }
            }
        }
    }

    public function email_styles( $css ) {
        $css .= '
			.wc-ts-cancel-review-reminder {
				margin-top: 16px;
			}
		';

        return $css;
    }

    public function get_cancel_review_reminder_link( $order ) {
        $code = wc_ts_get_crud_data( $order, 'ts_cancel_review_reminder_code' );

        if ( ! $code || empty( $code ) ) {
            global $wp_hasher;

            if ( empty( $wp_hasher ) ) {
                require_once ABSPATH . WPINC . '/class-phpass.php';
                $wp_hasher = new PasswordHash( 8, true );
            }

            $code = $wp_hasher->HashPassword( wp_generate_password( 20 ) );

            update_post_meta( wc_ts_get_crud_data( $order, 'id' ), '_ts_cancel_review_reminder_code', $code );
        }

        $order_id = wc_ts_get_crud_data( $order, 'id' );
        $link     = add_query_arg( array( 'disable-review-reminder' => $code, 'order-id' => $order_id ), get_site_url() );

        if ( $lang = wc_ts_get_order_language( $order ) ) {
            $link = add_query_arg( array( 'lang' => $lang ), $link );
        }

        return apply_filters( 'woocommerce_trusted_shops_cancel_review_reminder_link', $link, $code, $order );
    }

    public function email_cancel_review_reminder( $order, $sent_to_admin, $plain_text ) {
        $type = WC_germanized()->emails->get_current_email_object();

        // Try to flush the cache before continuing
        WC_GZD_Cache_Helper::maybe_flush_cache( 'db', array( 'cache_type' => 'meta', 'meta_type' => 'post', 'meta_key' => 'ts_review_reminder_opted_in' ) );
        $opted_in = wc_ts_get_crud_data( $order, 'ts_review_reminder_opted_in' );

        if ( $type && 'yes' === $opted_in && 'customer_processing_order' === $type->id ) {
            wc_get_template( 'emails/cancel-review-reminder.php', array( 'link' => $this->get_cancel_review_reminder_link( $order ) ) );
        }
    }

    public function update_order_meta( $order_id ) {
        $checkbox = wc_gzd_get_legal_checkbox( 'review_reminder' );

        if ( isset( $_POST['review_reminder'] ) || ! $checkbox || ( $checkbox && ! $checkbox->is_enabled() ) ) {
            update_post_meta( $order_id, '_ts_review_reminder_opted_in', 'yes' );
        }
    }

    public function review_reminder_checkbox() {
        if ( ! function_exists( 'wc_gzd_register_legal_checkbox' ) ) {
            return;
        }

        wc_gzd_register_legal_checkbox( 'review_reminder', array(
            'html_id'              => 'review-reminder',
            'html_name'            => 'review_reminder',
            'html_wrapper_classes' => array( 'legal' ),
            'label'                =>  _x( 'Yes, I would like to be reminded via e-mail after {days} day(s) to review my order. I am able to cancel the reminder at any time by clicking on the "cancel review reminder" link within the order confirmation.', 'trusted-shops', 'woocommerce-germanized' ),
            'label_args'           => array( '{days}' => $this->base->review_reminder_days ),
            'hide_input'           => false,
            'is_enabled'           => false,
            'is_mandatory'         => false,
            'error_message'        => _x( 'Please allow us to send a review reminder by e-mail.', 'trusted-shops', 'woocommerce-germanized' ),
            'priority'             => 6,
            'is_core'              => true,
            'admin_name'           => _x( 'Review reminder', 'trusted-shops', 'woocommerce-germanized' ),
            'admin_desc'           => _x( 'Asks the customer to receive a Trusted Shops review reminder.', 'trusted-shops', 'woocommerce-germanized' ),
            'locations'            => array( 'checkout' ),
        ) );
    }

    public function set_product_widget_template( $template ) {

    	if ( in_array( $template, array( 'single-product/rating.php' ) ) ) {
            $template = 'trusted-shops/product-widget.php';
        }

        return $template;
    }

    public function remove_review_tab( $tabs ) {
        if ( isset( $tabs['reviews'] ) )
            unset( $tabs['reviews'] );

        return $tabs;
    }

    public function review_tab( $tabs ) {
        $tabs['trusted_shops_reviews'] = array(
            'title'    => $this->base->product_sticker_tab_text,
            'priority' => 30,
            'callback' => array( $this, 'template_product_sticker' ),
        );
        return $tabs;
    }

    public function template_review_sticker( $template ) {
        wc_get_template( 'trusted-shops/review-sticker.php', array( 'plugin' => $this->base, 'element' => '#ts_review_sticker' ) );
    }

    public function template_product_sticker( $template ) {
        wc_get_template( 'trusted-shops/product-sticker.php', array( 'plugin' => $this->base ) );
    }

    public function template_trustbadge() {
        wc_get_template( 'trusted-shops/trustbadge.php', array( 'plugin' => $this->base ) );
    }

    public function template_thankyou( $order_id ) {
        wc_get_template( 'trusted-shops/thankyou.php', array(
            'order_id'        => $order_id,
            'plugin'          => $this->base,
        ) );
    }

}
