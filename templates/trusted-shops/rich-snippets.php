<?php
/**
 * Trusted Shops Rich Snippets HTML
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<!-- Module: WooCommerce Germanized -->
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "Organization",
    "name": "<?php echo $name; ?>",
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?php echo $rating['avg']; ?>",
        "bestRating": "<?php echo $rating['max']; ?>",
        "ratingCount": "<?php echo $rating['count']; ?>"
    }
    <?php do_action( 'woocommerce_trusted_shops_rich_snippets_ld_json' ); ?>
}
</script>