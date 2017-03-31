<?php
/**
 * Customer new account email
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( __( "Thanks for creating an account on %s. Please follow the activation link to activate your account:", 'woocommerce-germanized' ), esc_html( $blogname ) ); ?></p>

<p><a class="wc-button button" href="<?php echo esc_attr( $user_activation_url );?>"><?php _e( 'Activate your account', 'woocommerce-germanized' );?></a></p>

<?php if ( get_option( 'woocommerce_registration_generate_password' ) == 'yes' && $password_generated ) : ?>

	<p><?php printf( __( "Your password has been automatically generated: <strong>%s</strong>", 'woocommerce-germanized' ), esc_html( $user_pass ) ); ?></p>

<?php endif; ?>

<p><?php printf( __( "If you haven't created an account on %s please ignore this email.", "woocommerce-germanized" ),esc_html( $blogname ) );?></p>

<p><?php printf( __( 'If you cannot follow the link above please copy this url and paste it to your browser bar: %s', 'woocommerce-germanized' ), esc_attr( $user_activation_url ) ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>