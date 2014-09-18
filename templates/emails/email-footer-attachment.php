<?php
/**
 * Email Footer Page Attachment
 *
 * @author Vendidero
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

global $post;

setup_postdata( $GLOBALS['post'] =& $post_attach );

$content = ( empty( $GLOBALS['post']->post_excerpt ) ? $GLOBALS['post']->post_content : $GLOBALS['post']->post_excerpt );
$print_title = true;
if ( substr( trim( $content ), 0, 2 ) == '<h' )
	$print_title = false;
?>

<div class="wc-gzd-email-attach-post smaller" id="wc-gzd-email-attach-post-<?php the_id();?>">

	<?php if ( $print_title ) : ?>
		<h4 class="wc-gzd-mail-main-title"><?php the_title();?></h4>
	<?php endif; ?>

	<div class="wc-gzd-email-attached-content">

		<?php if ( empty( $GLOBALS['post']->post_excerpt ) ) : ?>

			<?php the_content();?>

		<?php else : ?>

			<?php the_excerpt(); ?>

		<?php endif; ?>

	</div>

</div>

<?php wp_reset_postdata(); ?>