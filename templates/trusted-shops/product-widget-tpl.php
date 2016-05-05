<?php
/**
 * Admin View: Product Widget Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<script type="text/javascript">
var summaryBadge = new productStickerSummary();
summaryBadge.showSummary({
	'tsId': '{id}',
	'sku': ['{sku}'],
	'element': '{element}',
	'starColor' : '{star_color}',
	'starSize' : '{star_size}px',
	'fontSize' : '{font_size}px',
	'showRating' : 'true',
	'scrollToReviews' : 'false'
});</script>