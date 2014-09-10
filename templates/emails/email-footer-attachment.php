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

?>

<div class="wc-gzd-email-attach-post smaller" id="wc-gzd-email-attach-post-<?php the_id();?>">

	<h4><?php the_title();?></h4>

	<?php the_content();?>

</div>

<?php wp_reset_postdata(); ?>