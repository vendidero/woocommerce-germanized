jQuery( function ( $ ) {

	$( document ).on( 'change', 'input[name=woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_integration_mode]', function() {
		if ( $( this ).val() === 'expert' && $( this ).is( ':checked' ) ) {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_trustbadge_code' ).parents( 'tr' ).show();
			// Show notice
			$( '.wc-' + trusted_shops_params.script_prefix + 'trusted-shops-expert-mode-note' ).appendTo( $( this ).parents( 'td' ) ).show();
		} else {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_trustbadge_code' ).parents( 'tr' ).hide();
			$( '.wc-' + trusted_shops_params.script_prefix + 'trusted-shops-expert-mode-note' ).hide();
		}
		$( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_enable_reviews' ).trigger( 'change' );
	});

	$( document ).on( 'change', '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_enable_reviews', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_enable' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_enable' ).parents( 'tr' ).show();
		} else {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_enable' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_enable' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_enable' ).removeAttr( 'checked' );
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_enable' ).removeAttr( 'checked' );
		}
		$( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_enable' ).trigger( 'change' );
		$( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_enable' ).trigger( 'change' );
	});

	$( document ).on( 'change', '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_enable', function() {

		$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_code' ).parents( 'tr' ).hide();

		if ( $( this ).is( ':checked' ) ) {
			
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_border_color' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_star_color' ).parents( 'tr' ).show();
			
			if ( $( document ).find( 'input[name=woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_integration_mode]:checked' ).val() === 'expert' ) {
				$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_code' ).parents( 'tr' ).show();
			}

		} else {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_border_color' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_star_color' ).parents( 'tr' ).hide();
		}
	});

	$( document ).on( 'change', '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_enable', function() {

		$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_code' ).parents( 'tr' ).hide();

		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_star_color' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_star_size' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_font_size' ).parents( 'tr' ).show();

			if ( $( document ).find( 'input[name=woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_integration_mode]:checked' ).val() === 'expert' ) {
				$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_code' ).parents( 'tr' ).show();
			}

		} else {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_star_color' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_star_size' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_font_size' ).parents( 'tr' ).hide();
		}
	});

	$( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_integration_mode' ).trigger( 'change' );
	$( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_enable_reviews' ).trigger( 'change' );

});