<?php
/**
 * Customer new account activation email.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/customer-new-account-activation.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.6.5
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
    <p><?php printf( __( "Thanks for creating an account on %s. Please follow the activation link to activate your account:", 'woocommerce-germanized' ), esc_html( $blogname ) ); ?></p>
    <p><a class="wc-button button" href="<?php echo esc_url( $user_activation_url ); ?>" target="_blank"><?php _e( 'Activate your account', 'woocommerce-germanized' ); ?></a></p>
<?php if ( get_option( 'woocommerce_registration_generate_password' ) == 'yes' && $password_generated ) : ?>
    <p><?php printf( __( "Your password has been automatically generated: <strong>%s</strong>", 'woocommerce-germanized' ), esc_html( $user_pass ) ); ?></p>
<?php endif; ?>
    <p><?php printf( __( "If you haven't created an account on %s please ignore this email.", "woocommerce-germanized" ), esc_html( $blogname ) ); ?></p>
    <p><?php printf( __( 'If you cannot follow the link above please copy this url and paste it to your browser bar: %s', 'woocommerce-germanized' ), WC_germanized()->emails->prevent_html_url_auto_link( esc_url( $user_activation_url ) ) ); ?></p>
<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>