<?php
/**
 * Trusted Shops Product Sticker
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<!-- Module: WooCommerce Germanized -->
<div id="ts_review_sticker"></div>

<script type="text/javascript">
	<?php echo $plugin->get_review_sticker_code( true ); ?>
</script>