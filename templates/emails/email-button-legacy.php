<?php
/**
 * Template for embedding an email button (Woo < 11.X).
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/email-button-legacy.php.
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
defined( 'ABSPATH' ) || exit;

// Fall back to the default when the option is set but empty (get_option's default only covers a missing option).
$wc_button_bg   = get_option( 'woocommerce_email_base_color', '#7f54b3' );
$wc_button_bg   = $wc_button_bg ? $wc_button_bg : '#7f54b3';
$wc_button_text = wc_hex_is_light( $wc_button_bg ) ? '#000000' : '#ffffff';
?>
<p style="margin: 24px 0;">
	<a href="<?php echo esc_url( $url ); ?>" style="display:inline-block;padding:16px 32px;background-color:<?php echo esc_attr( $wc_button_bg ); ?>;color:<?php echo esc_attr( $wc_button_text ); ?>;border-radius:4px;font-weight:bold;font-size:15px;text-decoration:none;">
		<?php echo esc_html( $label ); ?>
	</a>
</p>