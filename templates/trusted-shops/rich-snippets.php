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

<div itemscope itemtype="http://data-vocabulary.org/Review-aggregate" class="wc-gzd-trusted-shops-rating-widget">
	<a href="<?php echo $rating_link; ?>" target="_blank" title="<?php printf( _x( '%s ratings', 'trusted-shops', 'woocommerce-germanized' ), get_bloginfo( 'name' ) ); ?>"><span itemprop="itemreviewed"><strong><?php echo get_bloginfo( 'name' ); ?></strong></span></a>
	<div class="star-rating" title="<?php printf( _x( 'Rated %s out of %s', 'trusted-shops', 'woocommerce-germanized' ), $rating['avg'], (int) $rating['max'] ); ?>">
		<span style="width:<?php echo ( ( $rating['avg'] / 5 ) * 100 ); ?>%">
			<strong class="rating"><?php echo esc_html( $rating['avg'] ); ?></strong><?php printf( _x( 'out of %s', 'trusted-shops', 'woocommerce-germanized' ), (int) $rating[ 'max' ] ); ?>
		</span>
	</div>
	<br/>
	<span itemprop="rating" itemscope itemtype="http://data-vocabulary.org/Rating">
 		<?php printf( _x( '%s of %s based on %s <a href="%s" target="_blank">ratings</a>.', 'trusted-shops', 'woocommerce-germanized' ), '&#216; <span itemprop="average">' . $rating['avg'] . '</span>', '<span itemprop="best">' . (int) $rating['max'] . '</span>', '<span class="count" itemprop="votes">' . $rating['count'] . '</span>', $rating_link ); ?>
	</span>
</div>