window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.shipments = {

        params: {},
        shipments: {},
        $wrapper: false,
        needsSaving: false,
        needsShipments: true,
        needsReturns: false,

        init: function() {
            var self            = germanized.admin.shipments;
            self.params         = wc_gzd_admin_shipments_params;
            self.$wrapper       = $( '#panel-order-shipments' );
            self.needsShipments = self.$wrapper.find( '#order-shipment-add' ).is( ':visible' );
            self.needsReturns   = self.$wrapper.find( '#order-return-shipment-add' ).is( ':visible' );

            self.initShipments();

            // Listen to AJAX Events to allow running actions after Woo saved/added/removed order items.
            $( document ).ajaxComplete( self.onAjaxComplete );

            $( document )
                .on( 'click', '#order-shipments-list .shipment-header', self.onToggleShipment )
                .on( 'change', '#order-shipments-list :input:visible', self.setNeedsSaving )
                .on( 'click', '#panel-order-shipments #order-shipment-add', self.onAddShipment )
                .on( 'click', '#panel-order-shipments #order-return-shipment-add', self.onAddReturn )
                .on( 'click', '#panel-order-shipments .remove-shipment', self.onRemoveShipment )
                .on( 'click', '#panel-order-shipments button#order-shipments-save', self.onSave )
                .on( 'click', '#panel-order-shipments .notice-dismiss', self.onRemoveNotice );

            $( document.body )
                .on( 'wc_backbone_modal_loaded', self.backbone.init )
                .on( 'wc_backbone_modal_response', self.backbone.response );
        },

        onAjaxComplete: function( e, jqXHR, settings ) {
            var self = germanized.admin.shipments;

            if ( jqXHR != null ) {

                if ( settings.hasOwnProperty( 'data' ) ) {
                    var search = settings.data;
                    var data   = false;

                    try {
                        data = JSON.parse('{"' + search.replace(/&/g, '","').replace(/=/g,'":"') + '"}', function( key, value ) { return key==="" ? value:decodeURIComponent( value ) });
                    } catch (e) {
                        data = false;
                    }

                    if ( data && data.hasOwnProperty( 'action' ) ) {
                        var action = data.action;

                        if (
                            'woocommerce_save_order_items' === action
                            || 'woocommerce_remove_order_item' === action
                            || 'woocommerce_add_order_item' === action
                            || 'woocommerce_delete_refund' === action
                        ) {
                            self.syncItemQuantities();
                        }
                    }
                }
            }
        },

        syncItemQuantities: function() {
            var self = germanized.admin.shipments;

            self.block();

            var params = {
                'action': 'woocommerce_gzd_validate_shipment_item_quantities',
                'active': self.getActiveShipmentId()
            };

            self.doAjax( params, self.onSyncSuccess );
        },

        onSyncSuccess: function( data ) {
            var self = germanized.admin.shipments;

            self.unblock();
            self.initShipments();

            // Init tiptip
            self.initTiptip();
        },

        onSave: function( e ) {
            var self = germanized.admin.shipments;

            e.preventDefault();

            self.save();

            return false;
        },

        save: function() {
            var self = germanized.admin.shipments;

            self.block();

            var params = {
                'action': 'woocommerce_gzd_save_shipments',
                'active': self.getActiveShipmentId()
            };

            self.doAjax( params, self.onSaveSuccess );
        },

        initShipment: function( id ) {
            var self = germanized.admin.shipments;

            if ( ! self.shipments.hasOwnProperty( id ) ) {
                self.shipments[ id ] = new $.GermanizedShipment( id );
            } else {
                self.shipments[ id ].refreshDom();
            }
        },

        onSaveSuccess: function( data ) {
            var self = germanized.admin.shipments;

            self.initShipments();
            self.setNeedsSaving( false );
            self.unblock();

            // Init tiptip
            self.initTiptip();
        },

        getActiveShipmentId: function() {
            var self      = germanized.admin.shipments,
                $shipment = self.$wrapper.find( '.order-shipment.active' );

            if ( $shipment.length > 0 ) {
                return $shipment.data( 'shipment' );
            }

            return false;
        },

        block: function() {
            var self = germanized.admin.shipments;

            self.$wrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        unblock: function() {
            var self = germanized.admin.shipments;

            self.$wrapper.unblock();
        },

        getData: function( additionalData ) {
            var self = germanized.admin.shipments,
                data = {};

            additionalData = additionalData || {};

            $.each( self.$wrapper.find( ':input[name]' ).serializeArray(), function( index, item ) {
                if ( item.name.indexOf( '[]' ) !== -1 ) {
                    item.name = item.name.replace( '[]', '' );
                    data[ item.name ] = $.makeArray( data[ item.name ] );
                    data[ item.name ].push( item.value );
                } else {
                    data[ item.name ] = item.value;
                }
            });

            $.extend( data, additionalData );

            return data;
        },

        doAjax: function( params, cSuccess, cError ) {
            var self             = germanized.admin.shipments,
                url              = self.params.ajax_url,
                $wrapper         = self.$wrapper,
                refreshFragments = true;

            $wrapper.find( '.notice-wrapper' ).empty();

            cSuccess = cSuccess || self.onAjaxSuccess;
            cError   = cError || self.onAjaxError;

            if ( params.hasOwnProperty( 'refresh_fragments' ) ) {
                refreshFragments = params['refresh_fragments'];
            }

            if ( ! params.hasOwnProperty( 'security' ) ) {
                params['security'] = self.params.edit_shipments_nonce;
            }

            if ( ! params.hasOwnProperty( 'order_id' ) ) {
                params['order_id'] = self.params.order_id;
            }

            params = self.getData( params );

            $.ajax({
                type: "POST",
                url:  url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {

                        active               = self.getShipment( self.getActiveShipmentId() );
                        current_packaging_id = false;

                        if ( active ) {
                            current_packaging_id = active.getShipment().find( '.shipment-packaging-select' ).val();
                        }

                        if ( refreshFragments ) {
                            if ( data.fragments ) {
                                $.each( data.fragments, function ( key, value ) {
                                    $( key ).replaceWith( value );
                                    $( key ).unblock();
                                } );
                            }
                        }

                        cSuccess.apply( $wrapper, [ data ] );

                        if ( data.hasOwnProperty( 'order_needs_new_shipments' ) ) {
                            self.setNeedsShipments( data.order_needs_new_shipments );
                        }

                        if ( data.hasOwnProperty( 'order_needs_new_returns' ) ) {
                            self.setNeedsReturns( data.order_needs_new_returns );
                        }

                        var shipmentData = data.hasOwnProperty( 'shipments' ) ? data.shipments : {};

                        $.each( self.getShipments(), function( shipmentId, shipment ) {

                            if ( shipmentData.hasOwnProperty( shipmentId ) ) {
                                shipment.setIsEditable( shipmentData[ shipmentId ].is_editable );
                                shipment.setNeedsItems( shipmentData[ shipmentId ].needs_items );
                                shipment.setWeight( shipmentData[ shipmentId ].weight );
                                shipment.setLength( shipmentData[ shipmentId ].length );
                                shipment.setWidth( shipmentData[ shipmentId ].width );
                                shipment.setHeight( shipmentData[ shipmentId ].height );
                                shipment.setTotalWeight( shipmentData[ shipmentId ].total_weight );

                                self.initShipment( shipmentId );
                            }
                        });

                        if ( ( data.hasOwnProperty( 'needs_refresh' ) || data.hasOwnProperty( 'needs_packaging_refresh' ) ) && data.hasOwnProperty( 'shipment_id' ) ) {
                            self.initShipment( data.shipment_id );

                            if ( data.hasOwnProperty( 'needs_packaging_refresh' ) ) {
                                active = self.getShipment( self.getActiveShipmentId() );

                                if ( active ) {
                                    // Refresh dimensions in case the packaging has changed
                                    new_packaging_id = active.getShipment().find( '.shipment-packaging-select' ).val();

                                    if ( new_packaging_id !== current_packaging_id ) {
                                        self.getShipment( data.shipment_id ).refreshDimensions();
                                    }
                                }
                            }
                        }
                    } else {
                        cError.apply( $wrapper, [ data ] );
                        self.unblock();

                        if ( data.hasOwnProperty( 'message' ) ) {
                            self.addNotice( data.message, 'error' );
                        } else if( data.hasOwnProperty( 'messages' ) ) {
                            $.each( data.messages, function( i, message ) {
                                self.addNotice( message, 'error' );
                            });
                        }
                    }
                },
                error: function( data ) {
                    cError.apply( $wrapper, [ data ] );
                    self.unblock();
                },
                dataType: 'json'
            });
        },

        onAjaxError: function( data ) {

        },

        onAjaxSuccess: function( data ) {

        },

        onRemoveNotice: function() {
            $( this ).parents( '.notice' ).slideUp( 150, function() {
                $( this ).remove();
            });
        },

        addNotice: function( message, noticeType ) {
            var self = germanized.admin.shipments;

            self.$wrapper.find( '.notice-wrapper' ).append( '<div class="notice is-dismissible notice-' + noticeType +'"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
        },

        getParams: function() {
            var self = germanized.admin.shipments;

            return self.params;
        },

        onRemoveShipment: function() {
            var self      = germanized.admin.shipments,
                $shipment = $( this ).parents( '.order-shipment' ),
                id        = $shipment.data( 'shipment' );

            var answer = window.confirm( self.getParams().i18n_remove_shipment_notice );

            if ( answer ) {
                self.removeShipment( id );
            }

            return false;
        },

        removeShipment: function( shipment_id ) {
            var self   = germanized.admin.shipments;

            var params = {
                'action'     : 'woocommerce_gzd_remove_shipment',
                'shipment_id': shipment_id
            };

            self.block();
            self.doAjax( params, self.onRemoveShipmentSuccess, self.onRemoveShipmentError );
        },

        onRemoveShipmentSuccess: function( data ) {
            var self        = germanized.admin.shipments,
                shipmentIds = Array.isArray( data['shipment_id'] ) ? data['shipment_id'] : [data['shipment_id']];

            $.each( shipmentIds, function( i, shipmentId ) {
                var $shipment = self.$wrapper.find( '#shipment-' + shipmentId );

                if ( $shipment.length > 0 ) {
                    if ( $shipment.hasClass( 'active' ) ) {
                        $shipment.find( '.shipment-content-wrapper' ).slideUp( 300, function() {
                            $shipment.removeClass( 'active' );
                            $shipment.remove();

                            self.initShipments();
                        });
                    } else {
                        $shipment.remove();
                    }
                }
            });

            self.initShipments();
            self.unblock();
        },

        onRemoveShipmentError: function( data ) {
            var self = germanized.admin.shipments;

            self.unblock();
        },

        onAddShipment: function() {
            var self = germanized.admin.shipments;

            self.addShipment();

            return false;
        },

        addShipment: function() {
            var self = germanized.admin.shipments;

            var params = {
                'action': 'woocommerce_gzd_add_shipment'
            };

            self.block();
            self.doAjax( params, self.onAddShipmentSuccess, self.onAddShipmentError );
        },

        onAddShipmentSuccess: function( data ) {
            var self = germanized.admin.shipments;

            if ( self.$wrapper.find( '.order-shipment.active' ).length > 0 ) {
                self.$wrapper.find( '.order-shipment.active' ).find( '.shipment-content-wrapper' ).slideUp( 300, function() {

                    self.$wrapper.find( '.order-shipment.active' ).removeClass( 'active' );
                    self.appendNewShipment( data );

                    self.initShipments();

                    // Init tiptip
                    self.initTiptip();
                    self.unblock();
                });
            } else {
                self.appendNewShipment( data );
                self.initShipments();

                // Init tiptip
                self.initTiptip();
                self.unblock();
            }
        },

        appendNewShipment: function( data ) {
            var self = germanized.admin.shipments;

            if ( 'simple' === data['new_shipment_type'] && self.$wrapper.find( '.panel-order-return-title' ).length > 0 ) {
                self.$wrapper.find( '.panel-order-return-title' ).before( data.new_shipment );
            } else {
                self.$wrapper.find( '#order-shipments-list' ).append( data.new_shipment );
            }
        },

        onAddShipmentError: function( data ) {

        },

        onAddReturn: function() {

            $( this ).WCBackboneModal({
                template: 'wc-gzd-modal-add-shipment-return'
            });

            return false;
        },

        addReturn: function( items ) {
            var self = germanized.admin.shipments;

            self.block();

            var params = {
                'action' : 'woocommerce_gzd_add_return_shipment'
            };

            $.extend( params, items );

            self.doAjax( params, self.onAddReturnSuccess, self.onAddReturnError );
        },

        onAddReturnSuccess: function( data ) {
            var self = germanized.admin.shipments;

            self.onAddShipmentSuccess( data );
        },

        onAddReturnError: function( data ) {
            var self = germanized.admin.shipments;

            self.onAddShipmentError( data );
        },

        setNeedsSaving: function( needsSaving ) {
            var self       = germanized.admin.shipments,
                shipmentId = self.getActiveShipmentId(),
                $shipment  = shipmentId ? self.getShipment( shipmentId ).getShipment() : false;

            if ( typeof needsSaving !== "boolean" ) {
                needsSaving = true;
            }

            self.needsSaving = needsSaving === true;

            if ( self.needsSaving ) {
                self.$wrapper.find( '#order-shipments-save' ).show();
            } else {
                self.$wrapper.find( '#order-shipments-save' ).hide();
            }

            if ( $shipment ) {
                if ( self.needsSaving ) {
                    self.disableCreateLabel( $shipment );
                } else {
                    self.enableCreateLabel( $shipment );
                }
            }
          
            if ( self.needsSaving ) {
                self.disableCreateLabel( $shipment );
            } else {
                self.enableCreateLabel( $shipment );
            }

            self.hideOrShowFooter();

            $( document.body ).trigger( 'woocommerce_gzd_shipments_needs_saving', [ self.needsSaving, self.getActiveShipmentId() ] );

            self.initTiptip();
        },

        disableCreateLabel: function( $shipment ) {
            var self    = germanized.admin.shipments,
                $button = $shipment.find( '.create-shipment-label' );

            if ( $button.length > 0 ) {
                $button.addClass( 'disabled button-disabled' );
                $button.prop( 'title', self.params.i18n_create_label_disabled );
            }
        },

        enableCreateLabel: function( $shipment ) {
            var self    = germanized.admin.shipments,
                $button = $shipment.find( '.create-shipment-label' );

            if ( $button.length > 0 ) {
                $button.removeClass( 'disabled button-disabled' );
                $button.prop( 'title', self.params.i18n_create_label_enabled );
            }
        },

        setNeedsShipments: function( needsShipments ) {
            var self = germanized.admin.shipments;

            if ( typeof needsShipments !== "boolean" ) {
                needsShipments = true;
            }

            self.needsShipments = needsShipments === true;

            if ( self.needsShipments ) {
                self.$wrapper.addClass( 'needs-shipments' );
                self.$wrapper.find( '#order-shipment-add' ).show();
            } else {
                self.$wrapper.removeClass( 'needs-shipments' );
                self.$wrapper.find( '#order-shipment-add' ).hide();
            }

            self.hideOrShowFooter();
        },

        hideOrShowReturnTitle: function() {
            var self = germanized.admin.shipments;

            if ( self.$wrapper.find( '.order-shipment.shipment-return' ).length === 0 ) {
                self.$wrapper.find( '.panel-order-return-title' ).addClass( 'hide-default' );
            } else {
                self.$wrapper.find( '.panel-order-return-title' ).removeClass( 'hide-default' );
            }
        },

        setNeedsReturns: function( needsReturns ) {
            var self = germanized.admin.shipments;

            if ( typeof needsReturns !== "boolean" ) {
                needsReturns = true;
            }

            self.needsReturns = needsReturns === true;

            if ( self.needsReturns ) {
                self.$wrapper.addClass( 'needs-returns' );
                self.$wrapper.find( '#order-return-shipment-add' ).show();
            } else {
                self.$wrapper.removeClass( 'needs-returns' );
                self.$wrapper.find( '#order-return-shipment-add' ).hide();
            }

            self.hideOrShowFooter();
        },

        hideOrShowFooter: function() {
            var self = germanized.admin.shipments;

            if ( self.needsSaving || self.needsShipments || self.needsReturns ) {
                self.$wrapper.find( '.panel-footer' ).slideDown( 300 );
            } else {
                self.$wrapper.find( '.panel-footer' ).slideUp( 300 );
            }
        },

        onToggleShipment: function() {
            var self      = germanized.admin.shipments,
                $shipment = $( this ).parents( '.order-shipment:first' ),
                isActive  = $shipment.hasClass( 'active' );

            self.closeShipments();

            if ( ! isActive ) {
                $shipment.find( '> .shipment-content-wrapper' ).slideDown( 300, function() {
                    $shipment.addClass( 'active' );
                });
            }
        },

        closeShipments: function() {
            var self = germanized.admin.shipments;

            self.$wrapper.find( '.order-shipment.active .shipment-content-wrapper' ).slideUp( 300, function() {
                self.$wrapper.find( '.order-shipment.active' ).removeClass( 'active' );
            });
        },

        initShipments: function() {
            var self = germanized.admin.shipments;

            // Refresh wrapper
            self.$wrapper = $( '#panel-order-shipments' );

            self.$wrapper.find( '.order-shipment' ).each( function() {
                var id = $( this ).data( 'shipment' );

                self.initShipment( id );
            });

            self.hideOrShowReturnTitle();
        },

        getShipments: function() {
            var self = germanized.admin.shipments;

            return self.shipments;
        },

        getShipment: function( shipment_id ) {
            var self      = germanized.admin.shipments,
                shipments = self.getShipments();

            if ( shipments.hasOwnProperty( shipment_id ) ) {
                return shipments[ shipment_id ];
            }

            return false;
        },

        refresh: function( shipment_id ) {

        },

        refreshItems: function( shipment_id ) {

        },

        addItem: function() {

        },

        initTiptip: function() {
            var self = germanized.admin.shipments;

            // Tooltips
            $( document.body ).trigger( 'init_tooltips' );

            self.$wrapper.find( '.woocommerce-help-tip' ).tipTip({
                'attribute': 'data-tip',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });

            self.$wrapper.find( '.create-shipment-label' ).tipTip( {
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            } );
        },

        backbone: {

            onAddReturnSuccess: function( data ) {
                $( '#wc-gzd-return-shipment-items' ).html( data.html );
                $( '.wc-backbone-modal-content article' ).unblock();

                $( document.body ).on( 'change', 'input.wc-gzd-shipment-add-return-item-quantity', function() {
                    var $select  = $( this ),
                        quantity = $select.val();

                    if ( $select.attr( 'max' ) ) {
                        var maxQuantity = $select.attr( 'max' );

                        if ( quantity > maxQuantity ) {
                            $select.val( maxQuantity );
                        }
                    }
                });
            },

            init: function ( e, target ) {
                var self = germanized.admin.shipments;

                if( ( 'wc-gzd-modal-add-shipment-return' ) === target ) {
                    $( '.wc-backbone-modal-content article' ).block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });

                    self.doAjax( {
                        'action' : 'woocommerce_gzd_get_available_return_shipment_items'
                    }, self.backbone.onAddReturnSuccess );

                    return false;
                }
            },

            response: function ( e, target, data ) {
                var self = germanized.admin.shipments;

                if( ( 'wc-gzd-modal-add-shipment-return' ) === target ) {
                    self.addReturn( data );
                }
            }
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipments.init();
    });

})( jQuery, window.germanized.admin );
