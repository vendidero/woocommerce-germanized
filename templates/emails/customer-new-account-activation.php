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
<p><?php printf( esc_html__( 'Thanks for creating an account on %s. Please follow the activation link to activate your account:', 'woocommerce-germanized' ), esc_html( $blogname ) ); ?></p>
<p><a class="wc-button button" href="<?php echo esc_url( $user_activation_url ); ?>" target="_blank"><?php esc_html_e( 'Activate your account', 'woocommerce-germanized' ); ?></a></p>

<?php if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && $password_generated && $set_password_url ) : ?>
	<?php // If the password has not been set by the user during the sign up process, send them a link to set a new password ?>
	<p><a href="<?php echo esc_attr( $set_password_url ); ?>"><?php printf( esc_html__( 'Click here to set your new password.', 'woocommerce-germanized' ) ); ?></a></p>
<?php endif; ?>
	<p><?php printf( esc_html__( "If you haven't created an account on %s please ignore this email.", 'woocommerce-germanized' ), esc_html( $blogname ) ); ?></p>
	<p><?php printf( esc_html__( 'If you cannot follow the link above please copy this url and paste it to your browser bar: %s', 'woocommerce-germanized' ), wp_kses_post( WC_germanized()->emails->prevent_html_url_auto_link( esc_url( $user_activation_url ) ) ) ); ?></p>
<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
