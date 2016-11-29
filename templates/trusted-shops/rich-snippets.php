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

<div itemscope itemtype="http://schema.org/LocalBusiness" class="wc-gzd-trusted-shops-rating-widget">
	<meta itemprop="name" content="<?php echo bloginfo( 'url' ); ?>">
	<meta itemprop="image" content="<?php echo $image; ?>">
	<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">	
		<div class="star-rating">
			<span style="width:<?php echo ( ( $rating[ 'avg' ] / $rating[ 'max' ] ) * 100 ); ?>%"></span>
		</div>
		<p>
			<?php 
				printf( 
					_x( '&#216; %s / %s of %s %s %s customer reviews | Trusted Shops %s', 'trusted-shops', 'woocommerce-germanized' ), 
					'<span itemprop="ratingValue">' . $rating[ 'avg' ] . '</span>', 
					'<span itemprop="bestRating">' . $rating[ 'max' ] . '</span>', 
					'<span itemprop="ratingCount">' . $rating[ 'count' ] . '</span>',
					'<a href="' . $rating_link . '" title="' . sprintf( _x( '%s custom reviews', 'trusted-shops', 'woocommerce-germanized' ), get_bloginfo( 'name' ) ) . '" target="_blank">',
					get_bloginfo( 'name' ),
					'</a>'
				); 
			?>
		</p>
	</div>
</div>