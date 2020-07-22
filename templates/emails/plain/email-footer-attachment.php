<?php
/**
 * Template for embedding legal page content within email footer (plain-text).
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/email-footer-attachment.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $post;

$content     = ( empty( $post->post_excerpt ) ? $post->post_content : $post->post_excerpt );
$print_title = true;

if ( substr( trim( $content ), 0, 2 ) == '<h' ) {
	$print_title = false;
}

echo "\n----------------------------------------\n\n";

if ( $print_title ) {
	echo "= ";
	the_title();
	echo " =\n\n";
}

if ( empty( $post->post_excerpt ) ) {
	the_content();
} else {
	the_excerpt();
}

wp_reset_postdata();