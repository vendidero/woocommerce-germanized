window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, shipments ) {

    /**
     * Core
     */
    shipments.admin.shipments_table = {
        params: {},

        init: function() {
            var self    = shipments.admin.shipments_table;
            self.params = wc_shiptastic_admin_shipments_table_params;

            self.initEnhanced();

            $( document ).on( 'click', '#doaction, #doaction2', self.onBulkSubmit );

            $( '.has-shipment-modal' ).wc_shiptastic_admin_shipment_modal();

            $( document.body ).on( 'init_tooltips', function() {
                self.initTipTip();
            });

            self.initTipTip();
        },

        onBulkSubmit: function() {
            var self   = shipments.admin.shipments_table,
                action = $( this ).parents( '.bulkactions' ).find( 'select[name^=action]' ).val(),
                type   = $( this ).parents( '#posts-filter' ).find( 'input.shipment_type' ).val(),
                ids    = [];

            $( '#posts-filter' ).find( 'input[name="shipment[]"]:checked' ).each( function() {
                ids.push( $( this ).val() );
            });

            if ( self.params.bulk_actions.hasOwnProperty( action ) && ids.length > 0 ) {
                var actionData = self.params.bulk_actions[ action ];

                $( '.bulk-action-wrapper' ).find( '.bulk-title' ).text( actionData['title'] );
                $( '#posts-filter' ).addClass( 'bulk-action-processing' );
                $( '#posts-filter' ).find( '.bulkactions button' ).prop( 'disabled', true );

                // Handle bulk action processing
                self.handleBulkAction( action, 1, ids, type );

                return false;
            }
        },

        handleBulkAction: function( action, step, ids, type ) {
            var self       = shipments.admin.shipments_table,
                actionData = self.params.bulk_actions[ action ];

            $.ajax( {
                type: 'POST',
                url: self.params.ajax_url,
                data: {
                    action           : 'woocommerce_stc_shipments_bulk_action_handle',
                    bulk_action      : action,
                    step             : step,
                    type             : type,
                    referer          : window.location.href,
                    ids              : ids,
                    security         : actionData['nonce']
                },
                dataType: 'json',
                success: function( response ) {
                    if ( response.success ) {

                        if ( 'done' === response.data.step ) {
                            $( '.bulk-action-wrapper' ).find( '.woocommerce-shimpents-bulk-progress' ).val( response.data.percentage );

                            window.location = response.data.url;

                            setTimeout( function() {
                                $( '#posts-filter' ).removeClass( 'bulk-action-processing' );
                                $( '#posts-filter' ).find( '.bulkactions button' ).prop( 'disabled', false );
                            }, 2000 );
                        } else {
                            $( '.bulk-action-wrapper' ).find( '.woocommerce-shimpents-bulk-progress' ).val( response.data.percentage );
                            self.handleBulkAction( action, parseInt( response.data.step, 10 ), response.data.ids, response.data.type );
                        }
                    }
                }
            }).fail( function( response ) {
                window.console.log( response );
            } );
        },

        initTipTip: function() {
            $( '.column-actions .wc-stc-shipment-action-button' ).tipTip( {
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
        },

        initEnhanced: function() {
            try {
                $( document.body )
                    .on( 'wc-enhanced-select-init', function() {
                        // Ajax order search boxes
                        $( ':input.wc-stc-order-search' ).filter( ':not(.enhanced)' ).each( function() {
                            var select2_args = {
                                allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                                placeholder: $( this ).data( 'placeholder' ),
                                minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
                                escapeMarkup: function( m ) {
                                    return m;
                                },
                                ajax: {
                                    url:         wc_shiptastic_admin_shipments_table_params.ajax_url,
                                    dataType:    'json',
                                    delay:       1000,
                                    data:        function( params ) {
                                        return {
                                            term:     params.term,
                                            action:   'woocommerce_stc_json_search_orders',
                                            security: wc_shiptastic_admin_shipments_table_params.search_orders_nonce,
                                            exclude:  $( this ).data( 'exclude' )
                                        };
                                    },
                                    processResults: function( data ) {
                                        var terms = [];
                                        if ( data ) {
                                            $.each( data, function( id, text ) {
                                                terms.push({
                                                    id: id,
                                                    text: text
                                                });
                                            });
                                        }
                                        return {
                                            results: terms
                                        };
                                    },
                                    cache: true
                                }
                            };

                            $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
                        });

                        $( ':input.wc-stc-shipping-provider-search' ).filter( ':not(.enhanced)' ).each( function() {
                            var select2_args = {
                                allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                                placeholder: $( this ).data( 'placeholder' ),
                                minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
                                escapeMarkup: function( m ) {
                                    return m;
                                },
                                ajax: {
                                    url:         wc_shiptastic_admin_shipments_table_params.ajax_url,
                                    dataType:    'json',
                                    delay:       1000,
                                    data:        function( params ) {
                                        return {
                                            term:     params.term,
                                            action:   'woocommerce_stc_json_search_shipping_provider',
                                            security: wc_shiptastic_admin_shipments_table_params.search_shipping_provider_nonce,
                                            exclude:  $( this ).data( 'exclude' )
                                        };
                                    },
                                    processResults: function( data ) {
                                        var terms = [];
                                        if ( data ) {
                                            $.each( data, function( id, text ) {
                                                terms.push({
                                                    id: id,
                                                    text: text
                                                });
                                            });
                                        }
                                        return {
                                            results: terms
                                        };
                                    },
                                    cache: true
                                }
                            };

                            $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
                        });
                    });

                $( 'html' ).on( 'click', function( event ) {
                    if ( this === event.target ) {
                        $( ':input.wc-stc-order-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
                        $( ':input.wc-stc-shipping-provider-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
                    }
                } );
            } catch( err ) {
                // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
                window.console.log( err );
            }
        }
    };

    $( document ).ready( function() {
        shipments.admin.shipments_table.init();
    });

})( jQuery, window.shiptastic );
