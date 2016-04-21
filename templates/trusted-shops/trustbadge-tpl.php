<?php
/**
 * Admin View: Trustbadge Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<script type="text/javascript">
(function () {
	var _tsid = '{id}'; 
	_tsConfig = {
		'yOffset': '{offset}', /* offset from page bottom */
		'variant': '{variant}', /* text, default, small, reviews, custom, custom_reviews */
		'customElementId': '', /* required for variants custom and custom_reviews */
		'trustcardDirection': '', /* for custom variants: topRight, topLeft, bottomRight, bottomLeft */ 'customBadgeWidth': '', /* for custom variants: 40 - 90 (in pixels) */
		'customBadgeHeight': '', /* for custom variants: 40 - 90 (in pixels) */
		'disableResponsive': 'false', /* deactivate responsive behaviour */
		'disableTrustbadge': '{disable}', /* deactivate trustbadge */
		'trustCardTrigger': 'mouseenter', /* set to 'click' if you want the trustcard to be opened on click instead */ 'customCheckoutElementId': ''/* required for custom trustcard */
	};
	var _ts = document.createElement('script');
	_ts.type = 'text/javascript';
	_ts.charset = 'utf-8';
	_ts.async = true;
	_ts.src = '//widgets.trustedshops.com/js/' + _tsid + '.js'; var __ts = document.getElementsByTagName('script')[0]; __ts.parentNode.insertBefore(_ts, __ts);
})();</script>