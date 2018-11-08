<?php

class WC_GZD_Trusted_Shops_Schedule {

	public $base = null;

	protected static $_instance = null;

	public static function instance( $base ) {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self( $base );
		return self::$_instance;
	}

	private function __construct( $base ) {
		$this->base = $base;

		if ( $this->base->is_rich_snippets_enabled() ) {
			
			add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'update_reviews' ) );
			$reviews = $this->base->reviews_cache;

			// Generate reviews for the first time
			if ( empty( $reviews ) )
				add_action( 'init', array( $this, 'update_reviews' ) );
		}

		if ( $this->base->is_review_reminder_enabled() )
			add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'send_mails' ) );
	}

	/**
	 * Update Review Cache by grabbing information from xml file
	 */
	public function update_reviews() {

		$update = array();

		if ( $this->base->is_enabled() ) {

			$response = wp_remote_post( $this->base->api_url );

			if ( is_array( $response ) ) {
				$output          = json_decode( $response['body'], true );
				$reviews         = $output['response']['data']['shop']['qualityIndicators']['reviewIndicator'];
				$update['count'] = (string) $reviews['activeReviewCount'];
				$update['avg']   = (float) $reviews['overallMark'];
				$update['max']   = '5.00';
			}
		}

		update_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_reviews_cache', $update );
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
		
		$order_query = new WP_Query(
			array( 
				'post_type'   => 'shop_order', 
				'post_status' => apply_filters( 'woocommerce_trusted_shops_review_reminder_valid_order_statuses', array( $this->base->review_reminder_status ) ),
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
			)
		);

		while ( $order_query->have_posts() ) {

			$order_query->next_post();
			$order          = wc_get_order( $order_query->post->ID );
			$completed_date = apply_filters( 'woocommerce_trusted_shops_review_reminder_order_completed_date', wc_ts_get_crud_data( $order, 'completed_date' ), $order );
			$diff           = $this->base->plugin->get_date_diff( $completed_date, date( 'Y-m-d H:i:s' ) );

			if ( $diff['d'] >= (int) $this->base->review_reminder_days ) {

				if ( apply_filters( 'woocommerce_trusted_shops_send_review_reminder_email', true, $order ) ) {
					if ( $mail = $this->base->plugin->emails->get_email_instance_by_id( 'customer_trusted_shops' ) ) {
						$mail->trigger( wc_ts_get_crud_data( $order, 'id' ) );
					}
				}

				update_post_meta( wc_ts_get_crud_data( $order, 'id' ), '_trusted_shops_review_mail_sent', 1 );
			}
		}
	}
}