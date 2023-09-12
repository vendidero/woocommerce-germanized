/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {

    germanized.unit_price_observer_queue = {
        queue: {},
        timeout: null,
        params: {},
        request: null,

        init: function() {
            this.params = wc_gzd_unit_price_observer_queue_params;
            this.queue = {};
            this.timeout = null;
            this.request = null;
        },

        execute: function() {
            var self = germanized.unit_price_observer_queue,
                data = [],
                currentQueue = { ...self.queue };

            self.queue = {};
            self.timeout = null;

            Object.keys( currentQueue ).forEach( function( key ) {
                data.push({
                    'product_id': currentQueue[ key ].productId,
                    'price'     : currentQueue[ key ].priceData.price,
                    'price_sale': currentQueue[ key ].priceData.sale_price,
                    'quantity'  : currentQueue[ key ].priceData.quantity,
                });
            });

            self.request = $.ajax({
                type: "POST",
                url:  self.params.wc_ajax_url.toString().replace( '%%endpoint%%', 'gzd_refresh_unit_price' ),
                data: {
                    'security'  : self.params.refresh_unit_price_nonce,
                    'products'  : data,
                },
                success: function( data ) {
                    Object.keys( currentQueue ).forEach( function( queueId ) {
                        var current = currentQueue[ queueId ],
                            observer = current.observer,
                            priceData = current.priceData,
                            priceSelector = current.priceSelector,
                            isPrimary = current.isPrimary;

                        if ( observer ) {
                            if ( data.products.hasOwnProperty( queueId ) ) {
                                var response = data.products[ queueId ];

                                observer.stopObserver( observer, priceSelector );

                                /**
                                 * Do only adjust unit price in case current product id has not changed
                                 * in the meantime (e.g. variation change).
                                 */
                                if ( parseInt( response.product_id ) === observer.getCurrentProductId( observer ) ) {
                                    if ( response.hasOwnProperty( 'unit_price_html' ) ) {
                                        observer.unsetUnitPriceLoading( observer, priceData.unit_price, response.unit_price_html );
                                    } else {
                                        observer.unsetUnitPriceLoading( observer, priceData.unit_price );
                                    }
                                } else {
                                    observer.unsetUnitPriceLoading( observer, priceData.unit_price );
                                }

                                observer.startObserver( observer, priceSelector, isPrimary );
                            } else {
                                observer.stopObserver( observer, priceSelector );
                                observer.unsetUnitPriceLoading( observer, priceData.unit_price );
                                observer.startObserver( observer, priceSelector, isPrimary );
                            }
                        }
                    });

                    Object.keys( data.products ).forEach( function( responseProductId ) {
                        if ( currentQueue.hasOwnProperty( responseProductId ) ) {
                            var current = currentQueue[ responseProductId ],
                                $unitPrice = $( current.priceData.unit_price );

                            if ( $unitPrice.data( 'unitPriceObserver' ) ) {
                                $unitPrice.data( 'unitPriceObserver' )
                            }
                        }
                    });
                },
                error: function() {
                    Object.keys( currentQueue ).forEach( function( queueId ) {
                        var current = currentQueue[ queueId ],
                            observer = current.observer,
                            priceData = current.priceData,
                            priceSelector = current.priceSelector,
                            isPrimary = current.isPrimary;

                        if ( observer ) {
                            observer.stopObserver( observer, priceSelector );
                            observer.unsetUnitPriceLoading( observer, priceData.unit_price );
                            observer.startObserver( observer, priceSelector, isPrimary );
                        }
                    });
                },
                dataType: 'json'
            } );
        },

        add: function( observer, productId, priceData, priceSelector, isPrimary ) {
            var self = germanized.unit_price_observer_queue;

            self.queue[ productId ] = {
                'productId'    : productId,
                'observer'     : observer,
                'priceData'    : priceData,
                'priceSelector': priceSelector,
                'isPrimary'    : isPrimary
            };

            clearTimeout( self.timeout );
            self.timeout = setTimeout( self.execute, 500 );
        },
    };

    $( document ).ready( function() {
        germanized.unit_price_observer_queue.init();
    });

})( jQuery, window.germanized );