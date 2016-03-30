/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

	$.fn.wc_gzd_variation_form = function () {
		var $form 	 	= this,
			$wrapper 	= $form.parents( '.type-product' );

		$.fn.wc_gzd_variation_form.reset_variation = function() {

			if ( $('.type-product').find('.org_price').length > 0 ) {
				$( '.type-product .price.variation_modified:not(.price-unit)' ).html( $('.type-product').find('.org_price').html() ).removeClass('variation_modified').show();
			}
			if ( $('.type-product').find('.org_delivery_time').length > 0 ) {
				$( '.type-product .delivery-time-info' ).html( $('.type-product').find('.org_delivery_time').html() ).removeClass('variation_modified').show();
			}
			if ( $('.type-product').find('.org_unit_price').length > 0 ) {
				$( '.type-product .price-unit' ).html( $('.product').find('.org_unit_price').html() ).removeClass('variation_modified').show();
			}
			if ( $('.type-product').find('.org_tax_info').length > 0 ) {
				$( '.type-product .tax-info' ).html( $('.product').find('.org_tax_info').html() ).removeClass('variation_modified').show();
			}
			if ( $('.type-product').find('.org_product_units').length > 0 ) {
				$( '.type-product .product-units' ).html( $('.product').find('.org_product_units').html() ).removeClass('variation_modified').show();
			}
			$('.org_product_info').remove();
			$('.variation_modified').remove();
		}

		$form

		.on( 'found_variation', function( event, variation ) {

			if ( ! $wrapper.find( '.price:first' ).hasClass( 'variation_modified' ) ) {
				$wrapper.append( '<div class="org_price org_product_info">' + $wrapper.find( '.price:not(.price-unit):first' ).html() + '</div>' );
				if ( $wrapper.find( '.delivery-time-info:first' ).length > 0 )
					$wrapper.append( '<div class="org_delivery_time org_product_info">' + $wrapper.find( '.delivery-time-info:first' ).html() + '</div>' );
				if ( $wrapper.find( '.tax-info:first' ).length > 0 )
					$wrapper.append( '<div class="org_tax_info org_product_info">' + $wrapper.find( '.tax-info:first' ).html() + '</div>' );
				if ( $wrapper.find( '.price-unit:first' ).length > 0 )
					$wrapper.append( '<div class="org_unit_price org_product_info">' + $wrapper.find( '.price-unit:first' ).html() + '</div>' );
				if ( $wrapper.find( '.product-units:first' ).length > 0 )
					$wrapper.append( '<div class="org_product_units org_product_info">' + $wrapper.find( '.product-units:first' ).html() + '</div>' );
				$( '.org_product_info' ).hide();
			}
			if ( variation.price_html != '' ) {
				$( '.single_variation .price' ).hide();
				$wrapper.find( '.price:not(.price-unit):first' ).html( variation.price_html ).addClass( 'variation_modified' );
				$wrapper.find( '.price:not(.price-unit):first' ).find( ".price" ).contents().unwrap();
			}
			$wrapper.find( '.delivery-time-info:first' ).hide();
			$wrapper.find( '.price-unit:first' ).hide();
			$wrapper.find( '.tax-info:first' ).hide();
			$wrapper.find( '.product-units:first' ).hide();

			if ( variation.delivery_time != '' )
				$wrapper.find( 'p.delivery-time-info:first' ).html( variation.delivery_time ).addClass('variation_modified').show();
			if ( variation.tax_info != '' )
				$wrapper.find( '.tax-info:first' ).html( variation.tax_info ).addClass('variation_modified').show();
			if ( variation.unit_price != '' ) {
				$wrapper.find( '.price-unit:first' ).remove();
				$wrapper.find( 'div[itemprop="offers"]:first' ).after('<p class="price price-unit smaller variation_modified">' + variation.unit_price + '</p>').show();
			}
			if ( variation.product_units != '' ) {
				$wrapper.find( '.product-units:first' ).remove();
				$wrapper.find( '.product_meta' ).prepend('<span class="product-units-wrapper product-units variation_modified">' + variation.product_units + '</span>').show();
			}
		})

		// Check variations
		.on( 'update_variation_values', function( event, matching_variations ) {
			setTimeout(function() {
       		 	if ( ! $('.single_variation_wrap').is(':visible') || $( '.single_add_to_cart_button' ).is( '[disabled]' ) ) {
       		 		$.fn.wc_gzd_variation_form.reset_variation();
       		 	}
       		 }, 250);	
		})

		.on( 'click', '.reset_variations', function( event ) {
			$.fn.wc_gzd_variation_form.reset_variation();
		});

	};

	$( function() {

		// wc_add_to_cart_variation_params is required to continue, ensure the object exists
		if ( typeof wc_add_to_cart_variation_params === 'undefined' )
			return false;
		$( '.variations_form' ).wc_gzd_variation_form();
		$( '.variations_form .variations select' ).change();
		$( '.variations_form .variations input:radio:checked' ).change();
	});

})( jQuery, window, document );