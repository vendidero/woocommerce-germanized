/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

	function reset_variation() {

		if ( $('.type-product').find('.org_price').length > 0 ) {
			$( '.type-product .price.variation_modified:not(.price-unit)' ).html( $('.type-product').find('.org_price').html() ).removeClass('variation_modified').show();
		}
		if ( $('.type-product').find('.org_delivery_time').length > 0 ) {
			$( '.type-product .delivery-time-info' ).html( $('.type-product').find('.org_delivery_time').html() ).removeClass('variation_modified').show();
		}
		if ( $('.type-product').find('.org_unit_price').length > 0 ) {
			$( '.type-product .unit-price' ).html( $('.product').find('.org_unit_price').html() ).removeClass('variation_modified').show();
		}
		$('.org_product_info').remove();
		$('.variation_modified').remove();
	}

	$.fn.wc_gzd_variation_form = function () {

		$form = this
		$wrapper = $form.parents( '.type-product' )

		.on( 'found_variation', function( event, variation ) {
			if ( ! $wrapper.find( '.price:first' ).hasClass( 'variation_modified' ) ) {
				$wrapper.append( '<div class="org_price org_product_info">' + $wrapper.find( '.price:not(.price-unit):first' ).html() + '</div>' );
				if ( $wrapper.find( '.delivery-time-info:first' ).length > 0 )
					$wrapper.append( '<div class="org_delivery_time org_product_info">' + $wrapper.find( '.delivery-time-info:first' ).html() + '</div>' );
				if ( $wrapper.find( '.price-unit:first' ).length > 0 )
					$wrapper.append( '<div class="org_unit_price org_product_info">' + $wrapper.find( '.price-unit:first' ).html() + '</div>' );
				$( '.org_product_info' ).hide();
			}
			if ( variation.price_html != '' ) {
				$( '.single_variation .price' ).hide();
				$wrapper.find( '.price:not(.price-unit):first' ).html( variation.price_html ).addClass( 'variation_modified' );
			}
			$wrapper.find( '.delivery-time-info:first' ).hide();
			$wrapper.find( '.price-unit:first' ).hide();
			if ( variation.delivery_time != '' )
				$wrapper.find( 'p.delivery-time-info:first' ).html( variation.delivery_time ).addClass('variation_modified').show();
			if ( variation.unit_price != '' ) {
				$wrapper.find( '.price-unit:first' ).remove();
				$wrapper.find( 'div[itemprop="offers"]:first' ).after('<p class="price price-unit smaller variation_modified">' + variation.unit_price + '</p>').show();
			}
		})

		// Check variations
		.on( 'update_variation_values', function( event, matching_variations ) {
			setTimeout(function() {
       		 	if ( ! $('.single_variation_wrap').is(':visible') ) {
       		 		reset_variation();
       		 	}
       		 }, 250);	
		})

		.on( 'click', '.reset_variations', function( event ) {
			reset_variation();
		});

	};

	$( function() {

		// wc_add_to_cart_variation_params is required to continue, ensure the object exists
		if ( typeof wc_add_to_cart_variation_params === 'undefined' )
			return false;
		$( '.variations_form' ).wc_gzd_variation_form();
		$( '.variations_form .variations select' ).change();
	});

})( jQuery, window, document );