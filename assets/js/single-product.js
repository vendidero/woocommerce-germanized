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

            $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' ).on( 'DOMSubtreeModified', self.onChangePrice );

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

        getCurrentPriceData: function() {
            var self  = germanized.single_product,
                $price = $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' );

            if ( $price.length > 0 ) {
                var $unit_price = $price.parents( self.params.wrapper ).find( '.price-unit:first' ),
                    price       = self.getRawPrice( $price.find( '.amount:first' ) ),
                    sale_price  = '';

                /**
                 * Is sale?
                 */
                if ( $price.find( '.amount' ).length > 1 ) {
                    sale_price = self.getRawPrice( $price.find( '.amount:last' ) );
                }

                if ( $unit_price.length > 0 && price ) {
                    return {
                        'price': price,
                        'unit_price': $unit_price,
                        'sale_price': sale_price
                    };
                }
            }

            return false;
        },

        onChangePrice: function( event ) {
            /**
             * Need to use a tweak here to make sure our variation listener
             * has already adjusted the variationId (in case necessary).
             */
            setTimeout(function() {
                var self  = germanized.single_product,
                    priceData = self.getCurrentPriceData();

                event.preventDefault();

                if ( priceData ) {
                    /**
                     * Unbind the event because using :first selectors will trigger DOMSubtreeModified again (infinite loop)
                     */
                    $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' ).off( 'DOMSubtreeModified', self.onChangePrice );

                    /**
                     * In case AJAX requests are still running (e.g. price refreshes of other plugins) wait for them to finish
                     */
                    if ( $.active > 0 ) {
                        /**
                         * Do not listen to our own requests
                         */
                        if ( self.requests.length <= 0 ) {
                            $ ( document ).on( 'ajaxStop', self.onAjaxStopRefresh );
                        }
                    } else {
                        self.refreshUnitPrice( priceData.price, priceData.unit_price, priceData.sale_price );
                    }
                }
            }, 500 );
        },

        onAjaxStopRefresh: function( e ) {
            var self = germanized.single_product;
            var priceData = self.getCurrentPriceData();

            if ( priceData ) {
                self.refreshUnitPrice( priceData.price, priceData.unit_price, priceData.sale_price );
            }

            $ ( document ).off( 'ajaxStop', self.onAjaxStopRefresh );
        },

        getRawPrice: function( $el ) {
            var self      = germanized.single_product,
                price_raw = $el.length > 0 ? $el.text() : '',
                price     = false;

            try {
                price = accounting.unformat( price_raw, self.params.price_decimal_sep );
            } catch (e) {
                price = false;
            }

            return price;
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

                    /**
                     * Rebind the event
                     */
                    $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' ).on( 'DOMSubtreeModified', self.onChangePrice );
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