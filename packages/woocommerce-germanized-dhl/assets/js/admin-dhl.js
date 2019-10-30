window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.dhl = {

        params: {},

        init: function () {
            var self    = germanized.admin.dhl;
            self.params = wc_gzd_admin_dhl_params;

            $( document )
                .on( 'click', '#panel-order-shipments .create-shipment-label:not(.disabled)', self.onCreateLabel )
                .on( 'click', '#panel-order-shipments .remove-shipment-label', self.onRemoveLabel )
                .on( 'click', '#panel-order-shipments .send-shipment-label', self.onSendLabel );

            $( document.body )
                .on( 'woocommerce_gzd_shipments_needs_saving', self.onShipmentsNeedsSavingChange )
                .on( 'init_tooltips', self.initTip );

            self.initTip();
        },

        initTip: function() {
            $( '.create-shipment-label' ).tipTip( {
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            } );
        },

        onShipmentsNeedsSavingChange: function( e, needsSaving, currentShipmentId ) {
            var self      = germanized.admin.dhl,
                $shipment = self.getShipment( currentShipmentId );

            if ( needsSaving ) {
                self.disableCreateLabel( $shipment );
            } else {
                self.enableCreateLabel( $shipment );
            }
        },

        disableCreateLabel: function( $shipment ) {
            var self    = germanized.admin.dhl,
                $button =  $shipment.find( '.create-shipment-label' );

            $button.addClass( 'disabled button-disabled' );
            $button.prop( 'title', self.params.i18n_create_label_disabled );

            // Tooltips
            $( document.body ).trigger( 'init_tooltips' );
        },

        enableCreateLabel: function( $shipment ) {
            var self    = germanized.admin.dhl,
                $button =  $shipment.find( '.create-shipment-label' );

            $button.removeClass( 'disabled button-disabled' );
            $button.prop( 'title', self.params.i18n_create_label_enabled );

            // Tooltips
            $( document.body ).trigger( 'init_tooltips' );
        },

        getShipmentWrapperByLabel: function( labelId ) {
            var self       = germanized.admin.dhl,
                $wrapper   = $( '.wc-gzd-shipment-dhl-label[data-label="' + labelId + '"]' );

            if ( $wrapper.length > 0 ) {
                return $wrapper.parents( '.order-shipment:first' );
            }

            return false;
        },

        getShipmentIdByLabel: function( labelId ) {
            var self       = germanized.admin.dhl,
                $wrapper   = $( '.wc-gzd-shipment-dhl-label[data-label="' + labelId + '"]' );

            if ( $wrapper.length > 0 ) {
                return $wrapper.parents( '.order-shipment' ).data( 'shipment' );
            }

            return false;
        },

        removeLabel: function( labelId ) {
            var self       = germanized.admin.dhl,
                $wrapper   = self.getShipmentWrapperByLabel( labelId );

            var params = {
                'action'  : 'woocommerce_gzd_remove_dhl_label',
                'label_id': labelId,
                'security': self.params.remove_label_nonce
            };

            if ( $wrapper ) {
                self.doAjax( params, $wrapper );
            }
        },

        onRemoveLabel: function() {
            var self       = germanized.admin.dhl,
                labelId    = $( this ).data( 'label' );

            var answer = window.confirm( self.params.i18n_remove_label_notice );

            if ( answer ) {
                self.removeLabel( labelId );
            }

            return false;
        },

        sendLabel: function( labelId ) {
            var self       = germanized.admin.dhl,
                $wrapper   = self.getShipmentWrapperByLabel( labelId );

            var params = {
                'action'  : 'woocommerce_gzd_dhl_email_return_label',
                'label_id': labelId,
                'security': self.params.send_label_nonce
            };

            if ( $wrapper ) {
                self.doAjax( params, $wrapper, self.onSendLabelSuccess );
            }
        },

        onSendLabelSuccess: function( data ) {
            var shipments = germanized.admin.shipments;

            $.each( data.messages, function( i, message ) {
                shipments.addNotice( message, 'success' );
            });
        },

        onSendLabel: function() {
            var self       = germanized.admin.dhl,
                labelId    = $( this ).data( 'label' );

            self.sendLabel( labelId );

            return false;
        },

        doAjax: function( params, $wrapper, cSuccess, cError  ) {
            var self       = germanized.admin.dhl,
                shipments  = germanized.admin.shipments,
                $shipment  = $wrapper.hasClass( 'order-shipment' ) ? $wrapper : $wrapper.parents( '.order-shipment:first' ),
                shipmentId = $shipment.data( 'shipment' );

            cSuccess = cSuccess || self.onAjaxSuccess;
            cError   = cError || self.onAjaxError;

            if ( ! params.hasOwnProperty( 'security' ) ) {
                params['security'] = self.params.edit_label_nonce;
            }

            if ( ! params.hasOwnProperty( 'shipment_id' ) ) {
                params['shipment_id'] = shipmentId;
            }

            $shipment.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $shipment.find( '.notice-wrapper' ).empty();

            $.ajax({
                type: "POST",
                url:  self.params.ajax_url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        $shipment.unblock();

                        if ( data.fragments ) {
                            $.each( data.fragments, function ( key, value ) {
                                $( key ).replaceWith( value );
                            });
                        }

                        cSuccess.apply( $shipment, [ data ] );
                    } else {
                        cError.apply( $shipment, [ data ] );

                        $shipment.unblock();

                        if ( data.hasOwnProperty( 'message' ) ) {
                           shipments.addNotice( data.message, 'error' );
                        } else if( data.hasOwnProperty( 'messages' ) ) {
                            $.each( data.messages, function( i, message ) {
                                shipments.addNotice( message, 'error' );
                            });
                        }
                    }
                },
                error: function( data ) {},
                dataType: 'json'
            });
        },

        onAjaxSuccess: function( data ) {

        },

        onAjaxError: function( data ) {

        },

        getShipment: function( id ) {
            return $( '#panel-order-shipments' ).find( '#shipment-' + id );
        },

        onCreateLabel: function() {
            var self       = germanized.admin.dhl,
                shipmentId = $( this ).parents( '.order-shipment' ).data( 'shipment' );

            self.getShipment( shipmentId ).WCBackboneModal({
                template: 'wc-gzd-modal-create-shipment-label-' + shipmentId
            });

            return false;
        }
    };

    $( document ).ready( function() {
        germanized.admin.dhl.init();
    });

})( jQuery, window.germanized.admin );
