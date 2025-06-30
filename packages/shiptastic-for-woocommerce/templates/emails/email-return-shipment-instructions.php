<?php
/**
 * Email return shipment instructions
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/email-return-shipment-instructions.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Shiptastic/Templates/Emails
 * @version 4.3.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$provider = $shipment->get_shipping_provider_instance();
?>

<?php if ( $provider && $provider->has_return_instructions() ) : ?>
	<p><?php echo wp_kses_post( wpautop( wptexturize( $provider->get_return_instructions() ) ) . PHP_EOL ); ?></p>
<?php endif; ?>
