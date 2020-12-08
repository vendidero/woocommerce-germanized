<?php

class WC_Trusted_Shops_Schedule {

    public $base = null;

    protected static $_instance = null;

    public static function instance( $base ) {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self( $base );

        return self::$_instance;
    }

    private function __construct( $base ) {
        $this->base = $base;

        add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'update_reviews' ) );
        add_action( 'admin_init', array( $this, 'update_default_reviews' ), 10 );
        add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'send_mails' ) );
    }

    public function update_default_reviews() {
        // Generate reviews for the first time
        $option_key = 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_reviews_cache';

        $section = isset( $_GET['section'] ) ? wc_clean( $_GET['section'] ) : '';

        // Do only update default reviews if the admin user open the settings page.
        if ( 'trusted_shops' !== $section ) {
            return;
        }

        if ( ! get_option( $option_key, false ) ) {
            $this->_update_reviews();
        }

        if ( $this->base->is_multi_language_setup() ) {
            $compatibility    = $this->base->get_multi_language_compatibility();
            $current_language = $compatibility->get_current_language();

            global $wc_ts_original_lang;

            $wc_ts_original_lang = $current_language;

            foreach( $compatibility->get_languages() as $language ) {

                if ( $compatibility->get_default_language() == $language ) {
                    continue;
                }

                $option_key .= '_' . $language;

                if ( ! get_option( $option_key, false ) ) {
                    $this->_update_reviews( $language );
                }
            }
        }
    }

    /**
     * Update Review Cache by grabbing information from xml file
     */
    public function update_reviews() {
        $this->_update_reviews();

        if ( $this->base->is_multi_language_setup() ) {

            $compatibility    = $this->base->get_multi_language_compatibility();
            $current_language = $compatibility->get_current_language();

            global $wc_ts_original_lang;

            $wc_ts_original_lang = $current_language;

            foreach( $compatibility->get_languages() as $language ) {
                if ( $compatibility->get_default_language() == $language ) {
                    continue;
                }

                $this->_update_reviews( $language );
            }
        }
    }

    protected function _update_reviews( $lang = '' ) {
        if ( ! empty( $lang ) ) {
            wc_ts_switch_language( $lang );
        }

        if ( ! $this->base->is_rich_snippets_enabled() ) {
            if ( ! empty( $lang ) ) {
                wc_ts_restore_language();
            }

            return;
        }

        $update = array();

        if ( $this->base->is_enabled() ) {

            $response = wp_remote_post( $this->base->api_url );

            if ( is_array( $response ) ) {
                $output          = json_decode( $response['body'], true );

                if ( isset( $output['response']['data'] ) ) {
                    $reviews         = $output['response']['data']['shop']['qualityIndicators']['reviewIndicator'];
                    $update['count'] = (string) $reviews['activeReviewCount'];
                    $update['avg']   = (float) $reviews['overallMark'];
                    $update['max']   = '5.00';
                }
            }
        }

        $option_key = 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_reviews_cache';

        if ( ! empty( $lang ) ) {
            $option_key .= '_' . $lang;
        }

        update_option( $option_key, $update );

        if ( ! empty( $lang ) ) {
            wc_ts_restore_language();
        }
    }

    /**
     * Placeholder to avoid fatal errors within scheduled actions.
     *
     * @deprecated 2.2.5
     */
    public function update_review_widget() {}

    /**
     * Send review reminder mails after x days
     */
    public function send_mails() {
        if ( $this->base->is_multi_language_setup() ) {
            $compatibility    = $this->base->get_multi_language_compatibility();
            $current_language = $compatibility->get_current_language();

            global $wc_ts_original_lang;

            $wc_ts_original_lang = $current_language;

            foreach ( $compatibility->get_languages() as $language ) {
                $this->_send_mails( $language );
            }

        } else {
            $this->_send_mails();
        }
    }

    protected function _send_mails( $lang = '' ) {
        if ( ! empty( $lang ) ) {
            wc_ts_switch_language( $lang );
        }

        if ( ! $this->base->is_review_reminder_enabled() ) {
            if ( ! empty( $lang ) ) {
                wc_ts_restore_language();
            }

            return;
        }

	    $order_statuses = $this->base->review_reminder_status;

	    if ( ! is_array( $order_statuses ) ) {
		    $order_statuses = array( $order_statuses );
	    }

        $args = array(
            'post_type'   => 'shop_order',
            'post_status' => apply_filters( 'woocommerce_trusted_shops_review_reminder_valid_order_statuses', $order_statuses ),
            'showposts'   => -1,
            'meta_query'  => array(
                'relation'        => 'AND',
                'is_sent'         => array(
                    'key'         => '_trusted_shops_review_mail_sent',
                    'compare'     => 'NOT EXISTS',
                ),
                'opted_in'        => array(
                    'key'         => '_ts_review_reminder_opted_in',
                    'compare'     => '=',
                    'value'       => 'yes'
                ),
            ),
        );

        if ( ! empty( $lang ) ) {
            $args['meta_query']['wpml'] = array(
                'key'     => 'wpml_language',
                'compare' => '=',
                'value'   => $lang,
            );
        }

        $order_query = new WP_Query( apply_filters( 'woocommerce_trusted_shops_review_reminder_order_args', $args, $lang ) );

        while ( $order_query->have_posts() ) {

            $order_query->next_post();

            if ( ! $order = wc_get_order( $order_query->post->ID ) ) {
            	continue;
            }

            $completed_date = apply_filters( 'woocommerce_trusted_shops_review_reminder_order_completed_date', $order->get_date_completed(), $order );

            if ( ! $completed_date ) {
            	continue;
            }

            $now            = new DateTime();
	        $diff           = $now->diff( $completed_date );
            $min_days       = (int) $this->base->review_reminder_days;

            if ( $diff->days >= $min_days ) {

                if ( apply_filters( 'woocommerce_trusted_shops_send_review_reminder_email', true, $order ) ) {

	                $mails = WC()->mailer()->get_emails();

	                foreach ( $mails as $mail ) {

	                	if ( 'customer_trusted_shops' === $mail->id ) {
			                $mail->trigger( wc_ts_get_crud_data( $order, 'id' ) );

			                update_post_meta( wc_ts_get_crud_data( $order, 'id' ), '_trusted_shops_review_mail_sent', 1 );
		                }
	                }
                }
            }
        }

        if ( ! empty( $lang ) ) {
            wc_ts_restore_language();
        }
    }
}
