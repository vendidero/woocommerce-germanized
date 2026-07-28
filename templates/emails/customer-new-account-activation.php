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
 * @version 4.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$email_improvements_enabled = \Vendidero\Germanized\Package::has_email_improvements_enabled();
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<?php if ( $is_email_change ) : ?>
	<p><?php printf( esc_html__( 'If you have changed your account\'s email address on %s to this one, please click on the confirmation link below:', 'woocommerce-germanized' ), esc_html( $blogname ) ); ?></p>
<?php else : ?>
	<p><?php printf( esc_html__( 'Thanks for creating an account on %s. Please follow the activation link to activate your account:', 'woocommerce-germanized' ), esc_html( $blogname ) ); ?></p>
<?php endif; ?>

<?php
	wc_get_template(
		WC_germanized()->emails->get_email_button_template(),
		array(
			'url'   => $user_activation_url,
			'label' => $is_email_change ? esc_html__( 'Confirm your email address', 'woocommerce-germanized' ) : esc_html__( 'Activate your account', 'woocommerce-germanized' ),
		)
	);
	?>

<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php if ( ! $is_email_change && 'yes' === get_option( 'woocommerce_registration_generate_password' ) && $password_generated && $set_password_url ) : ?>
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
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
