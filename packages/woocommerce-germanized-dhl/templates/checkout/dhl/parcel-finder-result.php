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
 * @version 1.1.0
 */
defined( 'ABSPATH' ) || exit;
?>
<div id="parcel-content">
	<div id="site-notice"></div>
	<h4 class="parcel-title"><?php echo esc_html( $result->gzd_name ); ?></h4>
	<div id="bodyContent">
		<address>
			<?php
			echo wp_kses_post(
				WC()->countries->get_formatted_address(
					array(
						'address_1' => $result->place->address->streetAddress,
						'postcode'  => $result->place->address->postalCode,
						'city'      => $result->place->address->addressLocality,
						'country'   => $result->place->address->countryCode,
					)
				)
			);
			?>
		</address>

		<?php if ( 'packstation' !== $result->gzd_type ) : ?>
			<div class="parcel-opening-hours">
				<h5 class="parcel-subtitle"><?php echo esc_html_x( 'Opening Times', 'dhl', 'woocommerce-germanized' ); ?></h5>

				<?php foreach ( $result->gzd_opening_hours as $time ) : ?>
					<?php echo esc_html( $time['weekday'] ); ?>: <?php echo wp_kses_post( $time['time_html'] ); ?><br/>
				<?php endforeach; ?>
			</div>

			<div class="parcel-services">
				<h5 class="parcel-subtitle"><?php echo esc_html_x( 'Services', 'dhl', 'woocommerce-germanized' ); ?></h5>

				<?php echo esc_html_x( 'Handicap Accessible', 'dhl', 'woocommerce-germanized' ); ?>: <?php echo ( in_array( 'handicapped-access', $result->serviceTypes, true ) ? esc_html_x( 'Yes', 'dhl', 'woocommerce-germanized' ) : esc_html_x( 'No', 'dhl', 'woocommerce-germanized' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase ?><br/>
				<?php echo esc_html_x( 'Parking', 'dhl', 'woocommerce-germanized' ); ?>: <?php echo ( ( in_array( 'parking', $result->serviceTypes, true ) ) ? esc_html_x( 'Yes', 'dhl', 'woocommerce-germanized' ) : esc_html_x( 'No', 'dhl', 'woocommerce-germanized' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase ?><br/>
			</div>
		<?php endif; ?>

		<button type="button" class="dhl-parcelshop-select-btn" id="<?php echo esc_attr( $result->gzd_result_id ); ?>"><?php echo esc_html_x( 'Select ', 'dhl', 'woocommerce-germanized' ); ?></button>
	</div>
</div>
