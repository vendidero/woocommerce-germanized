<?php
/**
 * The Template for displaying a parcel finder result on the map.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/dhl/parcel-finder-result.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/DHL/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;
?>

<div id="parcel-content">
	<div id="site-notice"></div>
	<h4 class="parcel-title"><?php echo $result->gzd_name; ?></h4>
	<div id="bodyContent">
		<address>
			<?php echo $result->address->street; ?> <?php echo $result->address->streetNo; ?><br/>
			<?php echo $result->address->zip; ?> <?php echo $result->address->city; ?><br/>
		</address>

		<?php if ( 'packstation' !== $result->gzd_type ) : ?>
			<div class="parcel-opening-hours">
				<h5 class="parcel-subtitle"><?php _ex( 'Opening Times', 'dhl', 'woocommerce-germanized' ); ?></h5>

				<?php foreach( $result->gzd_opening_hours as $time ) : ?>
					<?php echo $time['weekday']; ?>: <?php echo $time['time_html']; ?><br/>
				<?php endforeach; ?>
			</div>

			<div class="parcel-services">
				<h5 class="parcel-subtitle"><?php _ex( 'Services', 'dhl', 'woocommerce-germanized' ); ?></h5>

				<?php _ex( 'Handicap Accessible', 'dhl', 'woocommerce-germanized' ); ?>: <?php echo ( $result->hasHandicappedAccess ? _x( 'Yes', 'dhl', 'woocommerce-germanized' ) : _x( 'No', 'dhl', 'woocommerce-germanized' ) ); ?><br/>
				<?php _ex( 'Parking', 'dhl', 'woocommerce-germanized' ); ?>: <?php echo ( $result->hasParkingArea ? _x( 'Yes', 'dhl', 'woocommerce-germanized' ) : _x( 'No', 'dhl', 'woocommerce-germanized' ) ); ?><br/>
			</div>
		
		<?php endif; ?>

		<button type="button" class="dhl-parcelshop-select-btn" id="<?php echo esc_attr( $result->gzd_result_id ); ?>"><?php _ex( 'Select ', 'dhl', 'woocommerce-germanized' ); ?></button>
	</div>
</div>
