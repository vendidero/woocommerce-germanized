<?php
/**
 * Admin View: Rich Snippets Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "Organization",
    "name": "{name}",
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{average}",
        "bestRating": "{maximum}",
        "ratingCount": "{count}"
    }
}
</script>
