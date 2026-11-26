/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.admin = {
        params: {},

        init: function() {
            var self = this;
            this.params = wc_gzd_admin_params;

            $( document )
                .on( 'click', 'a.woocommerce-gzd-input-toggle-trigger', this.onInputToogleClick )
                .on( 'change gzd_show_or_hide_fields', '.wc-gzd-admin-settings :input', this.onChangeInput );

            $( '.wc-gzd-admin-settings :input' ).trigger( 'gzd_show_or_hide_fields' );
            self.updateSectionVisibility();
        },

        onInputToogleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-gzd-input-toggle' ),
                $row      = $toggle.parents( 'fieldset' ),
                $checkbox = $row.find( 'input[type=checkbox]' ).length > 0 ? $row.find( 'input[type=checkbox]' ) : $toggle.parent().nextAll( 'input[type=checkbox]:first' ),
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

        onChangeInput: function() {
            var self             = germanized.admin,
                $mainInput       = $( this ),
                mainId           = $mainInput.attr( 'id' ) ? $mainInput.attr( 'id' ) : $mainInput.attr( 'name' ),
                $dependentFields = $( '.wc-gzd-admin-settings :input[data-show_if_' + $.escapeSelector( mainId ) + ']' );

            var $input, $field, data, meetsConditions, cleanName, $dependentField, valueExpected, val, isChecked;

            $.each( $dependentFields, function () {
                $input          = $( this );
                $field          = $input.parents( 'tr, .form-field' );
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

            if ( $dependentFields.length > 0 ) {
                self.updateSectionVisibility();
            }
        },

        /**
         * Hide section titles (and descriptions) in case none of the settings
         * of the section is visible, e.g. because they depend on a disabled option.
         */
        updateSectionVisibility: function() {
            $( '.wc-gzd-admin-settings table.form-table' ).each( function() {
                var $table = $( this ),
                    $rows  = $table.find( '> tbody > tr' );

                if ( $rows.length === 0 ) {
                    return;
                }

                var hasVisibleRows = $rows.filter( function() {
                    return ! $( this ).hasClass( 'wc-gzd-setting-invisible' );
                } ).length > 0;

                /**
                 * Find the section title and description belonging to the table:
                 * the previous siblings up to (and including) the next h2.
                 */
                var $sectionHeader = $();
                var $prev          = $table.prev();

                while ( $prev.length > 0 && ! $prev.is( 'table' ) ) {
                    if ( $prev.is( 'h2, p' ) || $prev.is( '[id$="-description"]' ) ) {
                        $sectionHeader = $sectionHeader.add( $prev );
                    }

                    if ( $prev.is( 'h2' ) ) {
                        break;
                    }

                    $prev = $prev.prev();
                }

                $sectionHeader.toggleClass( 'wc-gzd-setting-section-invisible', ! hasVisibleRows );
            } );
        },

        /**
         * Finds the input field by ID or name (some inputs, e.g. radio buttons may not have an id).
         *
         * @param cleanName
         * @returns {*|jQuery}
         */
        getInputByIdOrName: function( cleanName ) {
            var self = germanized.admin;
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
        }
    }


    $( document ).ready( function() {
        germanized.admin.init();
    });

})( jQuery, window.germanized );