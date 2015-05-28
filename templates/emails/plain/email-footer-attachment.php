<?php
/**
 * Email Footer Page Attachment (plain)
 *
 * @author Vendidero
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

global $post;
$post = $post_attach;

setup_postdata( $post );

$content = ( empty( $post->post_excerpt ) ? $post->post_content : $post->post_excerpt );
$print_title = true;

if ( substr( trim( $content ), 0, 2 ) == '<h' )
	$print_title = false;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

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