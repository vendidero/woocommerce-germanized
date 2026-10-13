window.eu_owb = window.eu_owb || {};
window.eu_owb.admin_order = window.eu_owb.admin_order || {};

( function( $, eu_owb ) {
    /**
     * Core
     */
    eu_owb.admin_order = {
        params: {},

        init: function () {
            var self  = eu_owb.admin_order;
            self.params = eu_owb_woocommerce_admin_order_params;

            $( document ).on( 'click', '.eu-owb-reject-withdrawal-request-start', self.onRejectRequest );
            $( document ).on( 'click', '.eu-owb-woocommerce-needs-confirmation', self.onConfirm );
            $( document ).on( 'click', '.eu-owb-order-withdrawal-order-save', self.onSave );
            $( document ).on( 'click', '.eu-owb-order-toggle-order-search', self.onToggleInlineEdit );
            $( document ).on( 'click', 'a.wc-action-button-reject', self.onToggleInlineEdit );

            self.initEnhanced();
        },

        onToggleInlineEdit: function( e ) {
            var self = eu_owb.admin_order,
                $parent = $( this ).parents( 'td' );

            e.preventDefault();

            $parent.find( '.eu-owb-order-inline-edit-wrapper' ).toggleClass( 'hidden' );

            return false;
        },

        onSave: function () {
            var self = eu_owb.admin_order,
                saveType = $( this ).data( 'save' ),
                id = $( this ).data( 'id' ),
                $parent = $( this ).parents( 'tr' ),
                $this = $( this );

            var value = $parent.find( '#' + saveType + '_' + id ).val();
            let props = {}

            props[ saveType ] = value;

            const data = {
                order_id: id,
                action:   'eu_owb_woocommerce_save_withdrawal_order',
                security: self.params.save_order_nonce,
                props: props,
                inline_action: $this.data( 'action' ) ? $this.data( 'action' ) : 'edit'
            };

            $this.addClass( 'eu-owb-woocommerce-is-loading disabled' );
            $this.append( '<span class="spinner is-active"></span>' );
            $this.prop( 'disabled', true );

            $.ajax( {
                type: 'POST',
                url: self.params.ajax_url,
                data: data,
                dataType: 'json',
            }).done( function ( response ) {
                $this.removeClass( 'eu-owb-woocommerce-is-loading disabled' );
                $this.find( '.spinner' ).remove();
                $this.prop( 'disabled', false );
                $this.addClass( 'eu-owb-woocommerce-success' );

                setTimeout( () => {
                    window.location.reload();
                }, 50 );
            }).fail( function ( xhr ) {
                $this.removeClass( 'eu-owb-woocommerce-is-loading disabled' );
                $this.find( '.spinner' ).remove();
                $this.prop( 'disabled', false );
            });

            return false;
        },

        initEnhanced: function() {
            var self = eu_owb.admin_order;

            try {
                $( document.body )
                    .on( 'wc-enhanced-select-init', function() {
                        // Ajax order search boxes
                        $( ':input.eu-owb-order-search' ).filter( ':not(.enhanced)' ).each( function() {
                            var select2_args = {
                                allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                                placeholder: $( this ).data( 'placeholder' ),
                                minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
                                escapeMarkup: function( m ) {
                                    return m;
                                },
                                ajax: {
                                    url:         self.params.ajax_url,
                                    dataType:    'json',
                                    delay:       1000,
                                    data:        function( params ) {
                                        return {
                                            term:     params.term,
                                            action:   'eu_owb_woocommerce_json_search_orders',
                                            security: self.params.search_orders_nonce,
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
                        $( ':input.eu-owb-order-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
                    }
                } );
            } catch( err ) {
                // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
                window.console.log( err );
            }
        },

        onConfirm: function( e ) {
            var self = eu_owb.admin_order,
                msg = $( this ).data( 'confirm' );

            if ( ! confirm( msg ) ) {
                e.preventDefault();
            }
        },

        onRejectRequest: function() {
            var self = eu_owb.admin_order,
                $wrapper = $( this ).parents( '.eu-owb-order-withdrawal-request' );

            $wrapper.find( '.eu-owb-reject-withdrawal-request-form' ).toggleClass( 'hidden' );
            $wrapper.find( '#eu_owb_reject_reason' ).focus();

            return false;
        },
    };

    $( document ).ready( function() {
        eu_owb.admin_order.init();
    });
})( jQuery, window.eu_owb );
