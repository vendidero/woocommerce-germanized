/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.settings = {
        params: {},

        init: function() {
            var self = this;
            this.params = wc_gzd_admin_settings_params;

            try {
                $( document.body ).on( 'wc-enhanced-select-init wc-gzd-enhanced-select-init', this.onEnhancedSelectInit ).trigger( 'wc-gzd-enhanced-select-init' );
            } catch( err ) {
                // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
                window.console.log( err );
            }

            $( document )
                .on( 'change', 'input[name=woocommerce_gzd_dispute_resolution_type]', this.onChangeDisputeResolutionType )
                .on( 'change', '.wc-gzd-setting-tabs input.woocommerce-gzd-tab-status-checkbox', this.onChangeTabStatus )
                .on( 'change', '.wc-gzd-setting-tab-enabled :input', this.preventWarning )
                .on( 'click', '.wc-gzd-install-extension-btn', this.onInstallExtension )
                .on( 'change gzd_show_or_hide_fields', '.wc-gzd-admin-settings :input', this.onChangeInput );

            $( '.wc-gzd-admin-settings :input' ).trigger( 'gzd_show_or_hide_fields' );
            $( 'input[name=woocommerce_gzd_dispute_resolution_type]:checked' ).trigger( 'change' );

            this.initMailSortable();

            $( document.body ).on( 'init_tooltips', function() {
                self.initTipTips();
            });

            self.initTipTip();
        },

        /**
         * Prevents the unsaved settings warning for the main germanized tab
         * as these toggles use AJAX requests to save the settings.
         */
        preventWarning: function() {
            window.onbeforeunload = '';
        },

        initTipTip: function() {
            $( '.wc-gzd-setting-tab-actions a.button' ).tipTip( {
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
        },

        onInstallExtension: function() {
            var self  = germanized.settings,
                $this = $( this ),
                $wrapper = $( '#wpbody-content' ).find( '.wrap' );

            if ( $( '.wc-gzd-setting-tabs' ).length > 0 ) {
                $wrapper = $( '.wc-gzd-setting-tabs' );
            } else if ( $this.parents( '.forminp' ).length > 0 ) {
                $wrapper = $this.parents( '.forminp' );
            }

            var $msg_wrapper = $wrapper.find( '.msg-wrapper' ).length > 0 ? $wrapper.find( '.msg-wrapper' ) : $wrapper;

            $( 'body' ).find( '#wc-gzd-ext-error' ).remove();

            var data = {
                action: 'woocommerce_gzd_install_extension',
                security: self.params.install_extension_nonce,
                extension: $this.data( 'extension' ),
                license_key: $wrapper.find( '#license_key' ).length > 0 ? $wrapper.find( '#license_key' ).val() : '',
            };

            $this.addClass( 'wc-gzd-is-loading' );
            $this.append( '<span class="spinner is-active"></span>' );

            if ( $this.is( ':button' ) ) {
                $this.addClass( 'disabled' ).prop( 'disabled', true );
            }

            $.ajax( {
                url: self.params.ajax_url,
                data: data,
                dataType: 'json',
                type: 'POST',
                success: function( response ) {
                    $this.find( '.spinner' ).remove();
                    $this.removeClass( 'wc-gzd-is-loading' );

                    if ( $this.is( ':button' ) ) {
                        $this.removeClass( 'disabled' ).prop( 'disabled', false );
                    }

                    if ( response.success ) {
                        if ( $this.is("[href]") && '#' !== $this.attr( 'href' ) ) {
                            window.location.href = $this.attr( 'href' );
                        } else if ( response.hasOwnProperty( 'redirect' ) ) {
                            window.location.href = response.redirect;
                        }
                    } else if ( response.hasOwnProperty( 'message' ) ) {
                        $msg_wrapper.before( '<div class="error inline" id="wc-gzd-ext-error"><p>' + response.message + '</p></div>' );

                        $( 'html, body' ).animate({
                            scrollTop: ( $( '#wc-gzd-ext-error' ).offset().top - 92 )
                        }, 1000 );
                    }
                }
            } );

            return false;
        },

        onChangeTabStatus: function() {
            var $checkbox = $( this ),
                self      = germanized.settings,
                tab_id    = $checkbox.data( 'tab' ),
                $toggle   = $checkbox.parents( 'td' ).find( '.woocommerce-gzd-input-toggle' ),
                $link     = $toggle.parents( 'a' ),
                isEnabled = $checkbox.is( ':checked' ) ? 'yes' : 'no';

            var data = {
                action: 'woocommerce_gzd_toggle_tab_enabled',
                security: self.params.tab_toggle_nonce,
                enable: isEnabled,
                tab: tab_id
            };

            $toggle.addClass( 'woocommerce-input-toggle--loading' );

            $.ajax( {
                url:      self.params.ajax_url,
                data:     data,
                dataType : 'json',
                type     : 'POST',
                success:  function( response ) {
                    if ( true === response.data ) {
                        $toggle.removeClass( 'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled' );
                        $toggle.addClass( 'woocommerce-input-toggle--enabled' );
                        $toggle.removeClass( 'woocommerce-input-toggle--loading' );

                        if ( response.hasOwnProperty( 'message' ) && response.message.length > 0 ) {
                            $( '.wc-gzd-setting-tabs' ).before( '<div class="error inline" id="message"><p>' + response.message +'</p></div>' );

                            $( 'html, body' ).animate({
                                scrollTop: ( $( '#message' ).offset().top - 32 )
                            }, 1000 );
                        }
                    } else if ( false === response.data ) {
                        $toggle.removeClass( 'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled' );
                        $toggle.addClass( 'woocommerce-input-toggle--disabled' );
                        $toggle.removeClass( 'woocommerce-input-toggle--loading' );
                    } else if ( 'needs_setup' === response.data ) {
                        window.location.href = $link.attr( 'href' );
                    }
                }
            } );

            return false;
        },

        onChangeInput: function() {
            var self             = germanized.settings,
                $mainInput       = $( this ),
                mainId           = $mainInput.attr( 'id' ) ? $mainInput.attr( 'id' ) : $mainInput.attr( 'name' ),
                $dependentFields = $( '.wc-gzd-admin-settings :input[data-show_if_' + $.escapeSelector( mainId ) + ']' );

            var $input, $field, data, meetsConditions, cleanName, $dependentField, valueExpected, val, isChecked;

            $.each( $dependentFields, function () {
                $input          = $( this );
                $field          = $input.parents( 'tr' );
                data            = $input.data();
                meetsConditions = true;

                for ( var dataName in data ) {
                    if ( data.hasOwnProperty( dataName ) ) {
                        /**
                         * Check all the conditions for a dependent field.
                         */
                        if ( dataName.substring( 0, 8 ) === 'show_if_' ) {
                            cleanName       = dataName.replace( 'show_if_', '' );
                            $dependentField = self.getInputByIdOrName( cleanName );
                            valueExpected   = $input.data( dataName ) ? $input.data( dataName ).split(',') : [];

                            if ( $dependentField.length > 0 ) {
                                val       = $dependentField.val();
                                isChecked = false;

                                if ( $dependentField.is( ':radio' ) ) {
                                    val = $dependentField.parents( 'fieldset' ).find( ':checked' ).length > 0 ? $dependentField.parents( 'fieldset' ).find( ':checked' ).val() : 'no';

                                    if ( 'no' !== val ) {
                                        isChecked = true;
                                    }
                                } else if ( $dependentField.is( ':checkbox' ) ) {
                                    val = $dependentField.is( ':checked' ) ? 'yes' : 'no';

                                    if ( 'yes' === val ) {
                                        isChecked = true;
                                    }
                                } else {
                                    isChecked = undefined !== val && '0' !== val && '' !== val;
                                }

                                if ( valueExpected && valueExpected.length > 0 ) {
                                    if ( $.inArray( val, valueExpected ) === -1 ) {
                                        meetsConditions = false;
                                    }
                                } else if ( ! isChecked ) {
                                    meetsConditions = false;
                                }
                            }

                            if ( ! meetsConditions ) {
                                break;
                            }
                        }
                    }
                }

                $field.removeClass( 'wc-gzd-setting-visible wc-gzd-setting-invisible' );

                if ( meetsConditions ) {
                    $field.addClass( 'wc-gzd-setting-visible' );
                } else {
                    $field.addClass( 'wc-gzd-setting-invisible' );
                }
            } );
        },

        /**
         * Finds the input field by ID or name (some inputs, e.g. radio buttons may not have an id).
         *
         * @param cleanName
         * @returns {*|jQuery}
         */
        getInputByIdOrName: function( cleanName ) {
            var self = germanized.settings;
            cleanName = self.getCleanDataId( cleanName );

            var $field = $( '.wc-gzd-admin-settings :input' ).filter( function() {
                var id = $( this ).attr('id' ) ? $( this ).attr('id' ) : $( this ).attr('name' );

                if ( ! id ) {
                    return false;
                }

                return self.getCleanDataId( id ) === cleanName;
            });

            return $field;
        },

        /**
         * Make sure to remove any hyphens as data-attributes are stored
         * camel case without hyphens in the DOM.
         */
        getCleanDataId: function( id ) {
            return id.toLowerCase().replace( /-/g, '' );
        },

        onEnhancedSelectInit: function() {
            var self = germanized.settings;

            // Tag select
            $( ':input.wc-gzd-enhanced-tags' ).filter( ':not(.enhanced)' ).each( function () {
                var select2_args = {
                    minimumResultsForSearch: 10,
                    allowClear: $( this ).data( 'allow_clear' ) ? true : false,
                    placeholder: $( this ).data( 'placeholder' ),
                    tags: true
                };

                $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
            });

            function display_result( self, select2_args ) {
                $( self ).selectWoo( select2_args ).addClass( 'enhanced' );

                if ( $( self ).prop( 'multiple' ) ) {
                    $( self ).on( 'change', function(){
                        var $children = $( self ).children();
                        $children.sort(function(a, b){
                            var atext = a.text.toLowerCase();
                            var btext = b.text.toLowerCase();

                            if ( atext > btext ) {
                                return 1;
                            }
                            if ( atext < btext ) {
                                return -1;
                            }
                            return 0;
                        });
                        $( self ).html( $children );
                    });
                }
            }

            $( ':input.gzd-select-term' ).filter( ':not(.enhanced)' ).each( function() {
                var select2_args = {
                    allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                    placeholder: $( this ).data( 'placeholder' ),
                    minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
                    escapeMarkup: function( m ) {
                        return m;
                    },
                    ajax: {
                        url:         self.params.ajax_url,
                        dataType:    'json',
                        delay:       250,
                        data:        function( params ) {
                            return {
                                term         : params.term,
                                action       : $( this ).data( 'action' ) || 'woocommerce_json_search_taxonomy_terms',
                                security     : self.params.search_term_nonce,
                                exclude      : $( this ).data( 'exclude' ),
                                taxonomy     : $( this ).data( 'taxonomy' ),
                                limit        : $( this ).data( 'limit' ),
                            };
                        },
                        processResults: function( data ) {
                            var terms = [];
                            if ( data && ! data.error ) {
                                $.each( data, function( index, term ) {
                                    terms.push( { id: term.term_id, text: term.name } );
                                } );
                            }
                            return {
                                results: terms
                            };
                        },
                        cache: true
                    }
                };

                display_result( this, select2_args );

                //$( this ).selectWoo( select2_args ).addClass( 'enhanced' );
            });
        },
      
        onParcelDeliveryShowSpecial: function() {
            var val = $( this ).val();

            if ( 'shipping_methods' === val ) {
                $( 'select#woocommerce_gzd_checkboxes_parcel_delivery_show_shipping_methods' ).parents( 'tr' ).show();
            } else {
                $( 'select#woocommerce_gzd_checkboxes_parcel_delivery_show_shipping_methods' ).parents( 'tr' ).hide();
            }
        },

        onChangeDisputeResolutionType: function() {
            var val = $( this ).val();
            var text = $( '#woocommerce_gzd_alternative_complaints_text_' + val );

            $( '[id^=woocommerce_gzd_alternative_complaints_text_]' ).parents( 'tr' ).hide();
            $( '#woocommerce_gzd_alternative_complaints_text_' + val ).parents( 'tr' ).show();
        },

        initMailSortable: function() {
            if ( $( '#woocommerce_gzd_mail_attach_imprint' ).length > 0 ) {
                var table = $( '#woocommerce_gzd_mail_attach_imprint' ).parents( 'table' );
                $( table ).find( 'tbody' ).sortable({
                    items: 'tr',
                    cursor: 'move',
                    axis: 'y',
                    handle: 'td, th',
                    scrollSensitivity: 40,
                    helper:function(e,ui){
                        ui.children().each(function(){
                            jQuery(this).width(jQuery(this).width());
                        });
                        ui.css('left', '0');
                        return ui;
                    },
                    start:function(event,ui) {
                        ui.item.css('background-color','#f6f6f6');
                    },
                    stop:function(event,ui){
                        ui.item.removeAttr('style');
                        var pages = [];
                        $( table ).find( 'tr select' ).each( function() {
                            pages.push( $(this).attr( 'id' ).replace( 'woocommerce_gzd_mail_attach_', '' ) );
                        });
                        $( '#woocommerce_gzd_mail_attach_order' ).val( pages.join() );
                    }
                });
            }
        }
    };

    $( document ).ready( function() {
        germanized.settings.init();
    });

})( jQuery, window.germanized );