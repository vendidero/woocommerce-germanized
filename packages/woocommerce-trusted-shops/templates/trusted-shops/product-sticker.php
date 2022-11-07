<?php
/**
 * Trusted Shops Product Sticker
 *
 * @author      Vendidero
 * @package     WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
	global $post;
	$skus = $plugin->get_product_skus( $post->ID );
?>
<!-- Module: WooCommerce Germanized -->
<div <?php echo $plugin->get_selector( 'product_sticker' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>

<script type="text/javascript">
	<?php echo $plugin->get_product_sticker_code( true, array( 'sku' => $skus ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</script>
