window.germanized = window.germanized || {};

( function( $, germanized ) {

    germanized.single_product = {

        params: {},
        requests: [],
        productId: 0,
        variationId: 0,

        init: function() {
            var self = germanized.single_product;

            self.params    = wc_gzd_single_product_params;
            self.productId = self.params.product_id;

            /**
             * Setup accounting jQuery script
             */
            accounting.settings = {
                currency: {
                    decimal : self.params.price_decimal_sep,
                    thousand: self.params.price_thousand_sep,
                },
                number: {
                    decimal : self.params.price_decimal_sep,
                    thousand: self.params.price_thousand_sep,
                }
            }

            $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' ).bind( 'DOMSubtreeModified', self.onChangePrice );

            /**
             * Maybe update variationId to make sure we are transmitting the
             * right product id to the AJAX call.
             */
            if ( $( '.variations_form' ).length > 0 ) {
                $( '.variations_form' ).each( function() {
                    var $form = $( this );

                    $form.on( 'reset_data', self.onResetVariation );
                    $form.on( 'found_variation.wc-variation-form', self.onFoundVariation );
                });
            }
        },

        onFoundVariation: function( event, variation ) {
            var self = germanized.single_product;

            if ( variation.hasOwnProperty( 'variation_id' ) ) {
                self.variationId = variation.variation_id;
            }
        },

        onResetVariation: function() {
            var self = germanized.single_product;

            self.variationId = 0;
        },

        onChangePrice: function( event ) {
            /**
             * Need to use a tweak here to make sure our variation listener
             * has already adjusted the variationId (in case necessary).
             */
            setTimeout(function() {
                var self  = germanized.single_product,
                    $price = $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' );

                event.preventDefault();

                /**
                 * Unbind the event because using :first selectors will trigger DOMSubtreeModified again (infinite loop)
                 */
                $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' ).unbind( 'DOMSubtreeModified', self.onChangePrice );

                var $unit_price = $price.parents( self.params.wrapper ).find( '.price-unit:first' ),
                    price       = accounting.unformat( $price.find( '.amount:first' ).text() ),
                    sale_price  = '';

                /**
                 * Is sale?
                 */
                if ( $price.find( '.amount' ).length > 1 ) {
                    sale_price = accounting.unformat( $price.find( '.amount:last' ).text() );
                }

                if ( $unit_price.length > 0 && price ) {
                    self.refreshUnitPrice( price, $unit_price, sale_price );
                }

                /**
                 * Rebind the event
                 */
                $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' ).bind( 'DOMSubtreeModified', self.onChangePrice );
            }, 500 );
        },

        refreshUnitPrice: function( price, $unit_price, sale_price ) {
            var self = germanized.single_product;

            /**
             * Cancel requests
             */
            if ( self.requests.length > 0 ) {
                for ( var i = 0; i < self.requests.length; i++ ) {
                    self.requests[i].abort();
                }
            }

            self.requests.push( $.ajax({
                type: "POST",
                url:  self.params.wc_ajax_url.toString().replace( '%%endpoint%%', 'gzd_refresh_unit_price' ),
                data: {
                    'security': self.params.refresh_unit_price_nonce,
                    'product_id': self.variationId > 0 ? self.variationId : self.productId,
                    'price': price,
                    'price_sale': sale_price
                },
                success: function( data ) {
                    if ( data.hasOwnProperty( 'unit_price_html' ) ) {
                        $unit_price.html( data.unit_price_html );
                    }
                },
                error: function( data ) {},
                dataType: 'json'
            } ) );
        }
    }

    $( document ).ready( function() {
        germanized.single_product.init();
    });

})( jQuery, window.germanized );