<?php
/**
 * The Template for displaying the authenticity status for a certain review.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/review-authenticity-status.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $comment;
$verified = wc_gzd_product_review_is_verified( $comment->comment_ID );

if ( '0' === $comment->comment_approved ) {
	return;
}
?>

<p class="wc-gzd-additional-info wc-gzd-review-authenticity-status <?php echo ( $verified ? 'is-verified' : 'is-unverified' ); ?>">
	<?php echo wp_kses_post( wc_gzd_get_legal_product_review_authenticity_notice( $comment->comment_ID ) ); ?>
</p>
