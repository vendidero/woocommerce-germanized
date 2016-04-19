<?php
/**
 * Trusted Shops Reviews Graphic
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<a href="<?php echo $rating_link; ?>" target="_blank" title="<?php echo _x( 'Show customer reviews', 'trusted-shops', 'woocommerce-germanized' ); ?>"><?php echo wp_get_attachment_image( $widget_attachment, 'full' ); ?></a>