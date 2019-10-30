window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    $.GermanizedShipment = function( shipmentId ) {

        /*
         * Variables accessible
         * in the class
         */
        this.vars = {
            $shipment    : false,
            params       : {},
            id           : '',
            isEditable   : true,
            isReturnable : true,
            needsItems   : true
        };

        /*
         * Can access this.method
         * inside other methods using
         * root.method()
         */
        this.root = this;

        /*
         * Constructor
         */
        this.construct = function( shipmentId ) {
            this.vars.id        = shipmentId;
            this.vars.params    = germanized.admin.shipments.getParams();

            this.refreshDom();

            $( document.body )
                .on( 'wc_backbone_modal_loaded', this.backbone.init.bind( this ) )
                .on( 'wc_backbone_modal_response', this.backbone.response.bind( this ) );
        };

        this.refreshDom = function() {
            this.vars.$shipment  = $( '#order-shipments-list' ).find( '#shipment-' + this.getId() );

            this.setNeedsItems( this.vars.$shipment.hasClass( 'needs-items' ) );
            this.setIsEditable( this.vars.$shipment.hasClass( 'is-editable' ) );
            this.setIsReturnable( this.vars.$shipment.hasClass( 'is-returnable' ) );

            $( '#shipment-' + this.vars.id + ' #shipment-items-' + this.vars.id ).off();
            $( '#shipment-' + this.vars.id + ' #shipment-footer-' + this.vars.id ).off();

            $( '#shipment-' + this.vars.id + ' #shipment-items-' + this.vars.id )
                .on( 'change', '.item-quantity', this.onChangeQuantity.bind( this ) )
                .on( 'click', 'a.remove-shipment-item', this.onRemoveItem.bind( this ) )
                .on( 'click', 'a.add-shipment-item', this.onAddItem.bind( this ) )
                .on( 'click', 'a.sync-shipment-items', this.onSyncItems.bind( this ) );

            $( '#shipment-' + this.vars.id + ' #shipment-footer-' + this.vars.id )
                .on( 'click', 'a.add-shipment-return', this.onAddReturn.bind( this ) );
        };

        this.getShipment = function() {
            return this.vars.$shipment;
        };

        this.getShipmentContent = function() {
            return this.vars.$shipment.find( '> .shipment-content-wrapper > .shipment-content > .columns > div:not(.shipment-returns-data)' );
        };

        this.onChangeQuantity = function( e ) {
            var $quantity   = $( e.target ),
                $item       = $quantity.parents( '.shipment-item' ),
                itemId      = $item.data( 'id' ),
                newQuantity = $quantity.val();

            this.blockItems();

            var params = {
                'action'       : 'woocommerce_gzd_limit_shipment_item_quantity',
                'shipment_id'  : this.getId(),
                'item_id'      : itemId,
                'quantity'     : newQuantity
            };

            germanized.admin.shipments.doAjax( params, this.onChangeQuantitySuccess.bind( this ) );
        };

        this.onChangeQuantitySuccess = function( data ) {
            var $item       = this.getShipment().find( '.shipment-item[data-id="' + data.item_id + '"]' ),
                currentQty  = $item.find( '.item-quantity' ).val(),
                maxQuantity = data.max_quantity;

            if ( currentQty > maxQuantity ) {
                $item.find( '.item-quantity' ).val( maxQuantity );
            } else if ( currentQty <= 0 ) {
                $item.find( '.item-quantity' ).val( 1 );
            }

            this.refreshDom();
            this.unblockItems();
        };

        this.setWeight = function( weight ) {
            this.getShipment().find( '#shipment-weight-' + this.getId() ).attr( 'placeholder', weight );
        };

        this.setLength = function( length ) {
            this.getShipment().find( '#shipment-length-' + this.getId() ).attr( 'placeholder', length );
        };

        this.setWidth = function( width ) {
            this.getShipment().find( '#shipment-width-' + this.getId() ).attr( 'placeholder', width );
        };

        this.setHeight = function( height ) {
            this.getShipment().find( '#shipment-height-' + this.getId() ).attr( 'placeholder', height );
        };

        this.setIsReturnable = function( isReturnable ) {
            var root = this;

            if ( typeof isReturnable !== "boolean" ) {
                isReturnable = true;
            }

            this.vars.isReturnable = isReturnable === true;

            if ( ! this.vars.isReturnable ) {
                this.getShipment().removeClass( 'is-returnable' );
            } else {
                this.getShipment().addClass( 'is-returnable' );
            }
        };

        this.setIsEditable = function( isEditable ) {
            var root = this;

            if ( typeof isEditable !== "boolean" ) {
                isEditable = true;
            }

            this.vars.isEditable = isEditable === true;

            if ( ! this.vars.isEditable ) {
                this.getShipment().removeClass( 'is-editable' );
                this.getShipment().addClass( 'is-locked' );

                // Disable inputs
                this.getShipmentContent().find( '.remove-shipment-item ' ).hide();
                this.getShipmentContent().find( '.shipment-item-actions' ).hide();
                this.getShipmentContent().find( ':input:not([type=hidden])' ).prop( 'disabled', true );

                $.each( this.vars.params.shipment_locked_excluded_fields, function( i, field ) {
                    root.getShipmentContent().find( ':input[name^=shipment_' + field + ']' ).prop( 'disabled', false );
                });

            } else {
                this.getShipment().addClass( 'is-editable' );
                this.getShipment().removeClass( 'is-locked' );

                // Disable inputs
                this.getShipmentContent().find( '.remove-shipment-item ' ).show();
                this.getShipmentContent().find( '.shipment-item-actions' ).show();
                this.getShipmentContent().find( ':input:not([type=hidden])' ).prop( 'disabled', false );
            }
        };

        this.setNeedsItems = function( needsItems ) {
            if ( typeof needsItems !== "boolean" ) {
                needsItems = true;
            }

            this.vars.needsItems = needsItems === true;

            if ( ! this.vars.needsItems ) {
                this.getShipment().removeClass( 'needs-items' );
            } else {
                this.getShipment().addClass( 'needs-items' );
            }
        };

        this.onSyncItems = function() {
            this.syncItems();

            return false;
        };

        this.syncItems = function() {
            this.blockItems();

            var params = {
                'action'     : 'woocommerce_gzd_sync_shipment_items',
                'shipment_id': this.getId()
            };

            germanized.admin.shipments.doAjax( params, this.onSyncItemsSuccess.bind( this ), this.onSyncItemsError.bind( this ) );
        };

        this.onSyncItemsSuccess = function( data ) {
            this.unblockItems();
        };

        this.onSyncItemsError = function( data ) {
            this.unblockItems();
        };

        this.onAddItem = function() {

            this.getShipment().WCBackboneModal({
                template: 'wc-gzd-modal-add-shipment-item-' + this.getId()
            });

            return false;
        };

        this.onAddReturn = function() {

            this.getShipment().WCBackboneModal({
                template: 'wc-gzd-modal-add-shipment-return-' + this.getId()
            });

            return false;
        };

        this.addItem = function( orderItemId, quantity ) {
            quantity = quantity || 1;

            this.blockItems();

            var params = {
                'action'           : 'woocommerce_gzd_add_shipment_item',
                'shipment_id'      : this.getId(),
                'original_item_id' : orderItemId,
                'quantity'         : quantity
            };

            germanized.admin.shipments.doAjax( params, this.onAddItemSuccess.bind( this ), this.onAddItemError.bind( this ) );
        };

        this.addReturn = function( items ) {
            this.block();

            var params = {
                'action'       : 'woocommerce_gzd_add_shipment_return',
                'shipment_id'  : this.getId()
            };

            $.extend( params, items );

            germanized.admin.shipments.doAjax( params, this.onAddReturnSuccess.bind( this ), this.onAddReturnError.bind( this ) );
        };

        this.onAddReturnSuccess = function( data ) {
            this.getShipment().find( '.shipment-return-list' ).append( data.new_shipment );

            this.refreshDom();
            germanized.admin.shipments.initShipments();

            this.unblock();
        };

        this.onAddReturnError = function( data ) {
            this.unblock();
        };

        this.onAddItemError = function( data ) {
            this.unblockItems();
        };

        this.onAddItemSuccess = function( data ) {
            this.getShipmentContent().find( '.shipment-item-list' ).append( data.new_item );

            this.refreshDom();
            this.unblockItems();
        };

        this.onRemoveItem = function( e ) {
            var $delete = $( e.target ),
                $item   = $delete.parents( '.shipment-item' ),
                itemId  = $item.data( 'id' );

            if ( $item.length > 0 ) {
                this.removeItem( itemId );
            }

            return false;
        };

        this.blockItems = function() {
            this.getShipmentContent().find( '.shipment-items' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };

        this.block = function() {
            this.getShipment().block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };

        this.unblockItems = function() {
            this.getShipmentContent().find( '.shipment-items' ).unblock();
        };

        this.unblock = function() {
            this.getShipment().unblock();
        };

        this.removeItem = function( itemId ) {
            var $item = this.getShipment().find( '.shipment-item[data-id="' + itemId + '"]' );

            var params = {
                'action'       : 'woocommerce_gzd_remove_shipment_item',
                'shipment_id'  : this.getId(),
                'item_id'      : itemId
            };

            this.blockItems();

            germanized.admin.shipments.doAjax( params, this.onRemoveItemSuccess.bind( this ) );
        };

        this.onRemoveItemSuccess = function( data ) {
            var $item = this.getShipment().find( '.shipment-item[data-id="' + data['item_id'] + '"]' );

            if ( $item.length > 0 ) {
                $item.slideUp( 150, function() {
                    $( this ).remove();
                });
            }

            this.unblockItems();
        };

        this.getId = function() {
            return this.vars.id;
        };

        this.backbone = {

            onAddItemSuccess: function( data ) {
                $select   = $( 'select#wc-gzd-shipment-add-items-select' );
                $quantity = $( 'input#wc-gzd-shipment-add-items-quantity' );

                $quantity.val( 1 );

                $.each( data.items, function( id, item ) {
                    $select.append( '<option value="' + id + '">' + item.name + '</option>' );
                    $quantity.data( 'max-quantity-' + id, item.max_quantity );
                });

                $( '.wc-backbone-modal-content article' ).unblock();

                $( document.body ).on( 'change', 'input#wc-gzd-shipment-add-items-quantity', function() {
                    var item_id  = $select.val(),
                        quantity = $( this ).val();

                    if ( $quantity.data( 'max-quantity-' + item_id ) ) {
                        var maxQuantity = $quantity.data( 'max-quantity-' + item_id );

                        if ( quantity > maxQuantity ) {
                            $quantity.val( maxQuantity );
                        }
                    }
                });
            },

            onAddReturnSuccess: function( data ) {
                $( '#wc-gzd-return-shipment-items' ).html( data.html );
                $( '.wc-backbone-modal-content article' ).unblock();

                $( document.body ).on( 'change', 'input.wc-gzd-shipment-add-return-item-quantity', function() {
                    var item_id  = $select.val(),
                        quantity = $( this ).val();

                    if ( $quantity.data( 'max-quantity-' + item_id ) ) {
                        var maxQuantity = $quantity.data( 'max-quantity-' + item_id );

                        if ( quantity > maxQuantity ) {
                            $quantity.val( maxQuantity );
                        }
                    }
                });
            },

            init: function ( e, target ) {
                var id = this.getId();

                if ( ( 'wc-gzd-modal-add-shipment-item-' + id ) === target ) {

                    $( '.wc-backbone-modal-content article' ).block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });

                    germanized.admin.shipments.doAjax( {
                        'action'     : 'woocommerce_gzd_get_shipment_available_items',
                        'shipment_id': id
                    }, this.backbone.onAddItemSuccess.bind( this ) );

                    return false;
                } else if( ( 'wc-gzd-modal-add-shipment-return-' + id ) === target ) {
                    $( '.wc-backbone-modal-content article' ).block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });

                    germanized.admin.shipments.doAjax( {
                        'action'     : 'woocommerce_gzd_get_shipment_available_return_items',
                        'shipment_id': id
                    }, this.backbone.onAddReturnSuccess.bind( this ) );

                    return false;
                }
            },

            response: function ( e, target, data ) {
                var id = this.getId();

                if ( ( 'wc-gzd-modal-add-shipment-item-' + id ) === target ) {
                    this.addItem( data.item_id, data.item_qty );
                } else if( ( 'wc-gzd-modal-add-shipment-return-' + id ) === target ) {
                    this.addReturn( data );
                }
            }
        };

        /*
         * Pass options when class instantiated
         */
        this.construct( shipmentId );
    };

})( jQuery, window.germanized.admin );
