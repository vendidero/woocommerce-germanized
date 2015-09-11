<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * eKomi Implementation - This Class adds eKomi Rating functionality. Hooks into schedules to update reviews and handles
 * Email notifications containing the eKomi review link.
 *
 * @class   WC_GZD_Ekomi
 * @version  1.0.0
 * @author   Vendidero
 */
class WC_GZD_Ekomi {

	/**
	 * API Version
	 *
	 * @var string
	 */
	public $version;
	/**
	 * Shop ID
	 *
	 * @var integer
	 */
	public $id;
	/**
	 * A user is needed to add the eKomi Reviews
	 *
	 * @var object
	 */
	public $user;

	/**
	 * Creates a new User if the eKomi user does not already exist. Adds hooks to schedules to manage review updates and Email notifications
	 */
	public function __construct() {
		$this->version = 'cust-1.0.0';
		$this->id = $this->shop_id;
		if ( $this->is_enabled() )
			add_action( 'init', array( $this, 'init' ) );
		// Register sections
		add_filter( 'woocommerce_gzd_settings_sections', array( $this, 'register_section' ), 2 );
		add_filter( 'woocommerce_gzd_get_settings_ekomi', array( $this, 'get_settings' ) );
	}

	public function init() {
		if ( ! username_exists( 'ekomi' ) ) {
			wp_create_user( __( 'eKomi Customer', 'woocommerce-germanized' ), wp_generate_password(), 'ekomi@loremipsumdolorom.com' );
			$this->user = get_user_by( 'email', 'ekomi@loremipsumdolorom.com' );
			wp_update_user( array( 'ID' => $this->user->ID, 'role' => 'customer' ) );
		}
		// Cronjobs & Hooks
		$this->user = get_user_by( 'email', 'ekomi@loremipsumdolorom.com' );
		add_action( 'woocommerce_gzd_ekomi', array( $this, 'put_products' ), 5 );
		add_action( 'woocommerce_gzd_ekomi', array( $this, 'send_mails' ), 10 );
		add_action( 'woocommerce_gzd_ekomi', array( $this, 'get_reviews' ), 15 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'put_order' ), 0, 1 );
		add_action( 'wp_footer', array( $this, 'add_scripts' ), 10 );
		// add_action( 'admin_init', array( $this, 'get_reviews' ) );
	}

	/**
	 * Gets eKomi Options
	 *
	 * @param string  $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return get_option( 'woocommerce_gzd_ekomi_' . $key );
	}

	/**
	 * Checks whether a certain eKomi Option isset
	 *
	 * @param string  $key
	 * @return boolean
	 */
	public function __isset( $key ) {
		return ( ! get_option( 'woocommerce_gzd_ekomi_' . $key ) ) ? false : true;
	}

	/**
	 * Checks whether eKomi API is enabled
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return ( empty( $this->id ) || empty( $this->interface_id ) || empty( $this->interface_password ) || empty( $this->certificate_link ) || empty( $this->partner_id ) ) ? false : true;
	}

	/**
	 * Transfers all Shop Products including IDs and Titles to eKomi
	 */
	public function put_products() {
		$posts = get_posts( array( 'post_type' => array( 'product' ), 'post_status' => 'publish', 'showposts' => -1 ) );
		if ( !empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$product = get_product( $post );
				if ( $product->is_type( 'variable' ) ) {
					$variations = $product->get_available_variations();
					if ( !empty( $variations ) ) {
						foreach ( $variations as $variation ) {
							$this->put_product( get_product( $variation['variation_id'] ) );
						}
					}
				} elseif ( $product->is_type( 'simple' ) ) {
					$this->put_product( $product );
				}
			}
		}
	}

	/**
	 * Transfers a single Product to eKomi
	 *
	 * @param object  $product
	 */
	public function put_product( $product ) {
		$response = unserialize( file_get_contents(
			'https://api.ekomi.de/v2/putProduct?auth=' . $this->interface_id . '|' . $this->interface_password . '&version=' . $this->version . '&product_id=' . urlencode( esc_attr( $this->get_product_id( $product ) ) ) . '&product_name=' . urlencode( esc_attr( $this->get_product_name( $product ) ) )
		) );
		return ( $response['done'] == 1 ) ? true : false;
	}

	/**
	 * Returns the product id. If is variation returns variation id instead.
	 *  
	 * @param  object $product 
	 * @return integer          
	 */
	public function get_product_id( $product ) {
		return ( isset( $product->variation_id ) ? $product->variation_id : $product->id );
	}

	/**
	 * Gets the Product's name based on it's type
	 *
	 * @param object  $product
	 * @return string
	 */
	public function get_product_name( $product ) {
		return isset( $product->variation_id ) ? get_the_title( $product->id ) . ' (' . implode( ', ', $product->get_variation_attributes() ) . ')' : get_the_title( $product->id );
	}

	/**
	 * Transfers a single Order to eKomi
	 *
	 * @param integer $order_id
	 * @return boolean
	 */
	public function put_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! isset( $order->ekomi_review_link ) ) {
			$items = $order->get_items();
			$product_ids = array();
			if ( !empty( $items ) ) {
				foreach ( $items as $item ) {
					$product_ids[] = ( empty( $item[ 'variation_id' ] ) ? $item[ 'product_id' ] : $item[ 'variation_id' ] );
				}
			}
			$response = unserialize( file_get_contents(
				'https://api.ekomi.de/v2/putOrder?auth=' . $this->interface_id . '|' . $this->interface_password . '&version=' . $this->version . '&order_id=' . urlencode( esc_attr( $order->id ) ) . '&product_ids=' . urlencode( esc_attr( implode( ',', $product_ids ) ) )
			) );
			if ( $response['done'] == 1 && isset( $response['link'] ) )
				update_post_meta( $order->id, '_ekomi_review_link', $response[ 'link' ] );
			return ( $response['done'] == 1 && isset( $response['link'] ) ) ? true : false;
		}
		return false;
	}

	/**
	 * Send Customer Email notifications if necessary (loops through orders and checks day difference after completion)
	 */
	public function send_mails() {
		$order_query = new WP_Query(
			array( 'post_type' => 'shop_order', 'post_status' => 'wc-completed', 'meta_query' =>
				array(
					array(
						'key'     => '_ekomi_review_link',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_ekomi_review_mail_sent',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		while ( $order_query->have_posts() ) {
			$order_query->next_post();
			$order = wc_get_order( $order_query->post->ID );
			$diff = WC_germanized()->get_date_diff( $order->completed_date, date( 'Y-m-d H:i:s' ) );
			if ( $diff[ 'd' ] >= (int) get_option( 'woocommerce_gzd_ekomi_day_diff' ) ) {

				if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_ekomi' ) ) {
					$mail->trigger( $order->id );
					update_post_meta( $order->id, '_ekomi_review_mail_sent', 1 );
					update_post_meta( $order->id, '_ekomi_review_link', '' );
				}

			}
		}
	}

	/**
	 * Grabs the reviews from eKomi and saves them as review within the Shop
	 *
	 * @return boolean
	 */
	public function get_reviews() {

		$response = file_get_contents(
			'https://api.ekomi.de/get_productfeedback.php?interface_id=' . $this->interface_id . '&interface_pw=' . $this->interface_password . '&version=' . $this->version . '&type=csv&product=all'
		);
		if ( ! empty( $response ) ) {
			$reviews = explode( PHP_EOL, $response );
			
			if ( ! empty( $reviews ) ) {
				
				foreach ( $reviews as $review ) {
					$review = str_getcsv( $review );
					if ( ! empty( $review[0] ) ) {
						
						if ( ! $this->review_exists( $review[1] ) && $this->is_product( (int) esc_attr( $review[2] ) ) ) {
							
							$product = wc_get_product( (int) esc_attr( $review[2] ) );
							
							$data = array(
								'comment_post_ID' => $product->id,
								'comment_author' => $this->user->user_login,
								'comment_author_email' => $this->user->user_email,
								'comment_content' => preg_replace( '/\v+|\\\[rn]/', '<br/>', esc_attr( $review[4] ) ),
								'comment_date' => date( 'Y-m-d H:i:s', (int) esc_attr( $review[0] ) ),
								'comment_approved' => 1,
							);

							$comment_id = wp_insert_comment( $data );
							
							if ( $comment_id ) {
								add_comment_meta( $comment_id, 'rating', (int) esc_attr( $review[3] ), true );
								add_comment_meta( $comment_id, 'order_id', esc_attr( $review[1] ), true );
							}
						}
					}
				}
			}
		}
		return ( is_array( $reviews ) ) ? true : false;
	}

	public function is_product( $id ) {
		return ( get_post_status( $id ) == 'publish' ) ? true : false;
	}

	/**
	 * Checks if a review already exists by using a eKomi order ID
	 *
	 * @param string  $review_order_id
	 * @return boolean
	 */
	public function review_exists( $review_order_id ) {
		$comments_query = new WP_Comment_Query;
		$comments       = $comments_query->query( array( 'meta_key' => 'order_id', 'meta_value' => $review_order_id ) );
		return empty( $comments ) ? false : true;
	}

	/**
	 * Returns the eKomi Widget html
	 *
	 * @param array   $atts
	 * @return string
	 */
	public function get_widget( $atts = array() ) {
		return ( $this->is_enabled() ) ? '<div id="eKomiWidget_default"></div>' : '';
	}

	/**
	 * Returns the eKomi Badge html
	 *
	 * @param array   $atts
	 * @return string
	 */
	public function get_badge( $atts = array() ) {
		extract( shortcode_atts( array( 'width' => '', ), $atts ) );
		return ( $this->is_enabled() ) ? '<div id="eKomiSeal_default" style="' . ( $width ? 'width:' . $width . 'px;height:' . $width . 'px;' : '' ) . '"></div>' : '';
	}

	/**
	 * Adds necessary scripts to the Footer to enable badge + widget generation
	 */
	public function add_scripts() {
		echo '
			<script type="text/javascript">
				(function(){
					eKomiIntegrationConfig = new Array(
						{certId:\'' . get_option( 'woocommerce_gzd_ekomi_partner_id' ) . '\'}
					);
					if(typeof eKomiIntegrationConfig != "undefined"){for(var eKomiIntegrationLoop=0;eKomiIntegrationLoop<eKomiIntegrationConfig.length;eKomiIntegrationLoop++){
						var eKomiIntegrationContainer = document.createElement(\'script\');
						eKomiIntegrationContainer.type = \'text/javascript\'; eKomiIntegrationContainer.defer = true;
						eKomiIntegrationContainer.src = (document.location.protocol==\'https:\'?\'https:\':\'http:\') +"//connect.ekomi.de/integration_1409045085/" + eKomiIntegrationConfig[eKomiIntegrationLoop].certId + ".js";
						document.getElementsByTagName("head")[0].appendChild(eKomiIntegrationContainer);
					}}else{if(\'console\' in window){ console.error(\'connectEkomiIntegration - Cannot read eKomiIntegrationConfig\'); }}
				})();
			</script>
		';
	}

	/**
	 * Returns eKomi Settings for Admin Interface
	 *
	 * @return array
	 */
	public function get_settings() {

		return array(

			array( 'title' => _x( 'Ekomi Integration', 'ekomi', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'ekomi_options' ),

			array(
				'title'  => _x( 'Shop ID', 'ekomi', 'woocommerce-germanized' ),
				'desc'   => _x( 'Insert your Shop ID here.', 'ekomi', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_ekomi_shop_id',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Link to Certificate', 'ekomi', 'woocommerce-germanized' ),
				'desc'   => _x( 'Insert the link to your Certificate', 'ekomi', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_ekomi_certificate_link',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Partner ID', 'ekomi', 'woocommerce-germanized' ),
				'desc'   => _x( 'Insert your Partner ID here (you may find that ID on your certificate website)', 'ekomi', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_ekomi_partner_id',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Interface ID', 'ekomi', 'woocommerce-germanized' ),
				'desc'   => _x( 'Insert your Interface ID here.', 'ekomi', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_ekomi_interface_id',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Interface Password', 'ekomi', 'woocommerce-germanized' ),
				'desc'   => _x( 'Insert your Interface Password here.', 'ekomi', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_ekomi_interface_password',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Days until Email', 'ekomi', 'woocommerce-germanized' ),
				'desc'   => _x( 'Number of days between an order being marked as completed and review email to customer.', 'ekomi', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_ekomi_day_diff',
				'type'   => 'number',
				'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
				'default' => 7,
			),

			array( 'type' => 'sectionend', 'id' => 'ekomi_options' ),

		);

	}

	public function register_section( $sections ) {
		$sections[ 'ekomi' ] = _x( 'eKomi Options', 'ekomi', 'woocommerce-germanized' );
		return $sections;
	}

}

?>
