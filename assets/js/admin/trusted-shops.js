jQuery( function ( $ ) {

	$( document ).on( 'change', '#woocommerce_gzd_trusted_shops_expert_mode', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_gzd_trusted_shops_trustbadge_code' ).parents( 'tr' ).show();
		} else {
			$( '#woocommerce_gzd_trusted_shops_trustbadge_code' ).parents( 'tr' ).hide();
		}
		$( document ).find( '#woocommerce_gzd_trusted_shops_enable_reviews' ).trigger( 'change' );
	});

	$( document ).on( 'change', '#woocommerce_gzd_trusted_shops_enable_reviews', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_gzd_trusted_shops_product_sticker_enable' ).parents( 'tr' ).show();
			$( '#woocommerce_gzd_trusted_shops_product_widget_enable' ).parents( 'tr' ).show();
		} else {
			$( '#woocommerce_gzd_trusted_shops_product_sticker_enable' ).parents( 'tr' ).hide();
			$( '#woocommerce_gzd_trusted_shops_product_widget_enable' ).parents( 'tr' ).hide();
		}
		$( document ).find( '#woocommerce_gzd_trusted_shops_product_sticker_enable' ).trigger( 'change' );
		$( document ).find( '#woocommerce_gzd_trusted_shops_product_widget_enable' ).trigger( 'change' );
	});

	$( document ).on( 'change', '#woocommerce_gzd_trusted_shops_product_sticker_enable', function() {

		$( '#woocommerce_gzd_trusted_shops_product_sticker_code' ).parents( 'tr' ).hide();

		if ( $( this ).is( ':checked' ) ) {
			
			$( '#woocommerce_gzd_trusted_shops_product_sticker_border_color' ).parents( 'tr' ).show();
			$( '#woocommerce_gzd_trusted_shops_product_sticker_star_color' ).parents( 'tr' ).show();
			
			if ( $( document ).find( '#woocommerce_gzd_trusted_shops_expert_mode' ).is( ':checked' ) ) {
				$( '#woocommerce_gzd_trusted_shops_product_sticker_code' ).parents( 'tr' ).show();
			}

		} else {
			$( '#woocommerce_gzd_trusted_shops_product_sticker_border_color' ).parents( 'tr' ).hide();
			$( '#woocommerce_gzd_trusted_shops_product_sticker_star_color' ).parents( 'tr' ).hide();
		}
	});

	$( document ).on( 'change', '#woocommerce_gzd_trusted_shops_product_widget_enable', function() {

		$( '#woocommerce_gzd_trusted_shops_product_widget_code' ).parents( 'tr' ).hide();

		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_gzd_trusted_shops_product_widget_star_color' ).parents( 'tr' ).show();
			$( '#woocommerce_gzd_trusted_shops_product_widget_star_size' ).parents( 'tr' ).show();
			$( '#woocommerce_gzd_trusted_shops_product_widget_font_size' ).parents( 'tr' ).show();

			if ( $( document ).find( '#woocommerce_gzd_trusted_shops_expert_mode' ).is( ':checked' ) ) {
				$( '#woocommerce_gzd_trusted_shops_product_widget_code' ).parents( 'tr' ).show();
			}

		} else {
			$( '#woocommerce_gzd_trusted_shops_product_widget_star_color' ).parents( 'tr' ).hide();
			$( '#woocommerce_gzd_trusted_shops_product_widget_star_size' ).parents( 'tr' ).hide();
			$( '#woocommerce_gzd_trusted_shops_product_widget_font_size' ).parents( 'tr' ).hide();
		}
	});

	$( document ).find( '#woocommerce_gzd_trusted_shops_expert_mode' ).trigger( 'change' );
	$( document ).find( '#woocommerce_gzd_trusted_shops_enable_reviews' ).trigger( 'change' );

});