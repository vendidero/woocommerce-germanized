<?php
/**
 * The Template for displaying parcel finder lightbox within checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/dhl/parcel-finder.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/DHL/Templates
 * @version 1.0.2
 */
defined( 'ABSPATH' ) || exit;
?>
<div id="dhl-parcel-finder-wrapper">
	<div id="dhl-parcel-finder-bg-overlay"></div>
	<div id="dhl-parcel-finder-inner">
		<div id="dhl-parcel-finder-inner-wrapper">
			<div id="dhl-parcel-finder">
				<form id="dhl-parcel-finder-form" method="post">
					<p class="form-row form-field small">
						<input type="text" name="dhl_parcelfinder_postcode" class="input-text" placeholder="<?php echo esc_attr_x( 'Postcode', 'dhl', 'woocommerce-germanized' ); ?>" id="dhl-parcelfinder-postcode" />
					</p>
					<p class="form-row form-field small">
						<input type="text" name="dhl_parcelfinder_city" class="input-text" placeholder="<?php echo esc_attr_x( 'City', 'dhl', 'woocommerce-germanized' ); ?>" id="dhl-parcelfinder-city" />
					</p>
					<p class="form-row form-field large">
						<input type="text" name="dhl_parcelfinder_address" class="input-text" placeholder="<?php echo esc_attr_x( 'Address', 'dhl', 'woocommerce-germanized' ); ?>" id="dhl-parcelfinder-address" />
					</p>

					<p class="form-row form-field finder-pickup-type packstation <?php echo ( ! $is_packstation_enabled ? 'hidden' : '' ); ?>" data-pickup_type="packstation">
						<input type="checkbox" name="dhl_parcelfinder_packstation_filter" class="input-checkbox" id="dhl-packstation-filter" value="yes" <?php echo ( $is_packstation_enabled ? 'checked="checked"' : '' ); ?> />
						<label for="dhl-packstation-filter"><?php echo esc_attr_x( 'Packstation', 'dhl', 'woocommerce-germanized' ); ?></label>
						<span class="icon" style="background-image: url('<?php echo esc_url( $img_packstation ); ?>');"></span>
					</p>

					<p class="form-row form-field finder-pickup-type parcelshop <?php echo ( ! $is_parcelshop_enabled ? 'hidden' : '' ); ?>" data-pickup_type="parcelshop">
						<input type="checkbox" name="dhl_parcelfinder_parcelshop_filter" class="input-checkbox" id="dhl-parcelshop-filter" value="yes" <?php echo ( $is_parcelshop_enabled ? 'checked="checked"' : '' ); ?> />
						<label for="dhl-parcelshop-filter"><?php echo esc_attr_x( 'Parcelshop', 'dhl', 'woocommerce-germanized' ); ?></label>
						<span class="icon" style="background-image: url('<?php echo esc_url( $img_parcelshop ); ?>');"></span>
					</p>

					<p class="form-row form-field finder-pickup-type postoffice <?php echo ( ! $is_postoffice_enabled ? 'hidden' : '' ); ?>" data-pickup_type="postoffice">
						<input type="checkbox" name="dhl_parcelfinder_postoffice_filter" class="input-checkbox" id="dhl-postoffice-filter" value="yes" <?php echo ( $is_postoffice_enabled ? 'checked="checked"' : '' ); ?> />
						<label for="dhl-postoffice-filter"><?php echo esc_attr_x( 'Postoffice', 'dhl', 'woocommerce-germanized' ); ?></label>
						<span class="icon" style="background-image: url('<?php echo esc_url( $img_postoffice ); ?>');"></span>
					</p>

					<p id="dhl-search-button" class="form-row form-field small">
						<input type="submit" class="button" name="apply_parcel_finder" value="<?php echo esc_attr_x( 'Search', 'dhl', 'woocommerce-germanized' ); ?>" />
					</p>

					<input type="hidden" name="dhl_parcelfinder_country" id="dhl-parcelfinder-country" />
					<div class="clear"></div>

					<button class="dhl-parcel-finder-close" title="close"><svg viewBox="0 0 32 32"><path d="M10,10 L22,22 M22,10 L10,22"></path></svg></button>
				</form>

				<div class="notice-wrapper"></div>
				<div id="dhl-parcel-finder-map"></div>
			</div>
		</div>
	</div>
</div>
