jQuery( function ( $ ) {

	var wc_gzd_product_variations_actions = {

		init: function() {
			$( '#woocommerce-product-data' ).on( 'click', '.woocommerce_variation', this.show_or_hide_unit_variation );
			$( '#general_product_data' ).on( 'blur', 'input#_unit_base', this.show_or_hide_unit_variation );
			$( '#general_product_data' ).on( 'change', 'select#_unit', this.show_or_hide_unit_variation );
			
			$( document ).bind( 'woocommerce_variations_save_variations_button', this.save_variations );
			$( document ).bind( 'woocommerce_variations_save_variations_on_submit', this.save_variations );
		},

		save_variations: function() {

			var fields = [ 'unit', 'unit_base', 'unit_product' ];
			var variations = $( '.woocommerce_variations' ).find( '.woocommerce_variation' );
			
			$.each( fields, function( index, id ) {
				var parent_val = $( '#_' + id ).val();

				variations.each( function() {
					$( this ).find( '.wc-gzd-parent-' + id ).val( parent_val );
				});
			});

		},

		show_or_hide_unit_variation: function() {
			if ( wc_gzd_product_variations_actions.is_variable() ) {
				
				$( '.variable_pricing_unit' ).hide();

				if ( ! wc_gzd_product_variations_actions.has_unit_price() && wc_gzd_product_variations_actions.has_unit() ) {

					$( '.variable_pricing_unit' ).show();
					$( '.variable_pricing_unit .form-row' ).hide();
					$( '.variable_pricing_unit' ).find( 'input[name*=variable_unit_product]' ).parents( '.form-row' ).show();

				} else if ( wc_gzd_product_variations_actions.has_unit_price() ) {

					$( '.variable_pricing_unit .form-row' ).show();
					$( '.variable_pricing_unit' ).show();
				
				}

			}
		},

		is_variable: function() {
			return $( 'select#product-type' ).val() === 'variable';
		},

		has_unit: function() {
			return $( '#_unit' ).val() !== '0';
		},

		has_unit_price: function() {
			return $( '#_unit' ).val() !== '0' && $( '#_unit_base' ).val().length !== 0;
		}

	};

	wc_gzd_product_variations_actions.init();

});