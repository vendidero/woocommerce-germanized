<?php
/**
 * Admin View: Product Sticker Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<script type="text/javascript">
    _tsRatingConfig = {
        tsid: '{id}',
        element: '{element}',
	    variant: 'testimonial',
        reviews: '{number}',
	    betterThan: '{better_than}',
        richSnippets: 'on',
        backgroundColor: '{bg_color}',
        linkColor: '#000000',
        fontFamily: '{font}',
        reviewMinLength: '10',
        quotationMarkColor: '#FFFFFF'
    };
    var scripts = document.getElementsByTagName('SCRIPT'),
	    me = scripts[scripts.length - 1];
    var _ts = document.createElement('SCRIPT');
    _ts.type = 'text/javascript';
    _ts.async = true;
    _ts.charset = 'utf-8';
    _ts.src ='//widgets.trustedshops.com/reviews/tsSticker/tsSticker.js';
    me.parentNode.insertBefore(_ts, me);
    _tsRatingConfig.script = _ts;</script>