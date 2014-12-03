/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

	function reset_variation() {
		if ( $('.product').find('.org_price').length > 0 ) {
			$( '.product .summary div > p.price' ).html( $('.product').find('.org_price').html() ).removeClass('variation_modified').show();
		}
		if ( $('.product').find('.org_delivery_time').length > 0 ) {
			$( '.product .summary .delivery-time-info' ).html( $('.product').find('.org_delivery_time').html() ).removeClass('variation_modified').show();
		}
		if ( $('.product').find('.org_unit_price').length > 0 ) {
			$( '.product .summary p.unit-price' ).html( $('.product').find('.org_unit_price').html() ).removeClass('variation_modified').show();
		}
		$('.org_product_info').remove();
		$('.variation_modified').remove();
	}

	$.fn.wc_gzd_variation_form = function () {

		$form = this

		.on( 'found_variation', function( event, variation ) {
			if ( ! $('.product .summary p.price').hasClass('variation_modified') ) {
				$('.product .summary').append( '<div class="org_price org_product_info">' + $('.product .summary p.price').html() + '</div>' );
				if ( $( '.product .summary .delivery-time-info' ).length > 0 ) {
					$('.product .summary').append( '<div class="org_delivery_time org_product_info">' + $('.product .summary p.delivery-time-info').html() + '</div>' );
				}
				if ( $( '.product .summary .price-unit' ).length > 0 )
					$('.product .summary').append( '<div class="org_unit_price org_product_info">' + $('.product .summary p.price-unit').html() + '</div>' );
				$('.org_product_info').hide();
			}
			if ( variation.price_html != '' ) {
				$('.single_variation .price').hide();
				$('.product .summary p.price').html( variation.price_html ).addClass('variation_modified');
			}
			$('.product .summary p.delivery-time-info').hide();
			$('.product .summary .price-unit').hide();
			if ( variation.delivery_time != '' )
				$('p.delivery-time-info').html( variation.delivery_time ).addClass('variation_modified').show();
			if ( variation.unit_price != '' ) {
				$('.product .summary .price-unit').remove();
				$('.summary .offer').after('<p class="price price-unit smaller variation_modified">' + variation.unit_price + '</p>').show();
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