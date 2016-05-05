<?php
/**
 * Admin View: Product Sticker Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<script type="text/javascript">
_tsProductReviewsConfig = {
	tsid: '{id}', 
	sku: ['{sku}'],
	variant: 'productreviews',
	borderColor: '{border_color}',
    locale: '{locale}',
    starColor: '{star_color}',
    starSize: '{star_size}px',
    ratingSummary: 'false',
    maxHeight: '1200px',
    element: '#ts_product_sticker',
    introtext: ''  /* optional */
};
var scripts = document.getElementsByTagName('SCRIPT'), me = scripts[scripts.length - 1];
var _ts = document.createElement('SCRIPT');
_ts.type = 'text/javascript';
_ts.async = true;
_ts.charset = 'utf-8';
_ts.src ='//widgets.trustedshops.com/reviews/tsSticker/tsProductSticker.js';
me.parentNode.insertBefore(_ts, me);
_tsProductReviewsConfig.script = _ts;</script>