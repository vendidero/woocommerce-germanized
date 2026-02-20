window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, shipments ) {
    shipments.admin.shipment_settings = {
        params: {},

        init: function() {
            var self = shipments.admin.shipment_settings;
            self.params = wc_shiptastic_admin_settings_params;

            $( document )
                .on( 'change shiptastic_show_or_hide_fields', 'table.form-table :input[id]', self.onChangeInput )
                .on( 'click', '.wc_input_table a.add', self.onAddInputRow )
                .on( 'change', '.stc-search-shipping-provider', self.onChangeProviderSearch );

            try {
                $( document.body ).on( 'wc-enhanced-select-init wc-stc-enhanced-select-init', self.onEnhancedSelectInit ).trigger( 'wc-stc-enhanced-select-init' );
            } catch( err ) {
                // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
                window.console.log( err );
            }

            $( 'table.form-table :input[id]' ).trigger( 'shiptastic_show_or_hide_fields' );
        },

        onChangeProviderSearch: function() {
            let data = $( this ).find( ':selected' ).data( 'data' );

            if ( data.hasOwnProperty( 'text' ) && data.text ) {
                $( '#shipping_provider_title' ).val( data.text );
            }

            if ( data.hasOwnProperty( 'tracking_url' ) && data.tracking_url ) {
                $( '#shipping_provider_tracking_url_placeholder' ).val( data.tracking_url );
            }
        },

        onEnhancedSelectInit: function() {
            var self = shipments.admin.shipment_settings;

            function display_result( self, select2_args ) {
                $( self ).selectWoo( select2_args ).addClass( 'enhanced' );
            }

            $( ':input.stc-search-shipping-provider' ).filter( ':not(.enhanced)' ).each( function() {
                let select2_args = {
                    allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                    placeholder: $( this ).data( 'placeholder' ),
                    minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
                    templateResult: function( provider ) {
                        if ( ! provider.icon ) {
                            return provider.text;
                        }

                        return $(
                            '<span class="stc-provider-result-wrapper"><img src="' + provider.icon +'" class="provider-icon" /> ' + provider.text + '</span>'
                        );
                    },
                    ajax: {
                        url:         self.params.ajax_url,
                        dataType:    'json',
                        delay:       250,
                        data:        function( params ) {
                            return {
                                provider     : params.term,
                                action       : $( this ).data( 'action' ) || 'woocommerce_stc_json_search_shipping_providers',
                                security     : self.params.search_shipping_providers_nonce,
                                exclude      : $( this ).data( 'exclude' ),
                                limit        : $( this ).data( 'limit' ),
                            };
                        },
                        processResults: function( data ) {
                            let providers = [];
                            if ( data && ! data.error ) {
                                $.each( data, function( index, provider ) {
                                    providers.push( provider );
                                } );
                            }
                            return {
                                results: providers,
                            };
                        },
                        cache: true
                    }
                };

                display_result( this, select2_args );
            });
        },

        onAddInputRow: function() {
            var self = shipments.admin.shipment_settings,
                $wrapper = $( this ).parents( '.wc_input_table' ),
                size = $wrapper.find( 'tbody tr.item:visible' ).length,
                $template = $wrapper.find( 'tr.template' ).clone();

            $template.find( ':input' ).each( function() {
                $( this ).attr('name', $( this ).attr( 'name' ).replace( 'size', size ) );
            });

            $template.appendTo( $wrapper.find( 'tbody' ) ).removeClass( 'template' ).show();

            $( document.body ).trigger( 'wc-enhanced-select-init' );

            return false;
        },

        getCleanInputId: function( $mainInput ) {
            var self = shipments.admin.shipment_settings,
                fieldId = $mainInput.attr( 'id' ) ? $mainInput.attr( 'id' ) : $mainInput.attr( 'name' );

            if ( self.params.hasOwnProperty( 'clean_input_callback' ) ) {
                var callback = self.params.clean_input_callback,
                    params = [],
                    objectName = '',
                    methodName = '';

                if ( callback.substring( 0, 17 ) === 'shiptastic.admin.' ) {
                    callback = callback.slice( 17 );
                    params = callback.split( "." );
                    objectName = shipments.admin[params[0]];
                    methodName = params[1];
                } else {
                    params = callback.split( "." );
                    objectName = window[params[0]];
                    methodName = params[1];
                }

                if ( 'object' === typeof objectName && objectName.hasOwnProperty( methodName ) ) {
                    fieldId = objectName[methodName]( $mainInput );
                }
            }

            if ( ! fieldId ) {
                return '';
            }

            return fieldId;
        },

        getInputByIdOrName: function( $wrapper , cleanName ) {
            var self = shipments.admin.shipment_settings;
            cleanName = self.getCleanDataId( cleanName );

            return $wrapper.find( ':input' ).filter( function() {
                var id = self.getCleanInputId( $( this ) );

                if ( ! id ) {
                    return false;
                }

                return self.getCleanDataId( id ) === cleanName;
            });
        },

        /**
         * Make sure to remove any hyphens as data-attributes are stored
         * camel case without hyphens in the DOM.
         */
        getCleanDataId: function( id ) {
            return id.toLowerCase().replace( /-/g, '' );
        },

        onChangeInput: function() {
            var self             = shipments.admin.shipment_settings,
                $mainInput       = $( this ),
                $wrapper         = $( this ).parents( 'form' ),
                mainId           = self.getCleanInputId( $mainInput ),
                $dependentFields = $wrapper.find( ':input[data-show_if_' + $.escapeSelector( mainId ) + ']' );

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
                            $dependentField = self.getInputByIdOrName( $wrapper, cleanName );
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

                if ( meetsConditions ) {
                    if ( $field.length === 0 ) {
                        // Use this markup as fallback in case field does not belong to a table, e.g. shipping method settings
                        $input.parents( 'fieldset' ).show();
                        $input.parents( 'fieldset' ).prev( 'label' ).show();
                    } else {
                        $field.show();
                    }
                } else {
                    if ( $field.length === 0 ) {
                        $input.parents( 'fieldset' ).hide();
                        $input.parents( 'fieldset' ).prev( 'label' ).hide();
                    } else {
                        $field.hide();
                    }
                }
            } );
        }
    };

    $( document ).ready( function() {
        shipments.admin.shipment_settings.init();
    });

})( jQuery, window.shiptastic );