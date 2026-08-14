<?php
/**
 * The Template for displaying a popover.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/global/popover.php.
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

wp_enqueue_script( 'wc-gzd-popover' );
?>
<a href="<?php echo $popover_fallback_url ? esc_url( $popover_fallback_url ) : '#'; ?>" <?php echo $popover_fallback_url ? 'target="_blank"' : ''; ?> class="wc-gzd-popover-trigger"><?php echo wc_gzd_kses_post_svg( $popover_trigger_html ); ?></a>
<div popover role="tooltip" class="wc-gzd-popover" aria-label="<?php echo esc_attr( isset( $popover_description ) ? $popover_description : '' ); ?>">
	<div class="wc-gzd-popover-inner">
		<div class="wc-gzd-popover-header">
			<a href="#" class="wc-gzd-popover-close" aria-label="<?php echo esc_attr__( 'Close dialog', 'woocommerce-germanized' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
					<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
				</svg>
			</a>
		</div>
		<div class="wc-gzd-popover-content">
			<?php echo wc_gzd_kses_post_svg( $popover_html ); ?>
		</div>
	</div>
</div>
