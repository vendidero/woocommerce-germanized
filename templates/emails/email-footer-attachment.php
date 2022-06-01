<?php
/**
 * Template for embedding legal page content within email footer.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/email-footer-attachment.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.1.1
 *
 * @var int $post_id
 * @var string $post_content
 * @var boolean $print_title
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div class="wc-gzd-email-attach-post smaller" id="wc-gzd-email-attach-post-<?php echo esc_attr( get_the_ID() ); ?>">
	<?php if ( $print_title ) : ?>
		<h4 class="wc-gzd-mail-main-title"><?php the_title(); ?></h4>
	<?php endif; ?>

	<div class="wc-gzd-email-attached-content">
		<?php echo wp_kses_post( $post_content ); ?>
	</div>
</div>
