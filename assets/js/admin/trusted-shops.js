jQuery( function ( $ ) {

	$( document ).on( 'click', '#wc-gzd-trusted-shops-export', function() {
		var href_org = $( this ).data( 'href-org' );
		$( this ).attr( 'href', href_org + '&interval=' + $( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_review_collector' ).val() ); 
	});

	$( document ).on( 'change', '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_integration_mode', function() {
		
		// Hide gateway options
		$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_gateway_bacs' ).parents( 'table.form-table' ).hide();
		$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_gateway_bacs' ).parents( 'table' ).prev( 'h3,h2' ).hide();

		if ( $( this ).val() === 'expert' ) {

			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_gateway_bacs' ).parents( 'table.form-table' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_gateway_bacs' ).parents( 'table' ).prev( 'h3,h2' ).show();

			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_trustbadge_code' ).parents( 'tr' ).show();
			// Show notice
			$( '.wc-gzd-trusted-shops-expert-mode-note' ).appendTo( $( this ).parents( 'td' ) ).show();
		} else {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_trustbadge_code' ).parents( 'tr' ).hide();
			$( '.wc-gzd-trusted-shops-expert-mode-note' ).hide();
		}
		$( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_enable_reviews' ).trigger( 'change' );
	});

	$( document ).on( 'change', '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_enable_reviews', function() {
		
		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_gtin_attribute' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_brand_attribute' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_mpn_attribute' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_enable' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_enable' ).parents( 'tr' ).show();
		} else {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_gtin_attribute' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_brand_attribute' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_mpn_attribute' ).parents( 'tr' ).hide();
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
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_star_size' ).parents( 'tr' ).show();
			
			if ( $( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_integration_mode' ).val() === 'expert' ) {
				$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_code' ).parents( 'tr' ).show();
			}

		} else {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_border_color' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_star_color' ).parents( 'tr' ).hide();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_sticker_star_size' ).parents( 'tr' ).hide();
		}
	});

	$( document ).on( 'change', '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_enable', function() {

		$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_code' ).parents( 'tr' ).hide();

		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_star_color' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_star_size' ).parents( 'tr' ).show();
			$( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_product_widget_font_size' ).parents( 'tr' ).show();

			if ( $( document ).find( '#woocommerce_' + trusted_shops_params.option_prefix + 'trusted_shops_integration_mode' ).val() === 'expert' ) {
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