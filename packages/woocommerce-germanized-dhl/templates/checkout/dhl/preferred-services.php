<?php
/**
 * The Template for displaying DHL preferred services within checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/dhl/preferred-services.php.
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
<tr class="dhl-preferred-service">
	<td colspan="2" class="dhl-preferred-service-content">
		<div class="dhl-preferred-service-item dhl-preferred-service-header">
			<div class="dhl-preferred-service-logo">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="DHL logo" class="dhl-co-logo">
			</div>
			<div class="dhl-preferred-service-title">
				<?php echo esc_html_x( 'DHL Preferred Delivery. Delivered just as you wish.', 'dhl', 'woocommerce-germanized' ); ?>
			</div>
			<?php if ( $preferred_day_enabled || $preferred_location_enabled || $preferred_neighbor_enabled ) : ?>
				<div class="dhl-preferred-service-desc">
					<?php echo wp_kses_post( _x( 'Thanks to the ﬂexible recipient services of DHL Preferred Delivery, you decide when and where you want to receive your parcels.<br/>Please choose your preferred delivery option.', 'dhl', 'woocommerce-germanized' ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $preferred_day_options ) && $preferred_day_enabled ) : ?>
			<div class="dhl-preferred-service-item dhl-preferred-service-day">
				<div class="dhl-preferred-service-title"><?php echo esc_html_x( 'Delivery day', 'dhl', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Choose one of the displayed days as your delivery day for your parcel delivery. Other days are not possible due to delivery processes.', 'dhl', 'woocommerce-germanized' ) ); ?></div>

				<?php if ( ! empty( $preferred_day_cost ) ) : ?>
					<div class="dhl-preferred-service-cost">
						<?php echo wp_kses_post( sprintf( _x( 'There is a surcharge of %1$s %2$s for this service.*', 'dhl', 'woocommerce-germanized' ), wc_price( $preferred_day_cost ), ( wc_gzd_additional_costs_include_tax() ? _x( 'incl. VAT', 'dhl', 'woocommerce-germanized' ) : _x( 'excl. VAT', 'dhl', 'woocommerce-germanized' ) ) ) ); ?>
					</div>
				<?php endif; ?>

				<div class="dhl-preferred-service-data">
					<ul class="dhl-preferred-service-times dhl-preferred-service-days">
						<?php
						foreach ( $preferred_day_options as $key => $value ) :
							$key          = empty( $key ) ? '' : $key;
							$week_day_num = empty( $key ) ? '-' : esc_html( date( 'j', strtotime( $key ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							$is_selected  = $preferred_day === $key ? 'checked="checked"' : '';
							?>
							<li>
								<input type="radio" name="dhl_preferred_day" class="dhl-preferred-day-option" id="dhl-preferred-day-<?php echo esc_attr( $key ); ?>" value="<?php echo ( esc_attr( empty( $key ) ? '' : date( 'Y-m-d', strtotime( $key ) ) ) ); ?>" <?php echo $is_selected; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.DateTime.RestrictedFunctions.date_date ?> />
								<label for="dhl-preferred-day-<?php echo esc_attr( $key ); ?>"><span class="dhl-preferred-time-title"><?php echo esc_html( $week_day_num ); ?></span><span class="dhl-preferred-time-value"><?php echo esc_html( $value ); ?></span></label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $preferred_location_enabled || $preferred_neighbor_enabled ) : ?>
			<div class="dhl-preferred-service-item dhl-preferred-service-location">
				<div class="dhl-preferred-service-title"><?php echo esc_html_x( 'Drop-off location or neighbor', 'dhl', 'woocommerce-germanized' ); ?></div>

				<div class="dhl-preferred-service-data">
					<ul class="dhl-preferred-location-types">
						<li>
							<input type="radio" name="dhl_preferred_location_type" id="dhl-preferred_location-none" class="" value="none" <?php checked( 'none', $preferred_location_type ); ?> />
							<label for="dhl-preferred_location-none"><?php echo esc_html_x( 'None', 'dhl location context', 'woocommerce-germanized' ); ?></label>
						</li>
						<?php if ( $preferred_location_enabled ) : ?>
							<li>
								<input type="radio" name="dhl_preferred_location_type" id="dhl-preferred_location-place" class="" value="place" <?php checked( 'place', $preferred_location_type ); ?> />
								<label for="dhl-preferred_location-place"><?php echo esc_html_x( 'Location', 'dhl', 'woocommerce-germanized' ); ?></label>
							</li>
						<?php endif; ?>
						<?php if ( $preferred_neighbor_enabled ) : ?>
							<li>
								<input type="radio" name="dhl_preferred_location_type" id="dhl-preferred_location-neighbor" class="" value="neighbor" <?php checked( 'neighbor', $preferred_location_type ); ?> />
								<label for="dhl-preferred_location-neighbor"><?php echo esc_html_x( 'Neighbor', 'dhl', 'woocommerce-germanized' ); ?></label>
							</li>
						<?php endif; ?>
					</ul>

					<?php if ( $preferred_location_enabled ) : ?>
						<div class="dhl-preferred-service-item dhl-preferred-service-location-data dhl-preferred-service-location-place dhl-hidden">
							<div class="dhl-preferred-service-title"><?php echo esc_html_x( 'Drop-off location', 'dhl', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Choose a weather-protected and non-visible place on your property, where we can deposit the parcel in your absence.', 'dhl', 'woocommerce-germanized' ) ); ?></div>
							<div class="dhl-preferred-service-data">
								<input type="text" name="dhl_preferred_location" id="dhl-preferred-location" class="" value="<?php echo esc_attr( $preferred_location ); ?>" maxlength="80" placeholder="<?php echo esc_attr( _x( 'e.g. Garage, Terrace', 'dhl', 'woocommerce-germanized' ) ); ?>" />
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $preferred_neighbor_enabled ) : ?>
						<div class="dhl-preferred-service-item dhl-preferred-service-location-data dhl-preferred-service-location-neighbor dhl-hidden">
							<div class="dhl-preferred-service-title"><?php echo esc_html_x( 'Neighbor', 'dhl', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Determine a person in your immediate neighborhood whom we can hand out your parcel in your absence. This person should live in the same building, directly opposite or next door.', 'dhl', 'woocommerce-germanized' ) ); ?></div>
							<div class="dhl-preferred-service-data">
								<input type="text" name="dhl_preferred_location_neighbor_name" id="dhl-preferred-location-neighbor-name" class="" value="<?php echo esc_attr( $preferred_location_neighbor_name ); ?>" maxlength="25" placeholder="<?php echo esc_attr( _x( 'First name, last name of neighbor', 'dhl', 'woocommerce-germanized' ) ); ?>" />
								<input type="text" name="dhl_preferred_location_neighbor_address" id="dhl-preferred-location-neighbor-address" class="" value="<?php echo esc_attr( $preferred_location_neighbor_address ); ?>" maxlength="55" placeholder="<?php echo esc_attr( _x( 'Street, number, postal code, city', 'dhl', 'woocommerce-germanized' ) ); ?>" />
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $preferred_delivery_type_enabled ) : ?>
			<div class="dhl-preferred-service-item dhl-preferred-delivery-type">
				<div class="dhl-preferred-service-title"><?php echo esc_html_x( 'Delivery Type', 'dhl', 'woocommerce-germanized' ); ?></div>

				<div class="dhl-preferred-service-data">
					<ul class="dhl-preferred-delivery-types">
						<?php foreach ( $preferred_delivery_types as $delivery_type => $delivery_type_title ) : ?>
							<li>
								<input type="radio" name="dhl_preferred_delivery_type" id="dhl-preferred_delivery_type-<?php echo esc_attr( $delivery_type ); ?>" class="" value="<?php echo esc_attr( $delivery_type ); ?>" <?php checked( $delivery_type, $preferred_delivery_type ); ?> />
								<label for="dhl-preferred_delivery_type-<?php echo esc_attr( $delivery_type ); ?>"><?php echo esc_html( $delivery_type_title ); ?>
									<?php if ( 'cdp' === $delivery_type ) : ?>
										<?php echo wc_help_tip( _x( 'Delivery to nearby parcel store/locker or to the front door.', 'dhl', 'woocommerce-germanized' ) ); ?></label>
								<?php elseif ( 'home' === $delivery_type ) : ?>
									<?php echo ( ! empty( $preferred_home_delivery_cost ) ? wp_kses_post( sprintf( _x( '(+%1$s %2$s)*', 'dhl', 'woocommerce-germanized' ), wc_price( $preferred_home_delivery_cost ), ( wc_gzd_additional_costs_include_tax() ? _x( 'incl. VAT', 'dhl', 'woocommerce-germanized' ) : _x( 'excl. VAT', 'dhl', 'woocommerce-germanized' ) ) ) ) : '' ); ?>
									<?php echo wc_help_tip( _x( 'Delivery usually to the front door.', 'dhl', 'woocommerce-germanized' ) ); ?>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>
	</td>
</tr>
