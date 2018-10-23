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

<div id="ts_review_sticker"></div>

<script type="text/javascript">
	<?php echo WC_germanized()->trusted_shops->get_review_sticker_code( true ); ?>
</script>