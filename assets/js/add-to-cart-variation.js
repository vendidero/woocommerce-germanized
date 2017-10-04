/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

	$.fn.wc_gzd_variation_form = function () {

		var $form 	 	= this,
			$wrapper 	= $form.parents( wc_gzd_add_to_cart_variation_params.wrapper );

		$.fn.wc_gzd_variation_form.reset_variation = function() {

			if ( $wrapper.find('.org_price').length > 0 ) {
				$wrapper.find('.price.variation_modified:not(.price-unit)' ).html( $wrapper.find('.org_price').html() ).removeClass('variation_modified').show();
			}
			if ( $wrapper.find('.org_delivery_time').length > 0 ) {
                $wrapper.find( '.delivery-time-info:first' ).html( $wrapper.find('.org_delivery_time').html() ).removeClass('variation_modified').show();
			}
			if ( $wrapper.find('.org_unit_price').length > 0 ) {
                $wrapper.find('.price-unit:first' ).html( $wrapper.find('.org_unit_price').html() ).removeClass('variation_modified').show();
			}
			if ( $wrapper.find('.org_tax_info').length > 0 ) {
                $wrapper.find('.tax-info:first' ).html( $wrapper.find('.org_tax_info').html() ).removeClass('variation_modified').show();
			}
			if ( $wrapper.find('.org_shipping_costs_info').length > 0 ) {
                $wrapper.find('.shipping-costs-info:first' ).html( $wrapper.find('.org_shipping_costs_info').html() ).removeClass('variation_modified').show();
			}
			if ( $wrapper.find('.org_product_units').length > 0 ) {
                $wrapper.find('.product-units:first' ).html( $wrapper.find('.org_product_units').html() ).removeClass('variation_modified').show();
			}

			$('.org_product_info').remove();
			$('.variation_modified').remove();
		};

		$form

		.on( 'found_variation', function( event, variation ) {

            if ( ! variation.variation_is_visible )
            	return;

			if ( ! $wrapper.find( '.price:first' ).hasClass( 'variation_modified' ) ) {
				$wrapper.append( '<div class="org_price org_product_info">' + $wrapper.find( '.price:not(.price-unit):first' ).html() + '</div>' );
				if ( $wrapper.find( '.delivery-time-info:first' ).length > 0 ) {
					$wrapper.append( '<div class="org_delivery_time org_product_info">' + $wrapper.find( '.delivery-time-info:first' ).html() + '</div>' );
				}
				if ( $wrapper.find( '.tax-info:first' ).length > 0 ) {
					$wrapper.append( '<div class="org_tax_info org_product_info">' + $wrapper.find( '.tax-info:first' ).html() + '</div>' );
				}
				if ( $wrapper.find( '.shipping-costs-info:first' ).length > 0 ) {
					$wrapper.append( '<div class="org_shipping_costs_info org_product_info">' + $wrapper.find( '.shipping-costs-info:first' ).html() + '</div>' );
				}
				if ( $wrapper.find( '.price-unit:first' ).length > 0 ) {
					$wrapper.append( '<div class="org_unit_price org_product_info">' + $wrapper.find( '.price-unit:first' ).html() + '</div>' );
				}
				if ( $wrapper.find( '.product-units:first' ).length > 0 ) {
					$wrapper.append( '<div class="org_product_units org_product_info">' + $wrapper.find( '.product-units:first' ).html() + '</div>' );
				}
				$( '.org_product_info' ).hide();
			}

			if ( variation.price_html !== '' ) {
				$( '.single_variation .price' ).hide();
				$wrapper.find( '.price:not(.price-unit):first' ).html( variation.price_html ).addClass( 'variation_modified' );
				$wrapper.find( '.price:not(.price-unit):first' ).find( '.price' ).contents().unwrap();
			}

			$wrapper.find( '.delivery-time-info:first' ).hide();
			$wrapper.find( '.price-unit:first' ).hide();
			$wrapper.find( '.tax-info:first' ).hide();
			$wrapper.find( '.shipping-costs-info:first' ).hide();
			$wrapper.find( '.product-units:first' ).hide();

			if ( variation.delivery_time !== '' ) {
				$wrapper.find( 'p.delivery-time-info:first' ).html( variation.delivery_time ).addClass('variation_modified').show();
			}
			if ( variation.tax_info !== '' ) {
				$wrapper.find( '.tax-info:first' ).html( variation.tax_info ).addClass('variation_modified').show();
			}
			if ( variation.shipping_costs_info !== '' ) {
				$wrapper.find( '.shipping-costs-info:first' ).html( variation.shipping_costs_info ).addClass('variation_modified').show();
			}
			if ( variation.unit_price !== '' ) {
			    // Check if unit price for variable product exists and replace instead of insert
				if ( $wrapper.find( '.price-unit:first' ).length ) {
                    $wrapper.find( '.price-unit:first' ).html( variation.unit_price ).addClass('variation-modified').show();
                } else {
                    $wrapper.find( '.price-unit:first' ).remove();
                    $wrapper.find( 'p.price:first' ).after('<p class="price price-unit smaller variation_modified">' + variation.unit_price + '</p>').show();
                }
			}
			if ( variation.product_units !== '' ) {
                // Check if product units for variable product exist and replace instead of insert
                if ( $wrapper.find( '.product-units:first' ).length ) {
                    $wrapper.find( '.product-units:first' ).html( variation.product_units ).addClass('variation-modified').show();
                } else {
                    $wrapper.find( '.product-units:first' ).remove();
                    $wrapper.find( '.product_meta:first' ).prepend('<span class="product-units-wrapper product-units variation_modified">' + variation.product_units + '</span>').show();
                }
			}
		})

		// Check variations
		.on( 'update_variation_values', function() {
			setTimeout(function() {
       		 	if ( ! $('.single_variation_wrap').is(':visible') || $( '.single_add_to_cart_button' ).is( '[disabled]' ) ) {
       		 		$.fn.wc_gzd_variation_form.reset_variation();
       		 	}
       		 }, 250);	
		})

		.on( 'click', '.reset_variations', function() {
			$.fn.wc_gzd_variation_form.reset_variation();
		})

		.on( 'reset_data', function() {
			$.fn.wc_gzd_variation_form.reset_variation();
		});

	};

	$( function() {

		// wc_add_to_cart_variation_params is required to continue, ensure the object exists
		if ( typeof wc_add_to_cart_variation_params === 'undefined' ) {
			return false;
		}
		
		$( '.variations_form' ).wc_gzd_variation_form();
		$( '.variations_form .variations select' ).change();
		$( '.variations_form .variations input:radio:checked' ).change();
	});

})( jQuery, window, document );