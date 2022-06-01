<?php
/**
 * The Template for displaying complaints text e.g. in terms or on imprint page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/global/complaints.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}  // Exit if accessed directly ?>

<?php if ( ! $text_only ) : ?>
	<h3><?php esc_html_e( 'Alternative Dispute Resolution in accordance with Art. 14 (1) ODR-VO and § 36 VSBG:', 'woocommerce-germanized' ); ?></h3>
<?php endif; ?>

<?php echo wp_kses_post( $dispute_text ); ?>
