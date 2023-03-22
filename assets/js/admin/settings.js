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
                .on( 'click', 'a.woocommerce-gzd-input-toggle-trigger', this.onInputToogleClick )
                .on( 'change', '.wc-gzd-setting-tabs input.woocommerce-gzd-tab-status-checkbox', this.onChangeTabStatus )
                .on( 'change gzd_show_or_hide_fields', '.wc-gzd-admin-settings :input', this.onChangeInput )
                .on( 'change', '.wc-gzd-setting-tab-enabled :input', this.preventWarning )
                .on( 'click', 'a.wc-gzd-install-extension-btn', this.onInstallExtension )

            $( document.body )
                .on( 'woocommerce_gzd_setting_field_visible', this.onShowField )
                .on( 'woocommerce_gzd_setting_field_invisible', this.onHideField );

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
                $this = $( this );

            var data = {
                action: 'woocommerce_gzd_install_extension',
                security: self.params.install_extension_nonce,
                extension: $this.data( 'extension' )
            };

            $this.addClass( 'wc-gzd-is-loading' );
            $this.append( '<span class="spinner is-active"></span>' );

            $.ajax( {
                url: self.params.ajax_url,
                data: data,
                dataType: 'json',
                type: 'POST',
                success: function( response ) {
                    $this.find( '.spinner' ).remove();
                    $this.removeClass( 'wc-gzd-is-loading' );

                    if ( response.success ) {
                        if ( $this.is("[href]") && '#' !== $this.attr( 'href' ) ) {
                            window.location.href = $this.attr( 'href' );
                        }
                    } else if ( response.hasOwnProperty( 'message' ) ) {
                        var $wrapper = $( '#wpbody-content' ).find( '.wrap' );

                        if ( $( '.wc-gzd-setting-tabs' ).length > 0 ) {
                            $wrapper = $( '.wc-gzd-setting-tabs' );
                        }

                        $wrapper.before( '<div class="error inline" id="message"><p>' + response.message + '</p></div>' );

                        $( 'html, body' ).animate({
                            scrollTop: ( $( '#message' ).offset().top - 32 )
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

        onShowField: function( e, $field, name, value ) {
            var $inputs = $field.parents( 'table' ).find( ':input[data-show_if_' + name + ']' );

            $inputs.each( function() {
                $( this ).trigger( 'gzd_show_or_hide_fields' );
            });
        },

        onHideField: function( e, $field, name, value ) {
            var $inputs = $field.parents( 'table' ).find( ':input[data-show_if_' + name + ']' );

            $inputs.each( function() {
                $( this ).trigger( 'gzd_show_or_hide_fields' );
            });
        },

        onChangeInput: function() {
            var self = germanized.settings,
                $field = $( this ).parents( 'tr' );

            $field.find( ':input:not(.select2-focusser, .select2-input)' ).each( function() {
                var $input   = $( this ),
                    checked  = false,
                    nameOrg = $( this ).attr( 'name' );

                if ( $input.is( ':checked' ) || $input.is( ':selected' ) ) {
                    checked = true;

                    // Make sure that hidden fields are considered unchecked
                    if ( ! $input.parents( 'tr' ).is( ':visible' ) ) {
                        checked = false;
                    }
                }

                if ( typeof nameOrg === typeof undefined || nameOrg === false ) {
                    return;
                }

                // Remove square brackets
                var name    = nameOrg.replace( /[\[\]']+/g, '' );
                var val     = $input.val();

                var $fields = $( '.wc-gzd-admin-settings' ).find( ':input[data-show_if_' + name +  ']' );

                if ( $input.is( ':radio' ) ) {
                    /**
                     * Do not trigger logic for non-selected radios
                     */
                    if ( ! $input.is( ':checked' ) ) {
                        return;
                    }
                } else if ( $input.is( ':checkbox' ) ) {
                    val = $input.is( ':checked' ) ? 'yes' : 'no';

                    // Make sure that hidden fields are considered unchecked
                    if ( ! $input.parents( 'tr' ).is( ':visible' ) ) {
                        val = 'no';
                    }
                }

                $fields.each( function() {
                    var dataValue   = $( this ).data( 'show_if_' + name ),
                        data        = $( this ).data(),
                        currentVal  = $( this ).val(),
                        currentName = $( this ).attr( 'name' ).replace( /[\[\]']+/g, '' ),
                        $field      = $( this ).parents( 'tr' ),
                        skipField   = false;

                    var isFieldVisible = $field.hasClass( 'wc-gzd-setting-visible' );
                    var deps           = [];

                    for ( var dataName in data ) {
                        if ( data.hasOwnProperty( dataName ) ) {
                            if ( dataName.substring( 0, 8 ) === 'show_if_' ) {
                                var cleanName       = dataName.replace( 'show_if_', '' );
                                var $dependendField = self.getInputByIdOrName( cleanName );
                                var index           = $dependendField.index( ':input' );
                                deps[ index ] = cleanName;
                            }
                        }
                    }

                    deps = deps.filter(function(){return true;});

                    if ( deps.length > 1 ) {
                        if ( ! isFieldVisible ) {
                            var nameToUse = deps.slice(-1)[0];
                            var $theInput = self.getInputByIdOrName( nameToUse );

                            if ( name !== nameToUse ) {
                                skipField = true;

                                if ( $theInput.parents( 'tr' ).is( ':visible' ) ) {
                                    $theInput.trigger( 'gzd_show_or_hide_fields' );
                                }
                            } else {
                                if ( ! $theInput.parents( 'tr' ).is( ':visible' ) ) {
                                    $field.addClass( 'wc-gzd-setting-invisible' );
                                    $( document.body ).trigger( 'woocommerce_gzd_setting_field_invisible', [ $field, currentName, currentVal ] );

                                    skipField = true;
                                }
                            }
                        }
                    }

                    if ( skipField ) {
                        return;
                    }

                    $field.removeClass( 'wc-gzd-setting-visible wc-gzd-setting-invisible' );

                    if ( ( 'undefined' !== typeof dataValue ) && dataValue.length > 0 ) {
                        // Check value
                        if ( val === dataValue ) {
                            $field.addClass( 'wc-gzd-setting-visible' );

                            $( document.body ).trigger( 'woocommerce_gzd_setting_field_visible', [ $field, currentName, currentVal ] );
                        } else {
                            $field.addClass( 'wc-gzd-setting-invisible' );

                            $( document.body ).trigger( 'woocommerce_gzd_setting_field_invisible', [ $field, currentName, currentVal ] );
                        }
                    } else if ( checked ) {
                        $field.addClass( 'wc-gzd-setting-visible' );

                        $( document.body ).trigger( 'woocommerce_gzd_setting_field_visible', [ $field, currentName, currentVal ] );

                    } else {
                        $field.addClass( 'wc-gzd-setting-invisible' );

                        $( document.body ).trigger( 'woocommerce_gzd_setting_field_invisible', [ $field, currentName, currentVal ] );
                    }
                });

                var $table         = $( this ).parents( '.form-table' );
                var tableIsVisible = false;

                $table.find( 'tr' ).each( function() {
                    var isVisible = ! $( this ).hasClass( 'wc-gzd-setting-invisible' );

                    if ( isVisible ) {
                        tableIsVisible = true;
                        return false;
                    }
                });

                if ( ! tableIsVisible ) {
                    $table.hide();
                } else {
                    $table.show();
                }
            });
        },

        /**
         * Finds the input field by ID or name (some inputs, e.g. radio buttons may not have an id).
         *
         * @param cleanName
         * @returns {*|jQuery}
         */
        getInputByIdOrName: function( cleanName ) {
            var $field = $( '.wc-gzd-admin-settings' ).find( ':input#' + cleanName );

            if ( $field.length <= 0 ) {
                $field = $( '.wc-gzd-admin-settings' ).find( ':input[name="' + cleanName + '"]' );
            }

            return $field;
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

        onInputToogleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-gzd-input-toggle' ),
                $row      = $toggle.parents( 'fieldset' ),
                $checkbox = $row.find( 'input[type=checkbox]' ),
                $enabled  = $toggle.hasClass( 'woocommerce-input-toggle--enabled' );

            $toggle.removeClass( 'woocommerce-input-toggle--enabled' );
            $toggle.removeClass( 'woocommerce-input-toggle--disabled' );

            if ( $enabled ) {
                $checkbox.prop( 'checked', false );
                $toggle.addClass( 'woocommerce-input-toggle--disabled' );
            } else {
                $checkbox.prop( 'checked', true );
                $toggle.addClass( 'woocommerce-input-toggle--enabled' );
            }

            $checkbox.trigger( 'change' );

            return false;
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