window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, window, document, shipments ) {

    var AdminShipmentModal = function( $modalTrigger ) {
        var self = this;

        self.params        = wc_shiptastic_admin_shipment_modal_params;
        self.$modalTrigger = $modalTrigger;

        self.destroy();
        self.setup();

        self.$modalTrigger.on( 'click.wc-stc-modal-' + self.modalId, { adminShipmentModal: self }, self.onClick )

        $( document.body )
            .on( 'wc_backbone_modal_loaded.wc-stc-modal-' + self.modalId, { adminShipmentModal: self }, self.onOpen )
            .on( 'wc_backbone_modal_response.wc-stc-modal-' + self.modalId, { adminShipmentModal: self }, self.response )
            .on( 'wc_backbone_modal_before_remove.wc-stc-modal-' + self.modalId, { adminShipmentModal: self }, self.onClose );
    };

    AdminShipmentModal.prototype.setup = function() {
        var self = this;

        self.referenceId   = self.$modalTrigger.data( 'reference' ) ? self.$modalTrigger.data( 'reference' ) : 0;
        self.modalClass    = self.$modalTrigger.data( 'id' );
        self.modalId       = self.modalClass + '-' + self.referenceId;
        self.loadAsync     = self.$modalTrigger.data( 'load-async' ) ? self.$modalTrigger.data( 'load-async' ) : false;
        self.nonceParams   = self.$modalTrigger.data( 'nonce-params' ) ? self.$modalTrigger.data( 'nonce-params' ) : 'wc_shiptastic_admin_shipments_params';
        self.$modal        = false;

        self.$modalTrigger.data( 'self', this );
    };

    AdminShipmentModal.prototype.destroy = function() {
        var self = this;

        self.$modalTrigger.off( '.wc-stc-modal-' + self.modalId );

        $( document ).off( '.wc-stc-modal-' + self.modalId );
        $( document.body ).off( '.wc-stc-modal-' + self.modalId );
    };

    AdminShipmentModal.prototype.getShipment = function( id ) {
        return $( '#panel-order-shipments' ).find( '#shipment-' + id );
    };

    AdminShipmentModal.prototype.onRemoveNotice = function( event ) {
        var self = event.data.adminShipmentModal;

        $( this ).parents( '.notice' ).slideUp( 150, function() {
            $( this ).remove();
        });

        return false;
    };

    AdminShipmentModal.prototype.onClick = function( event ) {
        var self = event.data.adminShipmentModal;

        self.$modalTrigger.WCBackboneModal({
            template: self.modalId
        });

        return false;
    };

    AdminShipmentModal.prototype.parseFieldId = function( fieldId ) {
        return fieldId.replace( '[', '_' ).replace( ']', '' );
    };

    AdminShipmentModal.prototype.onExpandMore = function( event ) {
        var self = event.data.adminShipmentModal,
            $wrapper = self.$modal.find( '.show-more-wrapper' ),
            $triggerWrapper = $( this ).parents( '.show-more-trigger' );

        $wrapper.show();
        $wrapper.find( ':input:visible' ).trigger( 'change', [self] );

        $triggerWrapper.find( '.show-more' ).hide();
        $triggerWrapper.find( '.show-fewer' ).show();

        return false;
    };

    AdminShipmentModal.prototype.onHideMore = function( event ) {
        var self = event.data.adminShipmentModal,
            $wrapper = self.$modal.find( '.show-more-wrapper' ),
            $triggerWrapper = $( this ).parents( '.show-more-trigger' );

        $wrapper.hide();

        $triggerWrapper.find( '.show-further-services' ).show();
        $triggerWrapper.find( '.show-fewer-services' ).hide();

        return false;
    };

    AdminShipmentModal.prototype.onChangeField = function( event ) {
        var self     = event.data.adminShipmentModal,
            $wrapper = self.$modal,
            fieldId  = self.parseFieldId( $( this ).attr( 'id' ) ),
            val      = $( this ).val();

        /**
         * Limit max quantity
         */
        if ( $( this ).attr( 'max' ) ) {
            var maxQuantity = $( this ).attr( 'max' );

            if ( val > maxQuantity ) {
                $( this ).val( maxQuantity );
            }
        }

        /**
         * Limit min quantity
         */
        if ( $( this ).attr( 'min' ) ) {
            var minQuantity = $( this ).attr( 'min' );

            if ( val < minQuantity ) {
                $( this ).val( minQuantity );
            }
        }

        /**
         * Show or hide a wrapper based on checkbox status
         */
        if ( $( this ).hasClass( 'show-if-trigger' ) ) {
            var $show = $wrapper.find( $( this ).data( 'show-if' ) );

            if ( $show.length > 0 ) {
                if ( $( this ).is( ':checked' ) ) {
                    $show.show();
                } else {
                    $show.hide();
                }

                $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_show_if', [self] );
                self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_show_if', [self] );
            }
        } else {
            $wrapper.find( ':input[data-show-if-' + fieldId + ']' ).parents( '.form-field' ).hide();

            if ( $( this ).is( ':visible' ) ) {
                if ( $( this ).is( ':checkbox' ) ) {
                    if ( $( this ).is( ':checked' ) ) {
                        $wrapper.find( ':input[data-show-if-' + fieldId + ']' ).parents( '.form-field' ).show();
                    }
                } else {
                    if ( '0' !== val && '' !== val ) {
                        $wrapper.find( ':input[data-show-if-' + fieldId + '=""]' ).parents( '.form-field' ).show();
                    }

                    $wrapper.find( ':input[data-show-if-' + fieldId + '*="' + val + '"]' ).parents( '.form-field' ).show();
                }
            }

            // Make sure to propagate show/if logic by firing a change event on the shown/hidden input.
            $wrapper.find( ':input[data-show-if-' + fieldId + ']' ).trigger( 'change' );

            self.showMoreWrapper();
        }
    };

    AdminShipmentModal.prototype.showMoreWrapper = function() {
        var $wrapper = this.$modal;

        /**
         * Hide the show more trigger in case no visible field is found within the wrapper.
         * Explicitly check for the display style as the wrapper may be in collapsed state.
         */
        $wrapper.find( '.show-more-wrapper' ).each( function() {
            var $showMoreWrapper = $( this ),
                hasVisible = $showMoreWrapper.find( 'p.form-field' ).filter( function () {
                    return this.style.display !== 'none';
                }),
                $trigger = $showMoreWrapper.data( 'trigger' ) ? $wrapper.find( $showMoreWrapper.data( 'trigger' ) ) : false;

            if ( $trigger.length > 0 ) {
                if ( hasVisible.length <= 0 ) {
                    $trigger.hide();
                } else {
                    $trigger.show();
                }
            }
        } );
    };

    AdminShipmentModal.prototype.onClose = function( event, target ) {
        var self = event.data.adminShipmentModal;

        if ( target.indexOf( self.modalId ) !== -1 ) {
            if ( self.$modal && self.$modal.length > 0 ) {
                self.$modal.off( 'click.wc-stc-modal-' + self.modalId );
            }
        }
    };

    AdminShipmentModal.prototype.onOpen = function( event, target ) {
        var self = event.data.adminShipmentModal;

        if ( target.indexOf( self.modalId ) !== -1 ) {
            self.setup();

            self.$modal = $( '.' + self.modalClass );
            self.$modal.data( 'self', self );

            if ( self.loadAsync ) {
                params = {
                    'action'      : self.getAction( 'load' ),
                    'reference_id': self.referenceId,
                    'security'    : self.getNonce( 'load' )
                };

                self.doAjax( params, self.onLoadSuccess );
            } else {
                self.initData();
            }

            $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_open', [self] );
            self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_open', [self] );
        }
    };

    AdminShipmentModal.prototype.onLoadSuccess = function( data, self ) {
        self.initData();

        $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_after_load_success', [data, self] );
        self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_after_load_success', [data, self] );
    };

    AdminShipmentModal.prototype.onAjaxSuccess = function( data, self ) {

    };

    AdminShipmentModal.prototype.onAjaxError = function( data, self ) {

    };

    AdminShipmentModal.prototype.getModalMainContent = function() {
        return this.$modal.find( 'article' );
    };

    AdminShipmentModal.prototype.doAjax = function( params, cSuccess, cError  ) {
        var self     = this,
            $content = self.getModalMainContent();

        cSuccess = cSuccess || self.onAjaxSuccess;
        cError   = cError || self.onAjaxError;

        if ( ! params.hasOwnProperty( 'reference_id' ) ) {
            params['reference_id'] = self.referenceId;
        }

        self.$modal.find( '.wc-backbone-modal-content' ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        self.$modal.find( '.notice-wrapper' ).empty();

        $.ajax({
            type: "POST",
            url:  self.params.ajax_url,
            data: params,
            success: function( data ) {
                if ( data.success ) {
                    if ( data.fragments ) {
                        $.each( data.fragments, function ( key, value ) {
                            $( key ).replaceWith( value );
                        });
                    }

                    self.$modal.find( '#btn-ok' ).prop( 'disabled', false );
                    self.$modal.find( '.wc-backbone-modal-content' ).unblock();

                    cSuccess.apply( self, [ data, self ] );

                    /**
                     * Refresh shipments data, if available
                     */
                    if ( shipments.admin.shipments ) {
                        shipments.admin.shipments.refresh( data );
                    }

                    $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_ajax_success', [data, self] );
                    self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_ajax_success', [data, self] );

                    /**
                     * Init JS form field types.
                     */
                    if ( data.fragments ) {
                        self.afterRefresh();
                    }
                } else {
                    self.$modal.find( '#btn-ok' ).prop( 'disabled', false );
                    self.$modal.find( '.wc-backbone-modal-content' ).unblock();

                    cError.apply( self, [ data, self ] );

                    self.printNotices( $content, data );

                    // ScrollTo top of modal
                    $content.animate({
                        scrollTop: 0
                    }, 500 );

                    $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_ajax_error', [data, self] );
                    self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_ajax_error', [data, self] );
                }
            },
            error: function( data ) {},
            dataType: 'json'
        });
    };

    AdminShipmentModal.prototype.afterRefresh = function() {
        var self = this;

        if ( self.$modal.find( '.notice-wrapper' ).length === 0 ) {
            self.getModalMainContent().prepend( '<div class="notice-wrapper"></div>' );
        }

        $( document.body ).trigger( 'wc-enhanced-select-init' );
        $( document.body ).trigger( 'wc-init-datepickers' );
        $( document.body ).trigger( 'init_tooltips' );
    };

    AdminShipmentModal.prototype.initData = function() {
        var self = this;

        self.$modal = $( '.' + self.modalClass );
        self.$modal.data( 'self', self );

        self.afterRefresh();

        self.$modal.on( 'click.wc-stc-modal-' + self.modalId, '#btn-ok', { adminShipmentModal: self }, self.onSubmit );
        self.$modal.on( 'touchstart.wc-stc-modal-' + self.modalId, '#btn-ok', { adminShipmentModal: self }, self.onSubmit );
        self.$modal.on( 'keydown.wc-stc-modal-' + self.modalId, { adminShipmentModal: self }, self.onKeyDown );

        self.$modal.on( 'click.wc-stc-modal-' + self.modalId, '.notice .notice-dismiss', { adminShipmentModal: self }, self.onRemoveNotice );
        self.$modal.on( 'change.wc-stc-modal-' + self.modalId, ':input[id]', { adminShipmentModal: self }, self.onChangeField );
        self.$modal.on( 'click.wc-stc-modal-' + self.modalId, '.show-more', { adminShipmentModal: self }, self.onExpandMore );
        self.$modal.on( 'click.wc-stc-modal-' + self.modalId, '.show-fewer', { adminShipmentModal: self }, self.onHideMore );

        $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_after_init_data', [self] );
        self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_after_init_data', [self] );

        self.$modal.find( ':input:visible' ).trigger( "change", [self] );
    };

    AdminShipmentModal.prototype.printNotices = function( $wrapper, data ) {
        var self = this;

        if ( data.hasOwnProperty( 'message' ) ) {
            self.addNotice( data.message, 'error', $wrapper );
        } else if ( data.hasOwnProperty( 'messages' ) ) {
            $.each( data.messages, function ( type, typeMessages ) {
                if ( typeof typeMessages === 'string' || typeMessages instanceof String ) {
                    self.addNotice( typeMessages, 'error', $wrapper );
                } else {
                    $.each( typeMessages, function ( i, message ) {
                        self.addNotice( message, ( 'soft' === type ? 'warning' : type ), $wrapper );
                    });
                }
            });
        }
    };

    AdminShipmentModal.prototype.onSubmitSuccess = function( data, self ) {
        var $content = self.getModalMainContent();

        if ( data.hasOwnProperty( 'messages' ) && ( data.messages.hasOwnProperty( 'error' ) || data.messages.hasOwnProperty( 'soft' ) ) ) {
            self.printNotices( $content, data );

            self.$modal.find( 'footer' ).find( '#btn-ok' ).addClass( 'modal-close' ).attr( 'id', 'btn-close' ).text( self.params.i18n_modal_close );
        } else {
            self.$modal.find( '.modal-close' ).trigger( 'click' );
        }

        if ( data.hasOwnProperty( 'shipment_id' ) && $( 'div#shipment-' + data['shipment_id'] ).length > 0 ) {
            shipments.admin.shipments.initShipment( data['shipment_id'] );
        }

        $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_after_submit_success', [data, self] );
        self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_after_submit_success', [data, self] );

        if ( data.hasOwnProperty( 'reload' ) && true === data['reload'] ) {
            window.location.reload();
        }
    };

    AdminShipmentModal.prototype.getCleanId = function( removePrefix = false ) {
        var clean = this.modalClass.split( '-' ).join( '_' ).replace( '_modal_', '_' );

        if ( removePrefix ) {
            clean = clean.replace( 'wc_stc_', '' ).replace( 'wc_gzdp_', '' );
        }

        return clean;
    };

    AdminShipmentModal.prototype.getNonceParams = function() {
        return window.hasOwnProperty( this.nonceParams ) ? window[ this.nonceParams ] : {};
    };

    AdminShipmentModal.prototype.getNonce = function( type ) {
        var nonceKey    = this.getCleanId( true ) + '_' + type + '_nonce',
            nonceParams = this.getNonceParams();

        return nonceParams.hasOwnProperty( nonceKey ) ? nonceParams[ nonceKey ] : this.params[ type + '_nonce' ];
    };

    AdminShipmentModal.prototype.getAction = function( type ) {
        return this.getCleanId().replace( 'wc_', 'woocommerce_' ) + '_' + type;
    };

    AdminShipmentModal.prototype.onKeyDown = function( event ) {
        var self   = event.data.adminShipmentModal,
            button = event.keyCode || event.which;

        // Enter key
        if ( 13 === button && ! ( event.target.tagName && ( event.target.tagName.toLowerCase() === 'input' || event.target.tagName.toLowerCase() === 'textarea' ) ) ) {
            self.onSubmit.apply( self.$modal.find( 'button#btn-ok' ), [ e ] );
        }
    };

    AdminShipmentModal.prototype.getFormData = function( $form ) {
        var data = {}

        $form.find( '.show-more-wrapper' ).each( function() {
            if ( ! $( this ).is( ':visible' ) ) {
                $( this ).addClass( 'show-more-wrapper-force-show' ).show();
            }
        } );

        /**
         * Do only transmit data of visible fields
         */
        $.each( $form.find( ':input' ).serializeArray(), function( index, item ) {
            var $item = $form.find( ':input[name="' + item.name + '"]' );

            /**
             * Skip invisible items except hidden inputs
             */
            if ( $item && ! $item.is( ':visible' ) && $item.attr( 'type' ) !== 'hidden' ) {
                return true;
            }

            if ( item.name.indexOf( '[]' ) !== -1 ) {
                item.name = item.name.replace( '[]', '' );
                data[ item.name ] = $.makeArray( data[ item.name ] );
                data[ item.name ].push( item.value );
            } else {
                data[ item.name ] = item.value;
            }
        });

        $form.find( '.show-more-wrapper-force-show' ).each( function() {
            $( this ).removeClass( 'show-more-wrapper-force-show' ).hide();
        } );

        return data;
    };

    AdminShipmentModal.prototype.onSubmit = function( event ) {
        var self       = event.data.adminShipmentModal,
            $content   = self.getModalMainContent(),
            $form      = $content.find( 'form' ),
            params     = self.getFormData( $form ),
            $submit    = self.$modal.find( '#btn-ok' );

        if ( $submit.length > 0 ) {
            $submit.prop( 'disabled', true );
        }

        params['security']     = self.getNonce( 'submit' );
        params['reference_id'] = self.referenceId;
        params['action']       = self.getAction( 'submit' );

        self.doAjax( params, self.onSubmitSuccess );

        event.preventDefault();
        event.stopPropagation();
    };

    AdminShipmentModal.prototype.addNotice = function( message, noticeType, $wrapper ) {
        $wrapper.find( '.notice-wrapper' ).append( '<div class="notice is-dismissible notice-' + noticeType +'"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
    };

    AdminShipmentModal.prototype.response = function( event, target, data ) {
        var self = event.data.adminShipmentModal;

        if ( target.indexOf( self.modalId ) !== -1 ) {
            $( document.body ).trigger( 'wc_shiptastic_admin_shipment_modal_response', [self, data] );
            self.$modalTrigger.trigger( 'wc_shiptastic_admin_shipment_modal_response', [self, data] );
        }
    };

    $.fn.wc_shiptastic_admin_shipment_modal = function() {
        return this.each( function() {
            new AdminShipmentModal( $( this ) );

            return this;
        });
    };
})( jQuery, window, document, window.shiptastic );
